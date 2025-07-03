<?php
header('Content-Type: application/json'); // Ensure JSON response

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Validate citation_id
  $citation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($citation_id <= 0) {
    throw new Exception("Invalid citation ID");
  }

  $conn->beginTransaction();

  // Verify citation exists
  $stmt = $conn->prepare("SELECT COUNT(*) FROM citations WHERE citation_id = :citation_id");
  $stmt->execute(['citation_id' => $citation_id]);
  if ($stmt->fetchColumn() == 0) {
    throw new Exception("Citation not found");
  }

  // Delete related records (violations and remarks)
  $stmt = $conn->prepare("DELETE FROM violations WHERE citation_id = :citation_id");
  $stmt->execute(['citation_id' => $citation_id]);

  $stmt = $conn->prepare("DELETE FROM remarks WHERE citation_id = :citation_id");
  $stmt->execute(['citation_id' => $citation_id]);

  // Delete the citation itself
  $stmt = $conn->prepare("DELETE FROM citations WHERE citation_id = :citation_id");
  $stmt->execute(['citation_id' => $citation_id]);

  // Note: Do NOT delete drivers or vehicles, as they may be referenced by other citations
  // If needed, we can add logic to clean up orphaned drivers/vehicles separately

  $conn->commit();
  echo json_encode(['status' => 'success', 'message' => 'Citation deleted successfully', 'redirect' => 'records.php']);
} catch (PDOException $e) {
  if (isset($conn) && $conn->inTransaction()) {
    $conn->rollBack();
  }
  echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
  if (isset($conn) && $conn->inTransaction()) {
    $conn->rollBack();
  }
  echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}

$conn = null;
?>