<?php
header('Content-Type: application/json');

$host = '127.0.0.1';
$db = 'traffic_citation_db';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
    }
} catch (Exception $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}

$input = json_decode(file_get_contents('php://input'), true);
$search = isset($input['search']) ? $input['search'] : '';
$status = isset($input['status']) ? $input['status'] : 'all';

$query = "
    SELECT c.*, d.last_name, d.first_name, v.vehicle_type
    FROM citations c
    JOIN drivers d ON c.driver_id = d.driver_id
    JOIN vehicles v ON c.vehicle_id = v.vehicle_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (c.ticket_number LIKE ? OR d.last_name LIKE ? OR d.first_name LIKE ? OR c.place_of_apprehension LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($status !== 'all') {
    $query .= " AND c.payment_status = ?";
    $params[] = $status;
    $types .= 's';
}

$query .= " ORDER BY c.apprehension_datetime DESC LIMIT 10";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$citations = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($citations);
$stmt->close();
$conn->close();
?>