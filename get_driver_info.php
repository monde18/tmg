<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $citation_id = filter_input(INPUT_GET, 'citation_id', FILTER_VALIDATE_INT);
    if (!$citation_id) {
        throw new Exception("Invalid citation ID");
    }

$stmt = $conn->prepare("
    SELECT d.first_name, d.last_name, d.license_number, d.address, 
           c.apprehension_datetime, vl.violation_type, 
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
    JOIN drivers d ON c.driver_id = d.driver_id
    WHERE c.driver_id = :driver_id AND c.is_archived = 0
");
    $stmt->execute([':citation_id' => $citation_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format records to include both citation and violation payment status
    $formatted_records = array_map(function($record) {
        return [
            'apprehension_datetime' => $record['apprehension_datetime'] ? $record['apprehension_datetime'] : 'N/A',
            'violation_type' => $record['violation_type'] ?: 'Unknown',
            'fine' => floatval($record['fine']),
            'citation_payment_status' => $record['payment_status'] ?: 'Unpaid',
            'violation_payment_status' => $record['violation_payment_status'] ?: 'Unpaid'
        ];
    }, $records);

    echo json_encode($formatted_records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("PDOException in get_offense_records.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Exception in get_offense_records.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>