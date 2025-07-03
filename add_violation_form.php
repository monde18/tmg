<?php
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the highest ticket number
    $stmt = $conn->query("SELECT MAX(CAST(ticket_number AS UNSIGNED)) AS max_ticket FROM citations");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $max_ticket = $row['max_ticket'] ? (int)$row['max_ticket'] : 6100;
    $next_ticket = sprintf("%05d", $max_ticket + 1);

    // Pre-fill driver info if driver_id is provided
    $driver_data = [];
    $offense_counts = [];
    if (isset($_GET['driver_id'])) {
        $driver_id = (int)$_GET['driver_id'];
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_id = :driver_id");
        $stmt->execute([':driver_id' => $driver_id]);
        $driver_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch offense counts for this driver from violations
        $stmt = $conn->prepare("
            SELECT vt.violation_type, MAX(v.offense_count) AS offense_count
            FROM violations v
            LEFT JOIN violation_types vt ON v.violation_type = vt.violation_type
            WHERE v.driver_id = :driver_id
            GROUP BY vt.violation_type
        ");
        $stmt->execute([':driver_id' => $driver_id]);
        $offense_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        error_log("Driver ID: $driver_id, Offense Counts: " . print_r($offense_counts, true));
    }

    // Fetch violation types from database
    $stmt = $conn->query("SELECT violation_type FROM violation_types ORDER BY violation_type");
    $valid_violations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $next_ticket = "06101";
    $driver_data = [];
    $valid_violations = [];
    error_log("PDOException in add_violation_form.php: " . $e->getMessage());
}
$conn = null;

// Map violation keys to display names and validate against violation_types
$violation_checkboxes = [
    'noHelmetDriver' => 'NO HELMET (DRIVER)',
    'noHelmetBackrider' => 'NO HELMET (BACKRIDER)',
    'noLicense' => 'NO DRIVER’S LICENSE / MINOR',
    'expiredReg' => 'NO / EXPIRED VEHICLE REGISTRATION',
    'defectiveAccessories' => 'NO / DEFECTIVE PARTS & ACCESSORIES',
    'recklessDriving' => 'RECKLESS / ARROGANT DRIVING',
    'disregardingSigns' => 'DISREGARDING TRAFFIC SIGN',
    'illegalModification' => 'ILLEGAL MODIFICATION',
    'passengerOnTop' => 'PASSENGER ON TOP OF THE VEHICLE',
    'noisyMuffler' => 'NOISY MUFFLER (98DB ABOVE)',
    'noMuffler' => 'NO MUFFLER ATTACHED',
    'illegalParking' => 'ILLEGAL PARKING',
    'roadObstruction' => 'ROAD OBSTRUCTION',
    'blockingPedestrianLane' => 'BLOCKING PEDESTRIAN LANE',
    'loadingUnloadingProhibited' => 'LOADING/UNLOADING IN PROHIBITED ZONE',
    'doubleParking' => 'DOUBLE PARKING',
    'drunkDriving' => 'DRUNK DRIVING',
    'colorumOperation' => 'COLORUM OPERATION',
    'noTrashBin' => 'NO TRASHBIN',
    'drivingInShortSando' => 'DRIVING IN SHORT / SANDO',
    'overloadedPassenger' => 'OVERLOADED PASSENGER',
    'overUnderCharging' => 'OVER CHARGING / UNDER CHARGING',
    'refusalToConvey' => 'REFUSAL TO CONVEY PASSENGER/S',
    'dragRacing' => 'DRAG RACING',
    'noOplanVisaSticker' => 'NO ENHANCED OPLAN VISA STICKER',
    'noEovMatchCard' => 'FAILURE TO PRESENT E-OV MATCH CARD',
    'otherViolation' => !empty($_POST['other_violation_input']) ? htmlspecialchars($_POST['other_violation_input']) : null
];

// Filter out invalid violation types
$violation_checkboxes = array_filter($violation_checkboxes, function($value) use ($valid_violations) {
    return $value === null || in_array($value, $valid_violations);
}, ARRAY_FILTER_USE_BOTH);

// Initialize violation_offenses with default values
$violation_offenses = [];
foreach ($violation_checkboxes as $key => $value) {
    if ($value !== null) {
        $offense_count = isset($offense_counts[$value]) ? (int)$offense_counts[$value] + 1 : 1;
        $violation_offenses[$key] = [
            'name' => $value,
            'offense_count' => $offense_count,
            'label' => $value . ($offense_count > 1 ? " - {$offense_count}" . ($offense_count == 2 ? "nd" : ($offense_count == 3 ? "rd" : "th")) . " Offense" : "")
        ];
        error_log("Violation: $value, Offense Count: $offense_count");
    } else {
        // Handle 'otherViolation' separately
        $violation_offenses[$key] = [
            'name' => 'Other Violation',
            'offense_count' => 1,
            'label' => 'Other Violation'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Add Violation Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f3f5;
            font-family: 'Inter', sans-serif;
        }
        .ticket-container {
            max-width: 1000px;
            background-color: white;
            margin: 40px auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .header {
            background: linear-gradient(90deg, rgb(8, 6, 119), rgb(11, 23, 185));
            color: white;
            padding: 24px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .ticket-number {
            position: absolute;
            top: 20px;
            right: 50px;
            font-weight: 600;
            background: #fff3cd;
            padding: 10px 20px;
            border: 2px solid #f97316;
            border-radius: 8px;
            font-size: 1.2rem;
            color: #1f2937;
        }
        .section {
            background-color: #f8fafc;
            padding: 24px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        .section h5 {
            font-weight: 700;
            margin-bottom: 20px;
            color: #1f2937;
        }
        .form-label {
            font-weight: 500;
            color: #374151;
        }
        .violation-category {
            margin-bottom: 1.5rem;
        }
        .violation-category h6 {
            font-weight: 600;
            margin-bottom: 12px;
            color: #2563eb;
        }
        .violation-list .form-check {
            margin-bottom: 0.5rem;
        }
        .remarks textarea {
            resize: none;
            border-color: #d1d5db;
        }
        .footer {
            font-size: 0.85rem;
            color: #6b7280;
            padding-top: 20px;
            border-top: 1px dashed #d1d5db;
            text-align: justify;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .signature-box {
            flex: 0 0 48%;
        }
        .signature-line {
            border-top: 2px solid #1f2937;
            margin-top: 50px;
        }
        .form-select {
            border-color: #d1d5db;
            transition: border-color 0.2s ease;
        }
        .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn-custom {
            transition: background-color 0.2s ease;
        }
        .btn-custom:hover {
            background-color: #2563eb;
            color: white;
        }
        #otherViolationInput, #otherVehicleInput, #otherBarangayInput {
            display: none;
            margin-top: 10px;
        }
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }
        @media print {
            .ticket-number, .btn-custom {
                display: none;
            }
            .ticket-container {
                box-shadow: none;
                border: none;
                margin: 0;
            }
        }
        @media (max-width: 576px) {
            .ticket-number {
                position: static;
                margin-bottom: 20px;
                text-align: center;
                display: block;
            }
        }
    </style>
</head>
<body>
    <form id="citationForm" action="insert_citation.php" method="POST">
        <div class="ticket-container position-relative">
            <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($next_ticket); ?>">
            <input type="hidden" name="driver_id" value="<?php echo htmlspecialchars($driver_data['driver_id'] ?? ''); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="ticket-number"><?php echo htmlspecialchars($next_ticket); ?></div>
            <a href="driver_records.php" class="btn btn-secondary btn-custom" style="position: absolute; top: 20px; left: 20px;">Back to Driver Records</a>

            <div class="header">
                <h4 class="font-bold text-lg">REPUBLIC OF THE PHILIPPINES</h4>
                <h4 class="font-bold text-lg">PROVINCE OF CAGAYAN • MUNICIPALITY OF BAGGAO</h4>
                <h1 class="font-extrabold text-3xl">ADD VIOLATION FORM</h1>
            </div>

            <!-- Driver Info -->
            <div class="section">
                <h5>Driver Information</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($driver_data['last_name'] ?? ''); ?>" placeholder="Enter last name" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($driver_data['first_name'] ?? ''); ?>" placeholder="Enter first name" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">M.I.</label>
                        <input type="text" name="middle_initial" class="form-control" value="<?php echo htmlspecialchars($driver_data['middle_initial'] ?? ''); ?>" placeholder="M.I.">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" class="form-control" value="<?php echo htmlspecialchars($driver_data['suffix'] ?? ''); ?>" placeholder="e.g., Jr.">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Zone</label>
                        <input type="text" name="zone" class="form-control" value="<?php echo htmlspecialchars($driver_data['zone'] ?? ''); ?>" placeholder="Enter zone">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barangay</label>
                        <select name="barangay" class="form-select" id="barangaySelect" required>
                            <option value="" disabled <?php echo (!isset($driver_data['barangay']) || $driver_data['barangay'] == '') ? 'selected' : ''; ?>>Select Barangay</option>
                            <option value="Adag" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Adag') ? 'selected' : ''; ?>>Adag</option>
                            <option value="Agaman" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman') ? 'selected' : ''; ?>>Agaman</option>
                            <option value="Taytay" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taytay') ? 'selected' : ''; ?>>Taytay</option>
                            <option value="Other" <?php echo (isset($driver_data['barangay']) && !in_array($driver_data['barangay'], ['Adag', 'Agaman', 'Taytay'])) ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <input type="text" name="other_barangay" class="form-control" id="otherBarangayInput" placeholder="Enter other barangay" value="<?php echo (isset($driver_data['barangay']) && !in_array($driver_data['barangay'], ['Adag', 'Agaman', 'Taytay'])) ? htmlspecialchars($driver_data['barangay']) : ''; ?>" <?php echo (isset($driver_data['barangay']) && !in_array($driver_data['barangay'], ['Adag', 'Agaman', 'Taytay'])) ? 'required' : ''; ?>>
                    </div>
                    <div class="col-md-3" id="municipalityDiv" <?php echo (isset($driver_data['barangay']) && !in_array($driver_data['barangay'], ['Adag', 'Agaman', 'Taytay'])) ? 'style="display: none;"' : ''; ?>>
                        <label class="form-label">Municipality</label>
                        <input type="text" name="municipality" class="form-control" id="municipalityInput" value="<?php echo htmlspecialchars($driver_data['municipality'] ?? 'Baggao'); ?>" readonly>
                    </div>
                    <div class="col-md-3" id="provinceDiv" <?php echo (isset($driver_data['barangay']) && !in_array($driver_data['barangay'], ['Adag', 'Agaman', 'Taytay'])) ? 'style="display: none;"' : ''; ?>>
                        <label class="form-label">Province</label>
                        <input type="text" name="province" class="form-control" id="provinceInput" value="<?php echo htmlspecialchars($driver_data['province'] ?? 'Cagayan'); ?>" readonly>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="has_license" id="hasLicense" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hasLicense">Has License</label>
                        </div>
                    </div>
                    <div class="col-md-4 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($driver_data['license_number'] ?? ''); ?>" placeholder="Enter license number" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                    </div>
                    <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                        <label class="form-label d-block">License Type</label>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="license_type" value="nonProf" id="nonProf" <?php echo (!isset($driver_data['license_type']) || $driver_data['license_type'] == 'Non-Professional') ? 'checked' : ''; ?> <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                            <label class="form-check-label" for="nonProf">Non-Prof</label>
                        </div>
                    </div>
                    <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                        <label class="form-label d-block"> </label>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="license_type" value="prof" id="prof" <?php echo (isset($driver_data['license_type']) && $driver_data['license_type'] == 'Professional') ? 'checked' : ''; ?> <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                            <label class="form-check-label" for="prof">Prof</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Info -->
            <div class="section">
                <h5>Vehicle Information</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Plate / MV File / Engine / Chassis No.</label>
                        <input type="text" name="plate_mv_engine_chassis_no" class="form-control" placeholder="Enter plate or other number" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3 mt-1">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="motorcycle" id="motorcycle" value="Motorcycle">
                                <label class="form-check-label" for="motorcycle">Motorcycle</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="tricycle" id="tricycle" value="Tricycle">
                                <label class="form-check-label" for="tricycle">Tricycle</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="suv" id="suv" value="SUV">
                                <label class="form-check-label" for="suv">SUV</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="van" id="van" value="Van">
                                <label class="form-check-label" for="van">Van</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="jeep" id="jeep" value="Jeep">
                                <label class="form-check-label" for="jeep">Jeep</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="truck" id="truck" value="Truck">
                                <label class="form-check-label" for="truck">Truck</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="kulong" id="kulong" value="Kulong">
                                <label class="form-check-label" for="kulong">Kulong</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="othersVehicle" id="othersVehicle" value="Others">
                                <label class="form-check-label" for="othersVehicle">Other</label>
                            </div>
                        </div>
                        <input type="text" name="other_vehicle_input" class="form-control" id="otherVehicleInput" placeholder="Specify other vehicle type">
                        <div id="vehicleTypeError" class="error-message">Please select at least one vehicle type.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Vehicle Description</label>
                        <input type="text" name="vehicle_description" class="form-control" placeholder="Brand, Model, CC, Color, etc.">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Apprehension Date & Time</label>
                        <div class="input-group">
                            <input type="datetime-local" name="apprehension_datetime" class="form-control" id="apprehensionDateTime" required>
                            <button class="btn btn-outline-secondary btn-custom" type="button" id="toggleDateTime" title="Set/Clear">📅</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Place of Apprehension</label>
                        <input type="text" name="place_of_apprehension" class="form-control" placeholder="Enter place of apprehension" required>
                    </div>
                </div>
            </div>

            <!-- Violations -->
            <div class="section">
                <h5 class="text-red-600">Violation(s)</h5>
                <div class="row violation-list">
                    <div class="col-md-6">
                        <div class="violation-category">
                            <h6>Helmet Violations</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noHelmetDriver']['name'] . '|' . $violation_offenses['noHelmetDriver']['offense_count']); ?>" id="noHelmetDriver">
                                <label class="form-check-label" for="noHelmetDriver"><?php echo htmlspecialchars($violation_offenses['noHelmetDriver']['label'] ?? 'NO HELMET (DRIVER)'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noHelmetBackrider']['name'] . '|' . $violation_offenses['noHelmetBackrider']['offense_count']); ?>" id="noHelmetBackrider">
                                <label class="form-check-label" for="noHelmetBackrider"><?php echo htmlspecialchars($violation_offenses['noHelmetBackrider']['label'] ?? 'NO HELMET (BACKRIDER)'); ?></label>
                            </div>
                        </div>
                        <div class="violation-category">
                            <h6>License & Registration</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noLicense']['name'] . '|' . $violation_offenses['noLicense']['offense_count']); ?>" id="noLicense">
                                <label class="form-check-label" for="noLicense"><?php echo htmlspecialchars($violation_offenses['noLicense']['label'] ?? 'NO DRIVER’S LICENSE / MINOR'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['expiredReg']['name'] . '|' . $violation_offenses['expiredReg']['offense_count']); ?>" id="expiredReg">
                                <label class="form-check-label" for="expiredReg"><?php echo htmlspecialchars($violation_offenses['expiredReg']['label'] ?? 'NO / EXPIRED VEHICLE REGISTRATION'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noOplanVisaSticker']['name'] . '|' . $violation_offenses['noOplanVisaSticker']['offense_count']); ?>" id="noOplanVisaSticker">
                                <label class="form-check-label" for="noOplanVisaSticker"><?php echo htmlspecialchars($violation_offenses['noOplanVisaSticker']['label'] ?? 'NO ENHANCED OPLAN VISA STICKER'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noEovMatchCard']['name'] . '|' . $violation_offenses['noEovMatchCard']['offense_count']); ?>" id="noEovMatchCard">
                                <label class="form-check-label" for="noEovMatchCard"><?php echo htmlspecialchars($violation_offenses['noEovMatchCard']['label'] ?? 'FAILURE TO PRESENT E-OV MATCH CARD'); ?></label>
                            </div>
                        </div>
                        <div class="violation-category">
                            <h6>Vehicle Condition</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['defectiveAccessories']['name'] . '|' . $violation_offenses['defectiveAccessories']['offense_count']); ?>" id="defectiveAccessories">
                                <label class="form-check-label" for="defectiveAccessories"><?php echo htmlspecialchars($violation_offenses['defectiveAccessories']['label'] ?? 'NO / DEFECTIVE PARTS & ACCESSORIES'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noisyMuffler']['name'] . '|' . $violation_offenses['noisyMuffler']['offense_count']); ?>" id="noisyMuffler">
                                <label class="form-check-label" for="noisyMuffler"><?php echo htmlspecialchars($violation_offenses['noisyMuffler']['label'] ?? 'NOISY MUFFLER (98DB ABOVE)'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noMuffler']['name'] . '|' . $violation_offenses['noMuffler']['offense_count']); ?>" id="noMuffler">
                                <label class="form-check-label" for="noMuffler"><?php echo htmlspecialchars($violation_offenses['noMuffler']['label'] ?? 'NO MUFFLER ATTACHED'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['illegalModification']['name'] . '|' . $violation_offenses['noMuffler']['offense_count']); ?>" id="illegalModification">
                                <label class="form-check-label" for="illegalModification"><?php echo htmlspecialchars($violation_offenses['illegalModification']['label'] ?? 'ILLEGAL MODIFICATION'); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="violation-category">
                            <h6>Driving Behavior</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['recklessDriving']['name'] . '|' . $violation_offenses['recklessDriving']['offense_count']); ?>" id="recklessDriving">
                                <label class="form-check-label" for="recklessDriving"><?php echo htmlspecialchars($violation_offenses['recklessDriving']['label'] ?? 'RECKLESS / ARROGANT DRIVING'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['dragRacing']['name'] . '|' . $violation_offenses['dragRacing']['offense_count']); ?>" id="dragRacing">
                                <label class="form-check-label" for="dragRacing"><?php echo htmlspecialchars($violation_offenses['dragRacing']['label'] ?? 'DRAG RACING'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['disregardingSigns']['name'] . '|' . $violation_offenses['disregardingSigns']['offense_count']); ?>" id="disregardingSigns">
                                <label class="form-check-label" for="disregardingSigns"><?php echo htmlspecialchars($violation_offenses['disregardingSigns']['label'] ?? 'DISREGARDING TRAFFIC SIGN'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['drunkDriving']['name'] . '|' . $violation_offenses['drunkDriving']['offense_count']); ?>" id="drunkDriving">
                                <label class="form-check-label" for="drunkDriving"><?php echo htmlspecialchars($violation_offenses['drunkDriving']['label'] ?? 'DRUNK DRIVING'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['drivingInShortSando']['name'] . '|' . $violation_offenses['drivingInShortSando']['offense_count']); ?>" id="drivingInShortSando">
                                <label class="form-check-label" for="drivingInShortSando"><?php echo htmlspecialchars($violation_offenses['drivingInShortSando']['label'] ?? 'DRIVING IN SHORT / SANDO'); ?></label>
                            </div>
                        </div>
                        <div class="violation-category">
                            <h6>Traffic Violations</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['illegalParking']['name'] . '|' . $violation_offenses['illegalParking']['offense_count']); ?>" id="illegalParking">
                                <label class="form-check-label" for="illegalParking"><?php echo htmlspecialchars($violation_offenses['illegalParking']['label'] ?? 'ILLEGAL PARKING'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['roadObstruction']['name'] . '|' . $violation_offenses['roadObstruction']['offense_count']); ?>" id="roadObstruction">
                                <label class="form-check-label" for="roadObstruction"><?php echo htmlspecialchars($violation_offenses['roadObstruction']['label'] ?? 'ROAD OBSTRUCTION'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['blockingPedestrianLane']['name'] . '|' . $violation_offenses['blockingPedestrianLane']['offense_count']); ?>" id="blockingPedestrianLane">
                                <label class="form-check-label" for="blockingPedestrianLane"><?php echo htmlspecialchars($violation_offenses['blockingPedestrianLane']['label'] ?? 'BLOCKING PEDESTRIAN LANE'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['loadingUnloadingProhibited']['name'] . '|' . $violation_offenses['loadingUnloadingProhibited']['offense_count']); ?>" id="loadingUnloadingProhibited">
                                <label class="form-check-label" for="loadingUnloadingProhibited"><?php echo htmlspecialchars($violation_offenses['loadingUnloadingProhibited']['label'] ?? 'LOADING/UNLOADING IN PROHIBITED ZONE'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['doubleParking']['name'] . '|' . $violation_offenses['doubleParking']['offense_count']); ?>" id="doubleParking">
                                <label class="form-check-label" for="doubleParking"><?php echo htmlspecialchars($violation_offenses['doubleParking']['label'] ?? 'DOUBLE PARKING'); ?></label>
                            </div>
                        </div>
                        <div class="violation-category">
                            <h6>Passenger & Operator Violations</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['passengerOnTop']['name'] . '|' . $violation_offenses['passengerOnTop']['offense_count']); ?>" id="passengerOnTop">
                                <label class="form-check-label" for="passengerOnTop"><?php echo htmlspecialchars($violation_offenses['passengerOnTop']['label'] ?? 'PASSENGER ON TOP OF THE VEHICLE'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['colorumOperation']['name'] . '|' . $violation_offenses['colorumOperation']['offense_count']); ?>" id="colorumOperation">
                                <label class="form-check-label" for="colorumOperation"><?php echo htmlspecialchars($violation_offenses['colorumOperation']['label'] ?? 'COLORUM OPERATION'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['noTrashBin']['name'] . '|' . $violation_offenses['noTrashBin']['offense_count']); ?>" id="noTrashBin">
                                <label class="form-check-label" for="noTrashBin"><?php echo htmlspecialchars($violation_offenses['noTrashBin']['label'] ?? 'NO TRASHBIN'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['overloadedPassenger']['name'] . '|' . $violation_offenses['overloadedPassenger']['offense_count']); ?>" id="overloadedPassenger">
                                <label class="form-check-label" for="overloadedPassenger"><?php echo htmlspecialchars($violation_offenses['overloadedPassenger']['label'] ?? 'OVERLOADED PASSENGER'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['overUnderCharging']['name'] . '|' . $violation_offenses['overUnderCharging']['offense_count']); ?>" id="overUnderCharging">
                                <label class="form-check-label" for="overUnderCharging"><?php echo htmlspecialchars($violation_offenses['overUnderCharging']['label'] ?? 'OVER CHARGING / UNDER CHARGING'); ?></label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" value="<?php echo htmlspecialchars($violation_offenses['refusalToConvey']['name'] . '|' . $violation_offenses['refusalToConvey']['offense_count']); ?>" id="refusalToConvey">
                                <label class="form-check-label" for="refusalToConvey"><?php echo htmlspecialchars($violation_offenses['refusalToConvey']['label'] ?? 'REFUSAL TO CONVEY PASSENGER/S'); ?></label>
                            </div>
                        </div>
                        <div class="violation-category">
                            <h6>Other Violations</h6>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="violations[]" id="otherViolation">
                                <label class="form-check-label" for="otherViolation"><?php echo htmlspecialchars($violation_offenses['otherViolation']['label'] ?? 'Other Violation'); ?></label>
                                <input type="text" name="other_violation_input" class="form-control" id="otherViolationInput" placeholder="Specify other violation" value="<?php echo !empty($_POST['other_violation_input']) ? htmlspecialchars($_POST['other_violation_input']) : ''; ?>">
                            </div>
                            <div id="violationError" class="error-message">Please select at least one violation.</div>
                        </div>
                    </div>
                    <div class="col-12 mt-3 remarks">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter additional remarks"></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>
                    All apprehensions are deemed admitted unless contested by filing a written contest at the Traffic Management Office within five (5) working days from date of issuance.
                    Failure to pay the corresponding penalty at the Municipal Treasury Office within fifteen (15) days from date of apprehension, shall be the ground for filing a formal complaint against you.
                    Likewise, a copy of this ticket shall be forwarded to concerned agencies for proper action/disposition.
                </p>
            </div>

            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-box">
                    <p class="font-medium">Signature of Vehicle Driver</p>
                    <div class="signature-line"></div>
                </div>
                <div class="signature-box">
                    <p class="font-medium">Name, Rank & Signature of Apprehending Officer</p>
                    <div class="signature-line"></div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-custom mt-4">Submit Violation</button>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const barangaySelect = document.getElementById('barangaySelect');
            const otherBarangayInput = document.getElementById('otherBarangayInput');
            const municipalityDiv = document.getElementById('municipalityDiv');
            const provinceDiv = document.getElementById('provinceDiv');
            const hasLicenseCheckbox = document.getElementById('hasLicense');
            const licenseFields = document.querySelectorAll('.license-field');
            const toggleBtn = document.getElementById('toggleDateTime');
            const dateTimeInput = document.getElementById('apprehensionDateTime');
            const otherViolationCheckbox = document.getElementById('otherViolation');
            const otherViolationInput = document.getElementById('otherViolationInput');
            const otherVehicleCheckbox = document.getElementById('othersVehicle');
            const otherVehicleInput = document.getElementById('otherVehicleInput');
            const vehicleTypeError = document.getElementById('vehicleTypeError');
            const violationError = document.getElementById('violationError');
            const vehicleCheckboxes = document.querySelectorAll('input[name$="Vehicle"], input[name="motorcycle"], input[name="tricycle"], input[name="suv"], input[name="van"], input[name="jeep"], input[name="truck"], input[name="kulong"]');
            let isAutoFilled = false;

            // Toggle License Fields
            hasLicenseCheckbox.addEventListener('change', () => {
                const isChecked = hasLicenseCheckbox.checked;
                licenseFields.forEach(field => {
                    field.style.display = isChecked ? 'block' : 'none';
                    const inputs = field.querySelectorAll('input');
                    inputs.forEach(input => {
                        input.required = isChecked;
                        if (!isChecked) {
                            input.value = '';
                            if (input.type === 'radio') input.checked = false;
                        }
                    });
                });
            });

            // Handle Barangay "Other" option
            barangaySelect.addEventListener('change', () => {
                const isOther = barangaySelect.value === 'Other';
                otherBarangayInput.style.display = isOther ? 'block' : 'none';
                otherBarangayInput.required = isOther;
                municipalityDiv.style.display = isOther ? 'none' : 'block';
                provinceDiv.style.display = isOther ? 'none' : 'block';
                if (isOther) {
                    municipalityDiv.querySelector('input').value = '';
                    provinceDiv.querySelector('input').value = '';
                } else if (barangaySelect.value) {
                    municipalityDiv.querySelector('input').value = 'Baggao';
                    provinceDiv.querySelector('input').value = 'Cagayan';
                }
                if (!isOther && otherBarangayInput.value) otherBarangayInput.value = '';
            });

            // Toggle DateTime button
            toggleBtn.addEventListener('click', () => {
                if (!isAutoFilled) {
                    const now = new Date();
                    const offset = now.getTimezoneOffset();
                    now.setMinutes(now.getMinutes() - offset); // Adjust for local timezone
                    const formatted = now.toISOString().slice(0, 16);
                    dateTimeInput.value = formatted;
                    isAutoFilled = true;
                    toggleBtn.innerText = '❌';
                    toggleBtn.classList.remove('btn-outline-secondary');
                    toggleBtn.classList.add('btn-outline-danger');
                } else {
                    dateTimeInput.value = '';
                    isAutoFilled = false;
                    toggleBtn.innerText = '📅';
                    toggleBtn.classList.remove('btn-outline-danger');
                    toggleBtn.classList.add('btn-outline-secondary');
                }
            });

            // Show/hide Other Violation input
            otherViolationCheckbox.addEventListener('change', () => {
                otherViolationInput.style.display = otherViolationCheckbox.checked ? 'block' : 'none';
                otherViolationInput.required = otherViolationCheckbox.checked;
                if (!otherViolationCheckbox.checked) {
                    otherViolationInput.value = '';
                }
            });

            // Show/hide Other Vehicle Type input
            otherVehicleCheckbox.addEventListener('change', () => {
                otherVehicleInput.style.display = otherVehicleCheckbox.checked ? 'block' : 'none';
                otherVehicleInput.required = otherVehicleCheckbox.checked;
                if (!otherVehicleCheckbox.checked) {
                    otherVehicleInput.value = '';
                }
            });

            // Ensure only one license type is selected
            const nonProfCheckbox = document.getElementById('nonProf');
            const profCheckbox = document.getElementById('prof');
            nonProfCheckbox.addEventListener('change', () => {
                if (nonProfCheckbox.checked) {
                    profCheckbox.checked = false;
                }
            });
            profCheckbox.addEventListener('change', () => {
                if (profCheckbox.checked) {
                    nonProfCheckbox.checked = false;
                }
            });

            // Handle form submission with AJAX
            document.getElementById('citationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                vehicleTypeError.style.display = 'none';
                violationError.style.display = 'none';

                // Validate vehicle type
                let vehicleSelected = Array.from(vehicleCheckboxes).some(cb => cb.checked);
                if (!vehicleSelected || (otherVehicleCheckbox.checked && !otherVehicleInput.value.trim())) {
                    vehicleTypeError.style.display = 'block';
                    vehicleTypeError.scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                // Validate violations
                const violations = document.querySelectorAll('input[name="violations[]"]:checked');
                if (violations.length === 0 || (otherViolationCheckbox.checked && !otherViolationInput.value.trim())) {
                    violationError.style.display = 'block';
                    violationError.scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                const formData = new FormData(this);
                console.log('Form Data:', Object.fromEntries(formData));
                fetch('insert_citation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        window.location.href = `driver_records.php?driver_id=${formData.get('driver_id') || ''}`;
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Error submitting form: ' + error.message);
                });
            });
        });
    </script>
</body>
</html>