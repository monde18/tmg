<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests if needed

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

$response = ['status' => 'error', 'message' => 'An unknown error occurred'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_POST['citation_id']) || !isset($_POST['amount'])) {
        throw new Exception("Missing required parameters: citation_id or amount.");
    }

    $citation_id = $_POST['citation_id'];
    $amount = floatval($_POST['amount']);

    // Start transaction
    $conn->beginTransaction();

    // Update payment status
    $stmt = $conn->prepare("UPDATE citations SET payment_status = 'Paid', payment_amount = :amount, payment_date = NOW() WHERE citation_id = :id AND payment_status = 'Unpaid'");
    $stmt->execute(['id' => $citation_id, 'amount' => $amount]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("No unpaid citations found or payment already processed.");
    }

    // Commit transaction
    $conn->commit();

    $response = ['status' => 'success', 'message' => 'Payment processed successfully.'];

} catch (PDOException $e) {
    $conn->rollBack();
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = $e->getMessage();
} finally {
    $conn = null;
    echo json_encode($response);
}
?>