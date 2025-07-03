<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $receivedToken = $_GET['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($receivedToken) || $receivedToken !== $sessionToken) {
        throw new Exception('Invalid CSRF token');
    }

    $show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $query = "SELECT COUNT(DISTINCT c.citation_id) as total 
              FROM citations c 
              JOIN drivers d ON c.driver_id = d.driver_id 
              WHERE c.is_archived = :is_archived";
    if ($search) {
        $query .= " AND (c.ticket_number LIKE :search OR CONCAT(d.last_name, ' ', d.first_name) LIKE :search)";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':is_archived', $show_archived ? 1 : 0, PDO::PARAM_INT);
    if ($search) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode(['total' => $total]);
} catch (Exception $e) {
    http_response_code($e instanceof PDOException ? 500 : 403);
    echo json_encode(['error' => $e->getMessage()]);
    error_log("Error in count_citations.php: " . $e->getMessage());
}
$conn = null;
?>