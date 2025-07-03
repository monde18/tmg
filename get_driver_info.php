<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get and validate driver_id and citation_id
    $driver_id = isset($_GET['driver_id']) ? filter_var($_GET['driver_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
    $citation_id = isset($_GET['citation_id']) ? filter_var($_GET['citation_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;

    if ($driver_id === false || $driver_id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => 'Invalid driver ID']);
        exit;
    }

    // Fetch driver information
    $driver_stmt = $conn->prepare("
        SELECT license_number, last_name, first_name, middle_initial, suffix
        FROM drivers
        WHERE driver_id = :id
    ");
    $driver_stmt->execute(['id' => $driver_id]);
    $driver = $driver_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'error' => 'Driver not found']);
        exit;
    }

    $driver_name = trim($driver['last_name'] . ', ' . $driver['first_name'] . 
                        ($driver['middle_initial'] ? ' ' . $driver['middle_initial'] : '') . 
                        ($driver['suffix'] ? ' ' . $driver['suffix'] : ''));

    // Fetch offenses with aggregated violations
    $query = "
        SELECT 
            c.citation_id,
            c.apprehension_datetime AS date_time,
            c.payment_status AS status,
            GROUP_CONCAT(CONCAT(vl.violation_type, ' (Offense ', vl.offense_count, ')') SEPARATOR ', ') AS offense,
            COALESCE(SUM(
                CASE vl.offense_count
                    WHEN 1 THEN vt.fine_amount_1
                    WHEN 2 THEN vt.fine_amount_2
                    WHEN 3 THEN vt.fine_amount_3
                    ELSE 150.00
                END
            ), 0) AS fine,
            MAX(vl.offense_count) AS offense_count
        FROM citations c
        LEFT JOIN violations vl ON c.citation_id = vl.citation_id
        LEFT JOIN violation_types vt ON UPPER(vl.violation_type) = UPPER(vt.violation_type)
        WHERE c.driver_id = :driver_id AND c.is_archived = 0
    ";
    $params = [':driver_id' => $driver_id];

    if ($citation_id > 0) {
        $query .= " AND c.citation_id = :citation_id";
        $params[':citation_id'] = $citation_id;
    }

    $query .= " GROUP BY c.citation_id ORDER BY c.apprehension_datetime DESC";

    $offense_stmt = $conn->prepare($query);
    $offense_stmt->execute($params);
    $offenses = $offense_stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Fetched " . count($offenses) . " offenses for driver_id: $driver_id, citation_id: $citation_id");

    // Format offenses for response
    $formatted_offenses = array_map(function($offense) {
        return [
            'date_time' => $offense['date_time'] ? $offense['date_time'] : 'N/A',
            'offense' => $offense['offense'] ?: 'None',
            'offense_count' => intval($offense['offense_count'] ?: 0),
            'fine' => floatval($offense['fine']),
            'status' => $offense['status'] ?: 'Unpaid'
        ];
    }, $offenses);

    // Prepare response
    $response = [
        'status' => 'success',
        'license_number' => $driver['license_number'] ?: 'N/A',
        'driver_name' => $driver_name,
        'offenses' => $formatted_offenses
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_driver_info.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    error_log("Exception in get_driver_info.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}

$conn = null;
?>