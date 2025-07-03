<?php
header('Content-Type: application/json');
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $action = $_POST['action'];
  $ids = json_decode($_POST['ids']);

  if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM citations WHERE citation_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
    $stmt->execute($ids);
    echo json_encode(['status' => 'success', 'message' => 'Selected citations deleted successfully.']);
  } else {
    $archive_value = ($action === 'archive') ? 1 : 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("UPDATE citations SET is_archived = ? WHERE citation_id IN ($placeholders)");
    $stmt->execute(array_merge([$archive_value], $ids));
    echo json_encode(['status' => 'success', 'message' => 'Selected citations ' . $action . 'd successfully.']);
  }
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
$conn = null;
?>