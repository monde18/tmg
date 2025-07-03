<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $citation_id = filter_input(INPUT_GET, 'citation_id', FILTER_VALIDATE_INT);
    if (!$citation_id) {
        throw new Exception("Invalid citation ID");
    }

    $stmt = $conn->prepare("
        SELECT apprehension_datetime, violation_type, 
               CASE offense_count
                   WHEN 1 THEN fine_amount_1
                   WHEN 2 THEN fine_amount_2
                   WHEN 3 THEN fine_amount_3
                   ELSE 200
               END AS fine,
               c.payment_status
        FROM citations c
        JOIN violations v ON c.citation_id = v.citation_id
        JOIN violation_types vt ON v.violation_type = vt.violation_type
        WHERE c.citation_id = :citation_id AND c.is_archived = 0
    ");
    $stmt->execute([':citation_id' => $citation_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records);
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