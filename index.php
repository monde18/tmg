<?php
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the highest ticket number
    $stmt = $conn->query("SELECT ticket_number FROM citations ORDER BY CAST(ticket_number AS UNSIGNED) DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if $row is an array and has a ticket number
    if ($row && !empty($row['ticket_number'])) {
        $max_ticket = (int)preg_replace('/[^0-9]/', '', $row['ticket_number']);
    } else {
        $max_ticket = 6100; // Default starting ticket number
    }
    $next_ticket = sprintf("%05d", $max_ticket + 1);

    // Ensure unique ticket number
    $stmt = $conn->prepare("SELECT COUNT(*) FROM citations WHERE ticket_number = :ticket_number");
    $stmt->execute([':ticket_number' => $next_ticket]);
    while ($stmt->fetchColumn() > 0) {
        $max_ticket++;
        $next_ticket = sprintf("%05d", $max_ticket + 1);
        $stmt->execute([':ticket_number' => $next_ticket]);
    }

    // Pre-fill driver info if driver_id is provided
    $driver_data = [];
    $offense_counts = [];
    if (isset($_GET['driver_id'])) {
        $driver_id = (int)$_GET['driver_id'];
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_id = :driver_id");
        $stmt->execute([':driver_id' => $driver_id]);
        $driver_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch offense counts for this driver
        $stmt = $conn->prepare("
            SELECT vt.violation_type, MAX(v.offense_count) AS offense_count
            FROM violations v
            JOIN violation_types vt ON v.violation_type = vt.violation_type
            WHERE v.driver_id = :driver_id
            GROUP BY vt.violation_type
        ");
        $stmt->execute([':driver_id' => $driver_id]);
        $offense_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Cache violation types
    if (!isset($_SESSION['violation_types'])) {
        $stmt = $conn->query("SELECT violation_type_id, violation_type, fine_amount_1, fine_amount_2, fine_amount_3 FROM violation_types ORDER BY violation_type");
        $_SESSION['violation_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $violation_types = $_SESSION['violation_types'];
} catch (PDOException $e) {
    $next_ticket = "06101";
    $driver_data = [];
    $violation_types = [];
    error_log("PDOException in index.php: " . $e->getMessage());
}
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Traffic Citation Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
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
            overflow: hidden;
            height: 100vh;
            display: flex;
        }

        /* Sidebar Styles (from sidebar.php) */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e3a8a 0%, #2b5dc9 70%, #3b82f6 100%);
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 20px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h4 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #facc15;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: opacity 0.3s ease, transform 0.3s ease;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h4 {
            opacity: 0;
            transform: translateX(-20px);
        }

        .sidebar-toggle {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border: none;
            border-radius: 0 10px 10px 0;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 3px 3px 8px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease;
            display: none;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            color: #fff;
            padding: 14px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
            z-index: 0;
        }

        .sidebar-nav a:hover::before {
            left: 0;
        }

        .sidebar-nav a i {
            margin-right: 15px;
            width: 22px;
            text-align: center;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .sidebar-nav a:hover i {
            transform: translateX(5px);
        }

        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-nav a.active {
            background: #2563eb;
            font-weight: 600;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar-nav a.active::before {
            display: none;
        }

        .sidebar.collapsed .sidebar-nav a span {
            display: none;
        }

        .sidebar.collapsed .sidebar-nav a {
            justify-content: center;
            padding: 14px;
        }

        .sidebar.collapsed .sidebar-nav a i {
            margin-right: 0;
            transform: scale(1.2);
        }

        .logout-link {
            position: absolute;
            bottom: 25px;
            width: calc(100% - 40px);
        }

        .logout-link a {
            display: flex;
            align-items: center;
            color: #ff4444;
            padding: 14px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease-in-out;
            background: rgba(255, 68, 68, 0.1);
        }

        .logout-link a:hover {
            background: rgba(255, 68, 68, 0.2);
            transform: translateX(5px);
        }

        .sidebar.collapsed .logout-link a {
            justify-content: center;
            padding: 14px;
        }

        .sidebar.collapsed .logout-link a span {
            display: none;
        }

        /* Content and Form Styles */
        .content {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            height: 100vh;
            margin-left: 260px;
            transition: margin-left 0.3s ease-in-out;
        }

        .content.collapsed {
            margin-left: 80px;
        }

        .ticket-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 4rem); /* Adjust for padding */
            overflow-y: auto;
            width: 100%;
        }

        .header {
            background: linear-gradient(90deg, var(--primary), #3b82f6);
            color: white;
            padding: 2rem;
            border-radius: 12px 12px 0 0;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
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
            top: 1.5rem;
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
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .section h5 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-size: 1.25rem;
            border-bottom: 2px solid var(--border);
            padding-bottom: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border);
            padding: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .form-control.is-invalid {
            border-color: var(--danger);
            background-color: #fef2f2;
        }

        .accordion-button {
            font-weight: 600;
            color: var(--primary);
            background-color: #f1f5f9;
            border: none;
            border-radius: 8px !important;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
        }

        .accordion-button:not(.collapsed) {
            color: var(--primary-dark);
            background-color: #e2e8f0;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .violation-list .form-check {
            margin-bottom: 0.75rem;
            padding-left: 2rem;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .remarks textarea {
            resize: vertical;
            min-height: 100px;
            font-size: 0.95rem;
        }

        .footer {
            font-size: 0.9rem;
            color: var(--secondary);
            padding: 1.5rem 0;
            border-top: 1px solid var(--border);
            text-align: justify;
        }

        .btn-custom {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline-secondary, .btn-outline-danger {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        #otherViolationInput, #otherVehicleInput, #otherBarangayInput {
            display: none;
            margin-top: 0.75rem;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar.collapsed {
                width: 260px;
            }
            .sidebar-header h4 {
                opacity: 1;
                transform: none;
            }
            .sidebar-nav a span {
                display: inline;
            }
            .sidebar-nav a {
                justify-content: flex-start;
                padding: 14px 18px;
            }
            .sidebar-nav a i {
                margin-right: 15px;
                transform: none;
            }
            .logout-link a span {
                display: inline;
            }
            .sidebar-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 10px;
                z-index: 1100;
            }
            .sidebar.open ~ .content .sidebar-toggle {
                left: 270px;
            }
            .content {
                margin-left: 0;
            }
            .content.collapsed {
                margin-left: 0;
            }
            .ticket-container {
                margin-left: 0;
                padding: 1.5rem;
                height: calc(100vh - 3rem);
            }
        }

        @media (max-width: 480px) {
            .ticket-container {
                padding: 1rem;
            }
            .header h1 {
                font-size: 1.5rem;
            }
            .form-label {
                font-size: 0.85rem;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 0.4rem;
            }
            .btn-custom {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        @media print {
            .sidebar, .ticket-number, .btn-custom, .btn-outline-secondary, .btn-outline-danger {
                display: none;
            }
            .content {
                margin-left: 0;
            }
            .ticket-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 1rem;
                width: 100%;
                height: auto;
            }
            .section {
                border: none;
                padding: 1rem;
            }
            .accordion-button::after {
                display: none;
            }
            .accordion-collapse {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4>Traffic System</h4>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" aria-label="Go to Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'records.php' ? 'active' : ''; ?>" aria-label="Go to Traffic Citations">
                    <i class="fas fa-file-alt"></i>
                    <span>Traffic Citations</span>
                </a>
            </li>
            <li>
                <a href="driver_records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'driver_records.php' ? 'active' : ''; ?>" aria-label="Go to Driver Records">
                    <i class="fas fa-users"></i>
                    <span>Driver Records</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" aria-label="Go to Reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="treasury_payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'treasury_payments.php' ? 'active' : ''; ?>" aria-label="Go to Treasury Payments">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Treasury Payments</span>
                </a>
            </li>
            <li>
                <a href="manage_violations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_violations.php' ? 'active' : ''; ?>" aria-label="Go to Manage Violations">
                    <i class="fas fa-gavel"></i>
                    <span>Manage Violations</span>
                </a>
            </li>
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" aria-label="Go to Add Citation">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Citation</span>
                </a>
            </li>
        </ul>
        <div class="logout-link">
            <a href="logout.php" aria-label="Logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <form id="citationForm" action="insert_citation.php" method="POST">
            <div class="ticket-container">
                <div class="header">
                    <h4 class="font-bold text-lg">REPUBLIC OF THE PHILIPPINES</h4>
                    <h4 class="font-bold text-lg">PROVINCE OF CAGAYAN • MUNICIPALITY OF BAGGAO</h4>
                    <h1 class="font-extrabold text-3xl mt-2">TRAFFIC CITATION TICKET</h1>
                    <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($next_ticket); ?>">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="ticket-number"><?php echo htmlspecialchars($next_ticket); ?></div>
                </div>

                <!-- Driver Info -->
                <div class="section">
                    <h5>Driver Information</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Enter last name" value="<?php echo htmlspecialchars($driver_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Enter first name" value="<?php echo htmlspecialchars($driver_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">M.I.</label>
                            <input type="text" name="middle_initial" class="form-control" placeholder="M.I." value="<?php echo htmlspecialchars($driver_data['middle_initial'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffix" class="form-control" placeholder="e.g., Jr." value="<?php echo htmlspecialchars($driver_data['suffix'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Zone</label>
                            <input type="text" name="zone" class="form-control" placeholder="Enter zone" value="<?php echo htmlspecialchars($driver_data['zone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Barangay *</label>
                            <select name="barangay" class="form-select" id="barangaySelect" required>
                                <option value="" disabled <?php echo (!isset($driver_data['barangay']) || $driver_data['barangay'] == '') ? 'selected' : ''; ?>>Select Barangay</option>
                                <option value="Adag" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Adag') ? 'selected' : ''; ?>>Adag</option>
                                <option value="Agaman" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman') ? 'selected' : ''; ?>>Agaman</option>
                                <option value="Agaman Norte" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman Norte') ? 'selected' : ''; ?>>Agaman Norte</option>
                                <option value="Agaman Sur" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Agaman Sur') ? 'selected' : ''; ?>>Agaman Sur</option>
                                <option value="Alaguia" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Alaguia') ? 'selected' : ''; ?>>Alaguia</option>
                                <option value="Alba" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Alba') ? 'selected' : ''; ?>>Alba</option>
                                <option value="Annayatan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Annayatan') ? 'selected' : ''; ?>>Annayatan</option>
                                <option value="Asassi" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Asassi') ? 'selected' : ''; ?>>Asassi</option>
                                <option value="Asinga-Via" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Asinga-Via') ? 'selected' : ''; ?>>Asinga-Via</option>
                                <option value="Awallan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Awallan') ? 'selected' : ''; ?>>Awallan</option>
                                <option value="Bacagan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bacagan') ? 'selected' : ''; ?>>Bacagan</option>
                                <option value="Bagunot" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bagunot') ? 'selected' : ''; ?>>Bagunot</option>
                                <option value="Barsat East" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Barsat East') ? 'selected' : ''; ?>>Barsat East</option>
                                <option value="Barsat West" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Barsat West') ? 'selected' : ''; ?>>Barsat West</option>
                                <option value="Bitag Grande" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bitag Grande') ? 'selected' : ''; ?>>Bitag Grande</option>
                                <option value="Bitag Pequeño" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bitag Pequeño') ? 'selected' : ''; ?>>Bitag Pequeño</option>
                                <option value="Bungel" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Bungel') ? 'selected' : ''; ?>>Bungel</option>
                                <option value="Canagatan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Canagatan') ? 'selected' : ''; ?>>Canagatan</option>
                                <option value="Carupian" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Carupian') ? 'selected' : ''; ?>>Carupian</option>
                                <option value="Catayauan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Catayauan') ? 'selected' : ''; ?>>Catayauan</option>
                                <option value="Dabburab" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Dabburab') ? 'selected' : ''; ?>>Dabburab</option>
                                <option value="Dalin" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Dalin') ? 'selected' : ''; ?>>Dalin</option>
                                <option value="Dallang" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Dallang') ? 'selected' : ''; ?>>Dallang</option>
                                <option value="Furagui" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Furagui') ? 'selected' : ''; ?>>Furagui</option>
                                <option value="Hacienda Intal" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Hacienda Intal') ? 'selected' : ''; ?>>Hacienda Intal</option>
                                <option value="Immurung" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Immurung') ? 'selected' : ''; ?>>Immurung</option>
                                <option value="Jomlo" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Jomlo') ? 'selected' : ''; ?>>Jomlo</option>
                                <option value="Mabangguc" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Mabangguc') ? 'selected' : ''; ?>>Mabangguc</option>
                                <option value="Masical" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Masical') ? 'selected' : ''; ?>>Masical</option>
                                <option value="Mission" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Mission') ? 'selected' : ''; ?>>Mission</option>
                                <option value="Mocag" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Mocag') ? 'selected' : ''; ?>>Mocag</option>
                                <option value="Nangalinan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Nangalinan') ? 'selected' : ''; ?>>Nangalinan</option>
                                <option value="Pallagao" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Pallagao') ? 'selected' : ''; ?>>Pallagao</option>
                                <option value="Paragat" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Paragat') ? 'selected' : ''; ?>>Paragat</option>
                                <option value="Piggatan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Piggatan') ? 'selected' : ''; ?>>Piggatan</option>
                                <option value="Poblacion" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Poblacion') ? 'selected' : ''; ?>>Poblacion</option>
                                <option value="Remus" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Remus') ? 'selected' : ''; ?>>Remus</option>
                                <option value="San Antonio" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Antonio') ? 'selected' : ''; ?>>San Antonio</option>
                                <option value="San Francisco" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Francisco') ? 'selected' : ''; ?>>San Francisco</option>
                                <option value="San Isidro" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Isidro') ? 'selected' : ''; ?>>San Isidro</option>
                                <option value="San Jose" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Jose') ? 'selected' : ''; ?>>San Jose</option>
                                <option value="San Vicente" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'San Vicente') ? 'selected' : ''; ?>>San Vicente</option>
                                <option value="Santa Margarita" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Santa Margarita') ? 'selected' : ''; ?>>Santa Margarita</option>
                                <option value="Santor" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Santor') ? 'selected' : ''; ?>>Santor</option>
                                <option value="Taguing" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taguing') ? 'selected' : ''; ?>>Taguing</option>
                                <option value="Taguntungan" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taguntungan') ? 'selected' : ''; ?>>Taguntungan</option>
                                <option value="Tallang" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Tallang') ? 'selected' : ''; ?>>Tallang</option>
                                <option value="Taytay" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Taytay') ? 'selected' : ''; ?>>Taytay</option>
                                <option value="Other" <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <input type="text" name="other_barangay" class="form-control" id="otherBarangayInput" placeholder="Enter other barangay" value="<?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Other') ? htmlspecialchars($driver_data['barangay']) : ''; ?>">
                        </div>
                        <div class="col-md-3" id="municipalityDiv" style="display: none;">
                            <label class="form-label">Municipality</label>
                            <input type="text" name="municipality" class="form-control" id="municipalityInput" value="<?php echo htmlspecialchars($driver_data['municipality'] ?? 'Baggao'); ?>" readonly>
                        </div>
                        <div class="col-md-3" id="provinceDiv" style="display: none;">
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
                            <label class="form-label">License Number *</label>
                            <input type="text" name="license_number" class="form-control" placeholder="Enter license number" value="<?php echo htmlspecialchars($driver_data['license_number'] ?? ''); ?>" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                        </div>
                        <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                            <label class="form-label d-block">License Type *</label>
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
                            <label class="form-label">Plate / MV File / Engine / Chassis No. *</label>
                            <input type="text" name="plate_mv_engine_chassis_no" class="form-control" placeholder="Enter plate or other number" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Vehicle Type *</label>
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="motorcycle" id="motorcycle">
                                    <label class="form-check-label" for="motorcycle">Motorcycle</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="tricycle" id="tricycle">
                                    <label class="form-check-label" for="tricycle">Tricycle</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="suv" id="suv">
                                    <label class="form-check-label" for="suv">SUV</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="van" id="van">
                                    <label class="form-check-label" for="van">Van</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="jeep" id="jeep">
                                    <label class="form-check-label" for="jeep">Jeep</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="truck" id="truck">
                                    <label class="form-check-label" for="truck">Truck</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="kulong" id="kulong">
                                    <label class="form-check-label" for="kulong">Kulong Kulong</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="othersVehicle" id="othersVehicle">
                                    <label class="form-check-label" for="othersVehicle">Others</label>
                                </div>
                            </div>
                            <input type="text" name="other_vehicle_input" class="form-control" id="otherVehicleInput" placeholder="Specify other vehicle type">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Vehicle Description</label>
                            <input type="text" name="vehicle_description" class="form-control" placeholder="Brand, Model, CC, Color, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Apprehension Date & Time *</label>
                            <div class="input-group">
                                <input type="datetime-local" name="apprehension_datetime" class="form-control" id="apprehensionDateTime" required>
                                <button class="btn btn-outline-secondary btn-custom" type="button" id="toggleDateTime" title="Set/Clear">📅</button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Place of Apprehension *</label>
                            <input type="text" name="place_of_apprehension" class="form-control" placeholder="Enter place of apprehension" required>
                        </div>
                    </div>
                </div>

                <!-- Violations (Accordion) -->
                <div class="section">
                    <h5 class="text-red-600">Violation(s) *</h5>
                    <div class="accordion violation-list" id="violationsAccordion">
                        <?php
                        $categories = [
                            'Helmet Violations' => ['NO HELMET (Driver)', 'NO HELMET (Backrider)'],
                            'License / Registration' => ['NO DRIVER’S LICENSE / MINOR', 'NO / EXPIRED VEHICLE REGISTRATION', 'NO ENHANCED OPLAN VISA STICKER', 'FAILURE TO PRESENT E-OV MATCH CARD'],
                            'Vehicle Condition' => ['NO / DEFECTIVE PARTS & ACCESSORIES', 'NOISY MUFFLER (98db above)', 'NO MUFFLER ATTACHED', 'ILLEGAL MODIFICATION'],
                            'Reckless / Improper Driving' => ['RECKLESS / ARROGANT DRIVING', 'DRAG RACING', 'DRUNK DRIVING', 'DRIVING IN SHORT / SANDO'],
                            'Traffic Rules' => ['DISREGARDING TRAFFIC SIGN', 'PASSENGER ON TOP OF THE VEHICLE', 'ILLEGAL PARKING', 'ROAD OBSTRUCTION', 'BLOCKING PEDESTRIAN LANE', 'LOADING/UNLOADING IN PROHIBITED ZONE', 'DOUBLE PARKING'],
                            'Miscellaneous' => ['COLORUM OPERATION', 'NO TRASHBIN', 'OVERLOADED PASSENGER', 'OVER CHARGING / UNDER CHARGING', 'REFUSAL TO CONVEY PASSENGER/S']
                        ];

                        foreach ($categories as $category => $category_violations) {
                            $category_id = htmlspecialchars(strtolower(str_replace([' ', '/', '(', ')'], '', $category)));
                            echo "<div class='accordion-item'>";
                            echo "<h2 class='accordion-header' id='heading-$category_id'>";
                            echo "<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-$category_id' aria-expanded='false' aria-controls='collapse-$category_id'>$category</button>";
                            echo "</h2>";
                            echo "<div id='collapse-$category_id' class='accordion-collapse collapse' aria-labelledby='heading-$category_id' data-bs-parent='#violationsAccordion'>";
                            echo "<div class='accordion-body p-4'>";
                            foreach ($violation_types as $v) {
                                if (in_array($v['violation_type'], $category_violations)) {
                                    $offense_count = isset($offense_counts[$v['violation_type']]) ? (int)$offense_counts[$v['violation_type']] + 1 : 1;
                                    $label = $v['violation_type'] . ($offense_count > 1 ? " - {$offense_count}" . ($offense_count == 2 ? "nd" : ($offense_count == 3 ? "rd" : "th")) . " Offense" : "") . " (₱" . number_format($v["fine_amount_$offense_count"], 2) . ")";
                                    $key = htmlspecialchars(strtolower(str_replace([' ', '/', '(', ')'], '', $v['violation_type'])));
                                    echo "<div class='form-check mb-2'>";
                                    echo "<input type='checkbox' class='form-check-input' name='violations[]' value='" . htmlspecialchars($v['violation_type']) . "' id='$key'>";
                                    echo "<label class='form-check-label' for='$key'>" . htmlspecialchars($label) . "</label>";
                                    echo "</div>";
                                }
                            }
                            echo "</div></div></div>";
                        }
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-other">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-other" aria-expanded="false" aria-controls="collapse-other">
                                    Other
                                </button>
                            </h2>
                            <div id="collapse-other" class="accordion-collapse collapse" aria-labelledby="heading-other" data-bs-parent="#violationsAccordion">
                                <div class="accordion-body p-4">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="other_violation" id="other_violation">
                                        <label class="form-check-label" for="other_violation">Other Violation</label>
                                    </div>
                                    <input type="text" name="other_violation_input" class="form-control" id="otherViolationInput" placeholder="Specify other violation">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 remarks">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Enter additional remarks"></textarea>
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

                <!-- Submit Button -->
                <button type="submit" class="btn btn-custom mt-4">Submit Citation</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const content = document.querySelector('.content');
            const csrfTokenInput = document.getElementById('csrfToken');
            const otherViolationCheckbox = document.getElementById('other_violation');
            const otherViolationInput = document.getElementById('otherViolationInput');
            const otherVehicleCheckbox = document.getElementById('othersVehicle');
            const otherVehicleInput = document.getElementById('otherVehicleInput');
            const hasLicenseCheckbox = document.getElementById('hasLicense');
            const licenseFields = document.querySelectorAll('.license-field');
            const barangaySelect = document.getElementById('barangaySelect');
            const otherBarangayInput = document.getElementById('otherBarangayInput');
            const municipalityDiv = document.getElementById('municipalityDiv');
            const provinceDiv = document.getElementById('provinceDiv');

            // Sidebar toggle
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('collapsed');
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('open');
                    sidebarToggle.style.left = sidebar.classList.contains('open') ? '270px' : '10px';
                }
            });

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

            // Auto-populate Municipality and Province
            barangaySelect.addEventListener('change', () => {
                const isOther = barangaySelect.value === 'Other';
                otherBarangayInput.style.display = isOther ? 'block' : 'none';
                otherBarangayInput.required = isOther;
                if (isOther) {
                    municipalityDiv.style.display = 'none';
                    provinceDiv.style.display = 'none';
                    municipalityDiv.querySelector('input').value = '';
                    provinceDiv.querySelector('input').value = '';
                } else if (barangaySelect.value) {
                    municipalityDiv.style.display = 'block';
                    provinceDiv.style.display = 'block';
                    municipalityDiv.querySelector('input').value = 'Baggao';
                    provinceDiv.querySelector('input').value = 'Cagayan';
                } else {
                    municipalityDiv.style.display = 'none';
                    provinceDiv.style.display = 'none';
                    municipalityDiv.querySelector('input').value = '';
                    provinceDiv.querySelector('input').value = '';
                }
                if (!isOther && otherBarangayInput.value) otherBarangayInput.value = '';
            });

            // Toggle DateTime button
            const toggleBtn = document.getElementById('toggleDateTime');
            const dateTimeInput = document.getElementById('apprehensionDateTime');
            let isAutoFilled = false;
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

            // Show/hide Other Violation input
            otherViolationCheckbox.addEventListener('change', () => {
                otherViolationInput.style.display = otherViolationCheckbox.checked ? 'block' : 'none';
                otherViolationInput.required = otherViolationCheckbox.checked;
                if (!otherViolationCheckbox.checked) {
                    otherViolationInput.value = '';
                }
            });

            // Show/hide Other Vehicle input
            otherVehicleCheckbox.addEventListener('change', () => {
                otherVehicleInput.style.display = otherVehicleCheckbox.checked ? 'block' : 'none';
                otherVehicleInput.required = otherVehicleCheckbox.checked;
                if (!otherVehicleCheckbox.checked) {
                    otherVehicleInput.value = '';
                }
            });

            // Ensure only one license type
            const nonProfCheckbox = document.getElementById('nonProf');
            const profCheckbox = document.getElementById('prof');
            nonProfCheckbox.addEventListener('change', () => {
                if (nonProfCheckbox.checked) profCheckbox.checked = false;
            });
            profCheckbox.addEventListener('change', () => {
                if (profCheckbox.checked) nonProfCheckbox.checked = false;
            });

            // Form validation and submission
            const vehicleCheckboxes = document.querySelectorAll('input[name="motorcycle"], input[name="tricycle"], input[name="suv"], input[name="van"], input[name="jeep"], input[name="truck"], input[name="kulong"], input[name="othersVehicle"]');
            const violationCheckboxes = document.querySelectorAll('input[name="violations[]"], input[name="other_violation"]');
            document.getElementById('citationForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate vehicle type
                let vehicleSelected = false;
                vehicleCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) vehicleSelected = true;
                });
                if (!vehicleSelected) {
                    alert('Please select at least one vehicle type.');
                    return;
                }

                // Validate violations
                let violationSelected = false;
                const selectedViolations = [];
                violationCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        violationSelected = true;
                        if (checkbox.name === 'violations[]') {
                            selectedViolations.push(checkbox.value);
                        } else if (checkbox.name === 'other_violation' && otherViolationInput.value.trim()) {
                            selectedViolations.push(otherViolationInput.value.trim());
                        }
                    }
                });
                if (!violationSelected) {
                    alert('Please select at least one violation.');
                    return;
                }

                const formData = new FormData(this);
                formData.append('csrf_token', csrfTokenInput.value);

                fetch('insert_citation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        document.getElementById('citationForm').reset();
                        municipalityDiv.querySelector('input').value = 'Baggao';
                        provinceDiv.querySelector('input').value = 'Cagayan';
                        otherViolationInput.style.display = 'none';
                        otherVehicleInput.style.display = 'none';
                        otherBarangayInput.style.display = 'none';
                        otherViolationInput.required = false;
                        otherVehicleInput.required = false;
                        otherBarangayInput.required = false;
                        hasLicenseCheckbox.checked = false;
                        licenseFields.forEach(field => {
                            field.style.display = 'none';
                            field.querySelectorAll('input').forEach(input => {
                                input.value = '';
                                if (input.type === 'radio') input.checked = false;
                                input.required = false;
                            });
                        });
                        isAutoFilled = false;
                        toggleBtn.innerText = '📅';
                        toggleBtn.classList.remove('btn-outline-danger');
                        toggleBtn.classList.add('btn-outline-secondary');
                        if (data.new_csrf_token) {
                            csrfTokenInput.value = data.new_csrf_token;
                        }
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Error submitting form: ' + error);
                });
            });

            // Real-time form validation
            const requiredInputs = document.querySelectorAll('input[required], select[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('input', () => {
                    if (input.value.trim() === '') {
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            });
        });
    </script>
</body>
</html>