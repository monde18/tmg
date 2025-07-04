<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

try {
    // Validate CSRF token
    $csrf_token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING);
    if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid CSRF token");
    }

    // Connect to database
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get driver_id from query parameter
    $driver_id = filter_input(INPUT_GET, 'driver_id', FILTER_VALIDATE_INT);
    if (!$driver_id) {
        throw new Exception("Invalid driver ID");
    }

    // Query to fetch all violations for the driver
    $stmt = $conn->prepare("
        SELECT c.apprehension_datetime, vl.violation_type, 
               CASE vl.offense_count
                   WHEN 1 THEN vt.fine_amount_1
                   WHEN 2 THEN vt.fine_amount_2
                   WHEN 3 THEN vt.fine_amount_3
                   ELSE 500.00
               END AS fine,
               vl.payment_status AS violation_payment_status
        FROM citations c
        JOIN violations vl ON c.citation_id = vl.citation_id
        JOIN violation_types vt ON UPPER(vl.violation_type) = UPPER(vt.violation_type)
        WHERE c.driver_id = :driver_id AND c.is_archived = 0
    ");
    $stmt->execute([':driver_id' => $driver_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format records for JSON response
    $formatted_records = array_map(function($record) {
        return [
            'apprehension_datetime' => $record['apprehension_datetime'] ?: 'N/A',
            'violation_type' => $record['violation_type'] ?: 'Unknown',
            'fine' => floatval($record['fine']),
            'violation_payment_status' => $record['violation_payment_status'] ?: 'Unpaid'
        ];
    }, $records);

    echo json_encode($formatted_records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("PDOException in get_driver_info.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Exception in get_driver_info.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>