<?php
header('Content-Type: application/json'); // Ensure JSON response

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sanitize inputs
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid CSRF token");
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate CSRF token

    // Validate and sanitize form inputs
    $citation_id = isset($_POST['citation_id']) ? (int)$_POST['citation_id'] : 0;
    $ticket_number = isset($_POST['ticket_number']) ? sanitize($_POST['ticket_number']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
    $first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
    $middle_initial = isset($_POST['middle_initial']) ? sanitize($_POST['middle_initial']) : '';
    $suffix = isset($_POST['suffix']) ? sanitize($_POST['suffix']) : '';
    $zone = isset($_POST['zone']) ? sanitize($_POST['zone']) : '';
    $barangay = isset($_POST['barangay']) ? sanitize($_POST['barangay']) : '';
    $other_barangay = isset($_POST['other_barangay']) ? sanitize($_POST['other_barangay']) : ''; // New field for "Other" barangay
    $municipality = isset($_POST['municipality']) ? sanitize($_POST['municipality']) : 'Baggao';
    $province = isset($_POST['province']) ? sanitize($_POST['province']) : 'Cagayan';
    $license_number = isset($_POST['license_number']) ? sanitize($_POST['license_number']) : '';
    $license_type = isset($_POST['license_type']) ? ($_POST['license_type'] === 'prof' ? 'Professional' : 'Non-Professional') : null;
    $plate_mv_engine_chassis_no = isset($_POST['plate_mv_engine_chassis_no']) ? sanitize($_POST['plate_mv_engine_chassis_no']) : '';

    // Handle vehicle type
    $vehicle_types = [];
    $vehicle_type_checkboxes = ['motorcycle', 'tricycle', 'suv', 'van', 'jeep', 'truck', 'kulong', 'othersVehicle'];
    foreach ($vehicle_type_checkboxes as $type) {
        if (isset($_POST[$type]) && $_POST[$type]) {
            $vehicle_types[] = ($type === 'othersVehicle' && !empty($_POST['other_vehicle_input'])) 
                ? sanitize($_POST['other_vehicle_input']) 
                : ucfirst($type);
        }
    }
    $vehicle_type = !empty($vehicle_types) ? $vehicle_types[0] : 'Unknown';
    if (empty($vehicle_types)) {
        throw new Exception("At least one vehicle type must be selected");
    }

    $vehicle_description = isset($_POST['vehicle_description']) ? sanitize($_POST['vehicle_description']) : '';
    $apprehension_datetime = isset($_POST['apprehension_datetime']) ? sanitize($_POST['apprehension_datetime']) : null;
    if ($apprehension_datetime && !DateTime::createFromFormat('Y-m-d\TH:i', $apprehension_datetime)) {
        throw new Exception("Invalid apprehension date and time format");
    }
    $place_of_apprehension = isset($_POST['place_of_apprehension']) ? sanitize($_POST['place_of_apprehension']) : '';
    $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : '';

    // Handle violations dynamically
    $violations = [];
    $stmt = $conn->prepare("SELECT violation_type FROM violation_types");
    $stmt->execute();
    $all_violation_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check all possible violation checkboxes
    foreach ($all_violation_types as $violation_type) {
        $key = strtolower(str_replace([' ', '/', '(', ')', '&'], '', $violation_type));
        if (isset($_POST[$key]) && $_POST[$key]) {
            $violations[] = $violation_type;
        }
    }

    // Check for custom violations
    foreach ($_POST as $key => $value) {
        if (preg_match('/^other_violation_input_(\d+|new)$/', $key) && !empty($value)) {
            $checkbox_key = str_replace('input', '', $key);
            if (isset($_POST[$checkbox_key]) && $_POST[$checkbox_key]) {
                $violations[] = sanitize($value);
            }
        }
    }

    if (empty($violations)) {
        throw new Exception("At least one violation must be selected");
    }

    // Input validation
    if ($citation_id <= 0) {
        throw new Exception("Invalid citation ID");
    }
    if (empty($ticket_number)) {
        throw new Exception("Ticket number is required");
    }
    if (empty($last_name) || empty($first_name)) {
        throw new Exception("Driver's last name and first name are required");
    }
    if ($barangay === 'Other' && empty($other_barangay)) {
        throw new Exception("Please specify the other barangay");
    }

    // Check for duplicate ticket number (excluding the current citation)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM citations WHERE ticket_number = :ticket_number AND citation_id != :citation_id");
    $stmt->execute([':ticket_number' => $ticket_number, ':citation_id' => $citation_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Ticket number $ticket_number already exists");
    }

    $conn->beginTransaction();

    // Get driver_id and vehicle_id
    $stmt = $conn->prepare("SELECT driver_id, vehicle_id FROM citations WHERE citation_id = :citation_id");
    $stmt->execute([':citation_id' => $citation_id]);
    $ids = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ids) {
        throw new Exception("Citation not found");
    }
    $driver_id = $ids['driver_id'];
    $vehicle_id = $ids['vehicle_id'];

    // Update drivers table
    $stmt = $conn->prepare("
        UPDATE drivers SET
            last_name = :last_name,
            first_name = :first_name,
            middle_initial = :middle_initial,
            suffix = :suffix,
            zone = :zone,
            barangay = :barangay,
            municipality = :municipality,
            province = :province,
            license_number = :license_number,
            license_type = :license_type
        WHERE driver_id = :driver_id
    ");
    $stmt->execute([
        ':last_name' => $last_name,
        ':first_name' => $first_name,
        ':middle_initial' => $middle_initial ?: null,
        ':suffix' => $suffix ?: null,
        ':zone' => $zone ?: null,
        ':barangay' => $barangay === 'Other' ? $other_barangay : $barangay,
        ':municipality' => $barangay === 'Other' ? null : $municipality,
        ':province' => $barangay === 'Other' ? null : $province,
        ':license_number' => $license_number ?: null,
        ':license_type' => $license_type ?: null,
        ':driver_id' => $driver_id
    ]);

    // Update vehicles table
    $stmt = $conn->prepare("
        UPDATE vehicles SET
            plate_mv_engine_chassis_no = :plate_mv_engine_chassis_no,
            vehicle_type = :vehicle_type,
            vehicle_description = :vehicle_description
        WHERE vehicle_id = :vehicle_id
    ");
    $stmt->execute([
        ':plate_mv_engine_chassis_no' => $plate_mv_engine_chassis_no,
        ':vehicle_type' => $vehicle_type,
        ':vehicle_description' => $vehicle_description ?: null,
        ':vehicle_id' => $vehicle_id
    ]);

    // Update citations table
    $stmt = $conn->prepare("
        UPDATE citations SET
            ticket_number = :ticket_number,
            apprehension_datetime = :apprehension_datetime,
            place_of_apprehension = :place_of_apprehension
        WHERE citation_id = :citation_id
    ");
    $stmt->execute([
        ':ticket_number' => $ticket_number,
        ':apprehension_datetime' => $apprehension_datetime,
        ':place_of_apprehension' => $place_of_apprehension,
        ':citation_id' => $citation_id
    ]);

    // Delete existing violations
    $stmt = $conn->prepare("DELETE FROM violations WHERE citation_id = :citation_id");
    $stmt->execute([':citation_id' => $citation_id]);

    // Insert new violations with offense count and driver_id
    if (!empty($violations)) {
        $stmt_count = $conn->prepare("
            SELECT COUNT(*) AS count 
            FROM violations 
            WHERE driver_id = :driver_id AND violation_type = :violation_type
        ");
        $insertStmt = $conn->prepare("
            INSERT INTO violations (citation_id, driver_id, violation_type, offense_count)
            VALUES (:citation_id, :driver_id, :violation_type, :offense_count)
        ");
        foreach ($violations as $violation) {
            $stmt_count->execute([':driver_id' => $driver_id, ':violation_type' => $violation]);
            $offense_count = $stmt_count->fetchColumn() + 1;
            $insertStmt->execute([
                ':citation_id' => $citation_id,
                ':driver_id' => $driver_id,
                ':violation_type' => $violation,
                ':offense_count' => $offense_count
            ]);
        }
    }

    // Update or insert remarks
    $stmt = $conn->prepare("SELECT remark_id FROM remarks WHERE citation_id = :citation_id");
    $stmt->execute([':citation_id' => $citation_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        if (empty($remarks)) {
            $stmt = $conn->prepare("DELETE FROM remarks WHERE citation_id = :citation_id");
            $stmt->execute([':citation_id' => $citation_id]);
        } else {
            $stmt = $conn->prepare("UPDATE remarks SET remark_text = :remark_text WHERE citation_id = :citation_id");
            $stmt->execute([':remark_text' => $remarks, ':citation_id' => $citation_id]);
        }
    } elseif (!empty($remarks)) {
        $stmt = $conn->prepare("INSERT INTO remarks (citation_id, remark_text) VALUES (:citation_id, :remark_text)");
        $stmt->execute([':citation_id' => $citation_id, ':remark_text' => $remarks]);
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Citation updated successfully', 'new_csrf_token' => $_SESSION['csrf_token']]);
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    file_put_contents('error.log', date('Y-m-d H:i:s') . ' - Database error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    file_put_contents('error.log', date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}

$conn = null;
?>