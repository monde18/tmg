<?php
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "traffic_citation_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $citation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $conn->prepare("
        SELECT c.ticket_number, c.apprehension_datetime, c.place_of_apprehension,
               d.last_name, d.first_name, d.middle_initial, d.suffix, d.zone, d.barangay,
               d.municipality, d.province, d.license_number, d.license_type,
               v.plate_mv_engine_chassis_no, v.vehicle_type, v.vehicle_description,
               r.remark_text
        FROM citations c
        JOIN drivers d ON c.driver_id = d.driver_id
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        LEFT JOIN remarks r ON c.citation_id = r.citation_id
        WHERE c.citation_id = :citation_id
    ");
    $stmt->execute([':citation_id' => $citation_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo "Citation not found.";
        exit;
    }

    // Fetch all violation types
    $stmt = $conn->prepare("SELECT violation_type FROM violation_types ORDER BY violation_type");
    $stmt->execute();
    $all_violation_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch violations for this citation
    $stmt = $conn->prepare("SELECT violation_type FROM violations WHERE citation_id = :citation_id");
    $stmt->execute([':citation_id' => $citation_id]);
    $violations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Categorize violations (for display purposes)
    $categories = [
        'Helmet Violations' => [],
        'License / Registration' => [],
        'Vehicle Condition' => [],
        'Reckless / Improper Driving' => [],
        'Traffic Rules' => [],
        'Passenger / Load Violations' => [],
        'Other Violations' => []
    ];

    foreach ($all_violation_types as $violation) {
        if (stripos($violation, 'HELMET') !== false) {
            $categories['Helmet Violations'][] = $violation;
        } elseif (stripos($violation, 'LICENSE') !== false || stripos($violation, 'REGISTRATION') !== false) {
            $categories['License / Registration'][] = $violation;
        } elseif (stripos($violation, 'PARTS') !== false || stripos($violation, 'MUFFLER') !== false || stripos($violation, 'MODIFICATION') !== false) {
            $categories['Vehicle Condition'][] = $violation;
        } elseif (stripos($violation, 'RECKLESS') !== false || stripos($violation, 'DRAG RACING') !== false || stripos($violation, 'DRUNK') !== false) {
            $categories['Reckless / Improper Driving'][] = $violation;
        } elseif (stripos($violation, 'TRAFFIC') !== false || stripos($violation, 'PARKING') !== false || stripos($violation, 'OBSTRUCTION') !== false || stripos($violation, 'PEDESTRIAN') !== false || stripos($violation, 'LOADING') !== false) {
            $categories['Traffic Rules'][] = $violation;
        } elseif (stripos($violation, 'PASSENGER') !== false || stripos($violation, 'OVERLOADED') !== false) {
            $categories['Passenger / Load Violations'][] = $violation;
        } else {
            $categories['Other Violations'][] = $violation;
        }
    }

    // Handle vehicle type
    $vehicle_type = strtolower($data['vehicle_type'] ?? '');
    $vehicle_types = [$vehicle_type];

    // Format apprehension datetime
    $apprehension_datetime = $data['apprehension_datetime'] && $data['apprehension_datetime'] != '0000-00-00 00:00:00'
        ? date('Y-m-d\TH:i', strtotime($data['apprehension_datetime']))
        : '';
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Traffic Citation Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <style>
        :root {
            --primary: #1e3a8a;
            --primary-dark: #1e40af;
            --secondary: #6b7280;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f97316;
            --bg-light: #f8fafc;
            --text-dark: #1f2937;
            --border: #d1d5db;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin: 1rem auto;
            width: calc(100% - 2rem);
            max-width: 1200px;
        }

        .container:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }

        .header {
            background: linear-gradient(90deg, var(--primary), #3b82f6);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--warning), #facc15);
        }

        .ticket-number {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-weight: 600;
            background: #fef3c7;
            padding: 0.5rem 1rem;
            border: 2px solid var(--warning);
            border-radius: 8px;
            font-size: 1rem;
            color: var(--text-dark);
            transition: transform 0.2s ease;
        }

        .ticket-number:hover {
            transform: scale(1.05);
        }

        .section {
            background-color: #f9fafb;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            transition: background-color 0.2s ease;
        }

        .section h5 {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary);
            font-size: 1.2rem;
            border-bottom: 2px solid var(--border);
            padding-bottom: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border);
            padding: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .form-control.is-invalid {
            border-color: var(--danger);
            background-color: #fef2f2;
        }

        .violation-category {
            margin-bottom: 1.5rem;
        }

        .violation-category h6 {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #2563eb;
            font-size: 1rem;
        }

        .violation-list .form-check {
            margin-bottom: 0.5rem;
            padding-left: 2rem;
        }

        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .btn-custom {
            background-color: #2563eb;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline-secondary, .btn-outline-danger {
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        #otherViolationInput, #otherVehicleInput, #otherBarangayInput {
            display: none;
            margin-top: 0.5rem;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .header h4 {
                font-size: 0.9rem;
            }

            .ticket-number {
                position: static;
                margin: 0 auto 1rem;
                text-align: center;
                display: block;
            }

            .section {
                padding: 1rem;
            }

            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 0.4rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0.75rem;
            }

            .header h1 {
                font-size: 1.25rem;
            }

            .form-label {
                font-size: 0.8rem;
            }

            .form-control, .form-select {
                font-size: 0.8rem;
                padding: 0.3rem;
            }

            .btn-custom {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }

        @media print {
            .ticket-number, .btn-custom, .btn-outline-secondary, .btn-outline-danger {
                display: none;
            }

            .container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 1rem;
                width: 100%;
            }

            .section {
                border: none;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container position-relative">
        <div class="header">
            <h4 class="font-bold text-lg">REPUBLIC OF THE PHILIPPINES</h4>
            <h4 class="font-bold text-lg">PROVINCE OF CAGAYAN • MUNICIPALITY OF BAGGAO</h4>
            <h1 class="font-extrabold text-3xl mt-2">EDIT TRAFFIC CITATION TICKET</h1>
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="ticket-number"><?php echo htmlspecialchars($data['ticket_number']); ?></div>
        </div>

        <form id="editCitationForm" action="update_citation.php" method="POST">
            <input type="hidden" name="citation_id" value="<?php echo $citation_id; ?>">

            <!-- Ticket Number -->
            <div class="section">
                <h5>Ticket Information</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ticket Number *</label>
                        <input type="text" name="ticket_number" class="form-control" value="<?php echo htmlspecialchars($data['ticket_number']); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Driver Information -->
            <div class="section">
                <h5>Driver Information</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($data['last_name']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($data['first_name']); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">M.I.</label>
                        <input type="text" name="middle_initial" class="form-control" value="<?php echo htmlspecialchars($data['middle_initial'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" class="form-control" value="<?php echo htmlspecialchars($data['suffix'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Zone</label>
                        <input type="text" name="zone" class="form-control" value="<?php echo htmlspecialchars($data['zone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barangay *</label>
                        <select name="barangay" class="form-select" id="barangaySelect" required>
                            <option value="" disabled <?php echo empty($data['barangay']) ? 'selected' : ''; ?>>Select Barangay</option>
                            <option value="Adag" <?php echo $data['barangay'] === 'Adag' ? 'selected' : ''; ?>>Adag</option>
                            <option value="Agaman" <?php echo $data['barangay'] === 'Agaman' ? 'selected' : ''; ?>>Agaman</option>
                            <option value="Agaman Norte" <?php echo $data['barangay'] === 'Agaman Norte' ? 'selected' : ''; ?>>Agaman Norte</option>
                            <option value="Agaman Sur" <?php echo $data['barangay'] === 'Agaman Sur' ? 'selected' : ''; ?>>Agaman Sur</option>
                            <option value="Alaguia" <?php echo $data['barangay'] === 'Alaguia' ? 'selected' : ''; ?>>Alaguia</option>
                            <option value="Alba" <?php echo $data['barangay'] === 'Alba' ? 'selected' : ''; ?>>Alba</option>
                            <option value="Annayatan" <?php echo $data['barangay'] === 'Annayatan' ? 'selected' : ''; ?>>Annayatan</option>
                            <option value="Asassi" <?php echo $data['barangay'] === 'Asassi' ? 'selected' : ''; ?>>Asassi</option>
                            <option value="Asinga-Via" <?php echo $data['barangay'] === 'Asinga-Via' ? 'selected' : ''; ?>>Asinga-Via</option>
                            <option value="Awallan" <?php echo $data['barangay'] === 'Awallan' ? 'selected' : ''; ?>>Awallan</option>
                            <option value="Bacagan" <?php echo $data['barangay'] === 'Bacagan' ? 'selected' : ''; ?>>Bacagan</option>
                            <option value="Bagunot" <?php echo $data['barangay'] === 'Bagunot' ? 'selected' : ''; ?>>Bagunot</option>
                            <option value="Barsat East" <?php echo $data['barangay'] === 'Barsat East' ? 'selected' : ''; ?>>Barsat East</option>
                            <option value="Barsat West" <?php echo $data['barangay'] === 'Barsat West' ? 'selected' : ''; ?>>Barsat West</option>
                            <option value="Bitag Grande" <?php echo $data['barangay'] === 'Bitag Grande' ? 'selected' : ''; ?>>Bitag Grande</option>
                            <option value="Bitag Pequeño" <?php echo $data['barangay'] === 'Bitag Pequeño' ? 'selected' : ''; ?>>Bitag Pequeño</option>
                            <option value="Bungel" <?php echo $data['barangay'] === 'Bungel' ? 'selected' : ''; ?>>Bungel</option>
                            <option value="Canagatan" <?php echo $data['barangay'] === 'Canagatan' ? 'selected' : ''; ?>>Canagatan</option>
                            <option value="Carupian" <?php echo $data['barangay'] === 'Carupian' ? 'selected' : ''; ?>>Carupian</option>
                            <option value="Catayauan" <?php echo $data['barangay'] === 'Catayauan' ? 'selected' : ''; ?>>Catayauan</option>
                            <option value="Dabburab" <?php echo $data['barangay'] === 'Dabburab' ? 'selected' : ''; ?>>Dabburab</option>
                            <option value="Dalin" <?php echo $data['barangay'] === 'Dalin' ? 'selected' : ''; ?>>Dalin</option>
                            <option value="Dallang" <?php echo $data['barangay'] === 'Dallang' ? 'selected' : ''; ?>>Dallang</option>
                            <option value="Furagui" <?php echo $data['barangay'] === 'Furagui' ? 'selected' : ''; ?>>Furagui</option>
                            <option value="Hacienda Intal" <?php echo $data['barangay'] === 'Hacienda Intal' ? 'selected' : ''; ?>>Hacienda Intal</option>
                            <option value="Immurung" <?php echo $data['barangay'] === 'Immurung' ? 'selected' : ''; ?>>Immurung</option>
                            <option value="Jomlo" <?php echo $data['barangay'] === 'Jomlo' ? 'selected' : ''; ?>>Jomlo</option>
                            <option value="Mabangguc" <?php echo $data['barangay'] === 'Mabangguc' ? 'selected' : ''; ?>>Mabangguc</option>
                            <option value="Masical" <?php echo $data['barangay'] === 'Masical' ? 'selected' : ''; ?>>Masical</option>
                            <option value="Mission" <?php echo $data['barangay'] === 'Mission' ? 'selected' : ''; ?>>Mission</option>
                            <option value="Mocag" <?php echo $data['barangay'] === 'Mocag' ? 'selected' : ''; ?>>Mocag</option>
                            <option value="Nangalinan" <?php echo $data['barangay'] === 'Nangalinan' ? 'selected' : ''; ?>>Nangalinan</option>
                            <option value="Pallagao" <?php echo $data['barangay'] === 'Pallagao' ? 'selected' : ''; ?>>Pallagao</option>
                            <option value="Paragat" <?php echo $data['barangay'] === 'Paragat' ? 'selected' : ''; ?>>Paragat</option>
                            <option value="Piggatan" <?php echo $data['barangay'] === 'Piggatan' ? 'selected' : ''; ?>>Piggatan</option>
                            <option value="Poblacion" <?php echo $data['barangay'] === 'Poblacion' ? 'selected' : ''; ?>>Poblacion</option>
                            <option value="Remus" <?php echo $data['barangay'] === 'Remus' ? 'selected' : ''; ?>>Remus</option>
                            <option value="San Antonio" <?php echo $data['barangay'] === 'San Antonio' ? 'selected' : ''; ?>>San Antonio</option>
                            <option value="San Francisco" <?php echo $data['barangay'] === 'San Francisco' ? 'selected' : ''; ?>>San Francisco</option>
                            <option value="San Isidro" <?php echo $data['barangay'] === 'San Isidro' ? 'selected' : ''; ?>>San Isidro</option>
                            <option value="San Jose" <?php echo $data['barangay'] === 'San Jose' ? 'selected' : ''; ?>>San Jose</option>
                            <option value="San Vicente" <?php echo $data['barangay'] === 'San Vicente' ? 'selected' : ''; ?>>San Vicente</option>
                            <option value="Santa Margarita" <?php echo $data['barangay'] === 'Santa Margarita' ? 'selected' : ''; ?>>Santa Margarita</option>
                            <option value="Santor" <?php echo $data['barangay'] === 'Santor' ? 'selected' : ''; ?>>Santor</option>
                            <option value="Taguing" <?php echo $data['barangay'] === 'Taguing' ? 'selected' : ''; ?>>Taguing</option>
                            <option value="Taguntungan" <?php echo $data['barangay'] === 'Taguntungan' ? 'selected' : ''; ?>>Taguntungan</option>
                            <option value="Tallang" <?php echo $data['barangay'] === 'Tallang' ? 'selected' : ''; ?>>Tallang</option>
                            <option value="Taytay" <?php echo $data['barangay'] === 'Taytay' ? 'selected' : ''; ?>>Taytay</option>
                            <option value="Other" <?php echo !in_array($data['barangay'], ['Adag', 'Agaman', 'Agaman Norte', 'Agaman Sur', 'Alaguia', 'Alba', 'Annayatan', 'Asassi', 'Asinga-Via', 'Awallan', 'Bacagan', 'Bagunot', 'Barsat East', 'Barsat West', 'Bitag Grande', 'Bitag Pequeño', 'Bungel', 'Canagatan', 'Carupian', 'Catayauan', 'Dabburab', 'Dalin', 'Dallang', 'Furagui', 'Hacienda Intal', 'Immurung', 'Jomlo', 'Mabangguc', 'Masical', 'Mission', 'Mocag', 'Nangalinan', 'Pallagao', 'Paragat', 'Piggatan', 'Poblacion', 'Remus', 'San Antonio', 'San Francisco', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Margarita', 'Santor', 'Taguing', 'Taguntungan', 'Tallang', 'Taytay']) ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <input type="text" name="other_barangay" class="form-control" id="otherBarangayInput" placeholder="Enter other barangay" value="<?php echo !in_array($data['barangay'], ['Adag', 'Agaman', 'Agaman Norte', 'Agaman Sur', 'Alaguia', 'Alba', 'Annayatan', 'Asassi', 'Asinga-Via', 'Awallan', 'Bacagan', 'Bagunot', 'Barsat East', 'Barsat West', 'Bitag Grande', 'Bitag Pequeño', 'Bungel', 'Canagatan', 'Carupian', 'Catayauan', 'Dabburab', 'Dalin', 'Dallang', 'Furagui', 'Hacienda Intal', 'Immurung', 'Jomlo', 'Mabangguc', 'Masical', 'Mission', 'Mocag', 'Nangalinan', 'Pallagao', 'Paragat', 'Piggatan', 'Poblacion', 'Remus', 'San Antonio', 'San Francisco', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Margarita', 'Santor', 'Taguing', 'Taguntungan', 'Tallang', 'Taytay']) ? htmlspecialchars($data['barangay']) : ''; ?>" <?php echo !in_array($data['barangay'], ['Adag', 'Agaman', 'Agaman Norte', 'Agaman Sur', 'Alaguia', 'Alba', 'Annayatan', 'Asassi', 'Asinga-Via', 'Awallan', 'Bacagan', 'Bagunot', 'Barsat East', 'Barsat West', 'Bitag Grande', 'Bitag Pequeño', 'Bungel', 'Canagatan', 'Carupian', 'Catayauan', 'Dabburab', 'Dalin', 'Dallang', 'Furagui', 'Hacienda Intal', 'Immurung', 'Jomlo', 'Mabangguc', 'Masical', 'Mission', 'Mocag', 'Nangalinan', 'Pallagao', 'Paragat', 'Piggatan', 'Poblacion', 'Remus', 'San Antonio', 'San Francisco', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Margarita', 'Santor', 'Taguing', 'Taguntungan', 'Tallang', 'Taytay']) ? 'required' : ''; ?>>
                    </div>
                    <div class="col-md-3" id="municipalityDiv" <?php echo !in_array($data['barangay'], ['Adag', 'Agaman', 'Agaman Norte', 'Agaman Sur', 'Alaguia', 'Alba', 'Annayatan', 'Asassi', 'Asinga-Via', 'Awallan', 'Bacagan', 'Bagunot', 'Barsat East', 'Barsat West', 'Bitag Grande', 'Bitag Pequeño', 'Bungel', 'Canagatan', 'Carupian', 'Catayauan', 'Dabburab', 'Dalin', 'Dallang', 'Furagui', 'Hacienda Intal', 'Immurung', 'Jomlo', 'Mabangguc', 'Masical', 'Mission', 'Mocag', 'Nangalinan', 'Pallagao', 'Paragat', 'Piggatan', 'Poblacion', 'Remus', 'San Antonio', 'San Francisco', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Margarita', 'Santor', 'Taguing', 'Taguntungan', 'Tallang', 'Taytay']) ? 'style="display: none;"' : ''; ?>>
                        <label class="form-label">Municipality</label>
                        <input type="text" name="municipality" class="form-control" value="<?php echo htmlspecialchars($data['municipality']); ?>" readonly>
                    </div>
                    <div class="col-md-3" id="provinceDiv" <?php echo !in_array($data['barangay'], ['Adag', 'Agaman', 'Agaman Norte', 'Agaman Sur', 'Alaguia', 'Alba', 'Annayatan', 'Asassi', 'Asinga-Via', 'Awallan', 'Bacagan', 'Bagunot', 'Barsat East', 'Barsat West', 'Bitag Grande', 'Bitag Pequeño', 'Bungel', 'Canagatan', 'Carupian', 'Catayauan', 'Dabburab', 'Dalin', 'Dallang', 'Furagui', 'Hacienda Intal', 'Immurung', 'Jomlo', 'Mabangguc', 'Masical', 'Mission', 'Mocag', 'Nangalinan', 'Pallagao', 'Paragat', 'Piggatan', 'Poblacion', 'Remus', 'San Antonio', 'San Francisco', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Margarita', 'Santor', 'Taguing', 'Taguntungan', 'Tallang', 'Taytay']) ? 'style="display: none;"' : ''; ?>>
                        <label class="form-label">Province</label>
                        <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($data['province']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($data['license_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block">License Type</label>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="license_type" value="nonProf" id="nonProf" <?php echo $data['license_type'] === 'Non-Professional' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="nonProf">Non-Prof</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block"> </label>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="license_type" value="prof" id="prof" <?php echo $data['license_type'] === 'Professional' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="prof">Prof</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Information -->
            <div class="section">
                <h5>Vehicle Information</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Plate / MV File / Engine / Chassis No.</label>
                        <input type="text" name="plate_mv_engine_chassis_no" class="form-control" value="<?php echo htmlspecialchars($data['plate_mv_engine_chassis_no']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Vehicle Type</label>
                        <div class="d-flex flex-wrap gap-3 mt-1">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="motorcycle" id="motorcycle" <?php echo $vehicle_type === 'motorcycle' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="motorcycle">Motorcycle</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="tricycle" id="tricycle" <?php echo $vehicle_type === 'tricycle' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="tricycle">Tricycle</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="suv" id="suv" <?php echo $vehicle_type === 'suv' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="suv">SUV</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="van" id="van" <?php echo $vehicle_type === 'van' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="van">Van</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="jeep" id="jeep" <?php echo $vehicle_type === 'jeep' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="jeep">Jeep</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="truck" id="truck" <?php echo $vehicle_type === 'truck' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="truck">Truck</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="kulong" id="kulong" <?php echo $vehicle_type === 'kulong' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="kulong">Kulong Kulong</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="othersVehicle" id="othersVehicle" <?php echo !in_array($vehicle_type, ['motorcycle', 'tricycle', 'suv', 'van', 'jeep', 'truck', 'kulong']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="othersVehicle">Others</label>
                                <input type="text" name="other_vehicle_input" class="form-control" id="otherVehicleInput" value="<?php echo !in_array($vehicle_type, ['motorcycle', 'tricycle', 'suv', 'van', 'jeep', 'truck', 'kulong']) ? htmlspecialchars($data['vehicle_type']) : ''; ?>" placeholder="Specify other vehicle type">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Vehicle Description</label>
                        <input type="text" name="vehicle_description" class="form-control" value="<?php echo htmlspecialchars($data['vehicle_description'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Apprehension Date & Time</label>
                        <div class="input-group">
                            <input type="datetime-local" name="apprehension_datetime" class="form-control" value="<?php echo $apprehension_datetime; ?>" required>
                            <button class="btn btn-outline-secondary btn-custom" type="button" id="toggleDateTime" title="Set/Clear">📅</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Place of Apprehension</label>
                        <input type="text" name="place_of_apprehension" class="form-control" value="<?php echo htmlspecialchars($data['place_of_apprehension']); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Violations -->
            <div class="section">
                <h5 class="text-red-600">Violation(s)</h5>
                <div class="row violation-list">
                    <div class="col-md-12">
                        <?php
                        $other_violations = array_diff($violations, $all_violation_types);

                        foreach ($categories as $category => $category_violations) {
                            if (empty($category_violations)) continue; // Skip empty categories
                            echo "<div class='violation-category'><h6>$category</h6>";
                            foreach ($category_violations as $violation) {
                                $key = htmlspecialchars(strtolower(str_replace([' ', '/', '(', ')', '&'], '', $violation)));
                                echo "<div class='form-check'>";
                                echo "<input type='checkbox' class='form-check-input' name='$key' id='$key' " . (in_array($violation, $violations) ? 'checked' : '') . ">";
                                echo "<label class='form-check-label' for='$key'>" . htmlspecialchars($violation) . "</label>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                        ?>
                        <div class="violation-category">
                            <h6>Custom Violations</h6>
                            <?php foreach ($other_violations as $index => $other_violation): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="otherViolation_<?php echo $index; ?>" id="otherViolation_<?php echo $index; ?>" checked>
                                    <label class="form-check-label" for="otherViolation_<?php echo $index; ?>">Custom Violation</label>
                                    <input type="text" name="other_violation_input_<?php echo $index; ?>" class="form-control otherViolationInput" value="<?php echo htmlspecialchars($other_violation); ?>" placeholder="Specify custom violation" style="display: block;">
                                </div>
                            <?php endforeach; ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="otherViolation_new" id="otherViolation_new">
                                <label class="form-check-label" for="otherViolation_new">Add New Custom Violation</label>
                                <input type="text" name="other_violation_input_new" class="form-control" id="otherViolationInput_new" placeholder="Specify new custom violation">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3 remarks">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter additional remarks"><?php echo htmlspecialchars($data['remark_text'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-custom" id="updateButton">Update Citation</button>
                <a href="fetch_citations.php" class="btn btn-outline-secondary btn-custom">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('editCitationForm');
            const csrfTokenInput = document.getElementById('csrfToken');
            const barangaySelect = document.getElementById('barangaySelect');
            const otherBarangayInput = document.getElementById('otherBarangayInput');
            const municipalityDiv = document.getElementById('municipalityDiv');
            const provinceDiv = document.getElementById('provinceDiv');
            const toggleBtn = document.getElementById('toggleDateTime');
            const dateTimeInput = form.querySelector('input[name="apprehension_datetime"]');
            let isAutoFilled = <?php echo $apprehension_datetime ? 'true' : 'false'; ?>;

            // Toggle DateTime button
            toggleBtn.addEventListener('click', () => {
                if (!isAutoFilled) {
                    const now = new Date();
                    const offset = now.getTimezoneOffset();
                    now.setMinutes(now.getMinutes() - offset);
                    dateTimeInput.value = now.toISOString().slice(0, 16);
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

            // Handle Other Violation checkboxes
            document.querySelectorAll('input[name^="otherViolation_"]').forEach(checkbox => {
                const input = checkbox.nextElementSibling.nextElementSibling;
                checkbox.addEventListener('change', () => {
                    input.style.display = checkbox.checked ? 'block' : 'none';
                    input.required = checkbox.checked;
                });
            });

            const newOtherViolationCheckbox = document.getElementById('otherViolation_new');
            const newOtherViolationInput = document.getElementById('otherViolationInput_new');
            newOtherViolationCheckbox.addEventListener('change', () => {
                newOtherViolationInput.style.display = newOtherViolationCheckbox.checked ? 'block' : 'none';
                newOtherViolationInput.required = newOtherViolationCheckbox.checked;
            });

            // Form submission
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                formData.append('csrf_token', csrfTokenInput.value);

                fetch('update_citation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        window.location.href = 'fetch_citations.php';
                    } else if (data.new_csrf_token) {
                        csrfTokenInput.value = data.new_csrf_token;
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Error updating citation: ' + error.message);
                });
            });

            // Real-time form validation
            const requiredInputs = form.querySelectorAll('input[required], select[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('input', () => {
                    if (input.value.trim() === '') {
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            });

            // Ensure only one vehicle type is selected
            const vehicleCheckboxes = document.querySelectorAll('input[name="motorcycle"], input[name="tricycle"], input[name="suv"], input[name="van"], input[name="jeep"], input[name="truck"], input[name="kulong"], input[name="othersVehicle"]');
            vehicleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        vehicleCheckboxes.forEach(otherCheckbox => {
                            if (otherCheckbox !== checkbox) {
                                otherCheckbox.checked = false;
                            }
                        });
                        if (checkbox.id === 'othersVehicle') {
                            document.getElementById('otherVehicleInput').style.display = 'block';
                            document.getElementById('otherVehicleInput').required = true;
                        } else {
                            document.getElementById('otherVehicleInput').style.display = 'none';
                            document.getElementById('otherVehicleInput').required = false;
                            document.getElementById('otherVehicleInput').value = '';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>