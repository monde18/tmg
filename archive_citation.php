<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    throw new Exception("Invalid request method. Use POST.");
  }

  $citation_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $archive = isset($_POST['archive']) ? (int)$_POST['archive'] : 0;
  $remarksReason = isset($_POST['remarksReason']) ? htmlspecialchars(trim($_POST['remarksReason']), ENT_QUOTES, 'UTF-8') : '';

  if ($citation_id <= 0) {
    throw new Exception("Invalid citation ID");
  }
  if ($archive !== 0 && $archive !== 1) {
    throw new Exception("Invalid archive status");
  }

  $conn->beginTransaction();

  // Update citation archive status
  $stmt = $conn->prepare("UPDATE citations SET is_archived = :is_archived WHERE citation_id = :citation_id");
  $stmt->execute([':is_archived' => $archive, ':citation_id' => $citation_id]);

  // Handle remarks if provided
  if ($remarksReason) {
    $stmt = $conn->prepare("SELECT remark_id FROM remarks WHERE citation_id = :citation_id");
    $stmt->execute([':citation_id' => $citation_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
      $stmt = $conn->prepare("UPDATE remarks SET remark_text = :remark_text WHERE citation_id = :citation_id");
      $stmt->execute([':remark_text' => $remarksReason, ':citation_id' => $citation_id]);
    } else {
      $stmt = $conn->prepare("INSERT INTO remarks (citation_id, remark_text) VALUES (:citation_id, :remark_text)");
      $stmt->execute([':citation_id' => $citation_id, ':remark_text' => $remarksReason]);
    }
  }

  $conn->commit();
  echo json_encode(['status' => 'success', 'message' => 'Citation ' . ($archive ? 'archived' : 'unarchived') . ' successfully', 'redirect' => 'citations.php' . ($archive ? '?show_archived=1' : '')]);
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