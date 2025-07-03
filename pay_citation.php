<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid CSRF token.");
    }

    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $citation_id = $_POST['citation_id'] ?? null;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $violation_ids = isset($_POST['violation_ids']) && is_array($_POST['violation_ids']) ? array_map('intval', $_POST['violation_ids']) : [];

    error_log("Received request: citation_id=$citation_id, amount=$amount, violation_ids=" . print_r($violation_ids, true), 3, 'payment.log');

    if (!$citation_id) {
        throw new Exception("Missing required parameter: citation_id.");
    }

    if (empty($violation_ids)) {
        throw new Exception("No violations selected for payment.");
    }

    if ($amount <= 0 || $amount > 100000) {
        throw new Exception("Invalid payment amount. Must be between ₱0.01 and ₱100,000.");
    }

    $conn->beginTransaction();

    // Calculate total fine for selected violations
    $placeholders = implode(',', array_fill(0, count($violation_ids), '?'));
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(
            COALESCE(
                CASE v.offense_count
                    WHEN 1 THEN vt.fine_amount_1
                    WHEN 2 THEN vt.fine_amount_2
                    WHEN 3 THEN vt.fine_amount_3
                    ELSE 500.00
                END, 500.00
            )
        ), 0) AS total_fine
        FROM violations v
        LEFT JOIN violation_types vt ON UPPER(v.violation_type) = UPPER(vt.violation_type)
        WHERE v.citation_id = ? AND v.violation_id IN ($placeholders)
    ");
    $stmt->execute(array_merge([$citation_id], $violation_ids));
    $total_fine = $stmt->fetchColumn();

    if ($total_fine === false || $total_fine == 0) {
        throw new Exception("No valid violations found for this citation.");
    }

    if ($amount < $total_fine) {
        throw new Exception("Payment amount (₱$amount) is less than the total fine (₱$total_fine) for selected violations.");
    }

    // Generate unique reference number (PAY-YYYYMMDD-HHMM-XXXX)
    $datePart = date('Ymd-Hi', strtotime('04:25 PM PST')); // Current date and time
    $maxAttempts = 10;
    $attempt = 0;
    do {
        $randomPart = sprintf("%04d", mt_rand(0, 9999));
        $referenceNumber = "PAY-$datePart-$randomPart";
        $stmt = $conn->prepare("SELECT COUNT(*) FROM citations WHERE reference_number = ?");
        $stmt->execute([$referenceNumber]);
        $exists = $stmt->fetchColumn() > 0;
        $attempt++;
    } while ($exists && $attempt < $maxAttempts);

    if ($exists) {
        throw new Exception("Unable to generate unique reference number.");
    }

    // Update citation
    $stmt = $conn->prepare("
        UPDATE citations 
        SET payment_status = CASE 
            WHEN (SELECT COUNT(*) FROM violations v WHERE v.citation_id = ? AND v.payment_status = 'Unpaid') = 0 THEN 'Paid'
            ELSE 'Partially Paid'
        END,
            payment_amount = payment_amount + ?,
            payment_date = NOW(),
            reference_number = ?
        WHERE citation_id = ? AND payment_status IN ('Unpaid', 'Partially Paid')
    ");
    $stmt->execute([$citation_id, $amount, $referenceNumber, $citation_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("No unpaid or partially paid citations found or payment already processed.");
    }

    // Update violations (optional, if tracking individual payment status)
    // Uncomment and adjust if you add payment_status to violations table
    /*
    $stmt = $conn->prepare("UPDATE violations SET payment_status = 'Paid' WHERE violation_id IN ($placeholders) AND payment_status = 'Unpaid'");
    $stmt->execute($violation_ids);
    */

    $conn->commit();

    echo json_encode(
        [
            'status' => 'success',
            'message' => 'Payment processed successfully for selected violations.',
            'payment_date' => date('Y-m-d H:i:s'),
            'reference_number' => $referenceNumber,
            'change' => number_format($amount - $total_fine, 2)
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Payment processing error: " . $e->getMessage(), 3, 'payment.log');
    echo json_encode(
        ['status' => 'error', 'message' => 'An error occurred while processing the payment.'],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Payment processing error: " . $e->getMessage(), 3, 'payment.log');
    echo json_encode(
        ['status' => 'error', 'message' => $e->getMessage()],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
} finally {
    $conn = null;
}
?>