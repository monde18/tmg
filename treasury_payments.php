<?php
// treasury_payments.php
// Frontend for managing traffic citation payments, displaying summaries, and handling payments
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $summaryQuery = "
        SELECT 
            COUNT(DISTINCT c.ticket_number) AS total_citations,
            SUM(CASE WHEN c.payment_status = 'Paid' THEN 1 ELSE 0 END) AS paid_citations,
            SUM(CASE WHEN c.payment_status = 'Unpaid' THEN 1 ELSE 0 END) AS unpaid_citations,
            SUM(CASE WHEN c.payment_status = 'Partially Paid' THEN 1 ELSE 0 END) AS partially_paid_citations,
            COALESCE(SUM(
                COALESCE(
                    CASE vl.offense_count
                        WHEN 1 THEN vt.fine_amount_1
                        WHEN 2 THEN vt.fine_amount_2
                        WHEN 3 THEN vt.fine_amount_3
                        ELSE 500.00
                    END, 500.00
                ) 
            ), 0) AS total_fines,
            COALESCE(SUM(CASE WHEN c.payment_status = 'Paid' THEN c.payment_amount ELSE 0 END), 0) AS total_paid
        FROM citations c
        LEFT JOIN violations vl ON c.citation_id = vl.citation_id
        LEFT JOIN violation_types vt ON UPPER(vl.violation_type) = UPPER(vt.violation_type)
        WHERE c.is_archived = 0
    ";
    $summaryStmt = $conn->prepare($summaryQuery);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    error_log("Summary Query: " . $summaryQuery);
    error_log("Summary Result: " . print_r($summary, true));
} catch (PDOException $e) {
    $summary = ['total_citations' => 0, 'paid_citations' => 0, 'unpaid_citations' => 0, 'partially_paid_citations' => 0, 'total_fines' => 0, 'total_paid' => 0];
    error_log("Summary PDOException: " . $e->getMessage());
}
$conn = null;

$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$recordsPerPage = filter_input(INPUT_GET, 'records_per_page', FILTER_VALIDATE_INT) ?: 20;
$offset = ($page - 1) * $recordsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $countQuery = "SELECT COUNT(DISTINCT c.ticket_number) as total 
                   FROM citations c 
                   JOIN drivers d ON c.driver_id = d.driver_id 
                   WHERE c.is_archived = 0";
    $params = [];
    $payment_status = filter_input(INPUT_GET, 'payment_status', FILTER_SANITIZE_STRING) ?: 'All';
    $payment_status = in_array($payment_status, ['Unpaid', 'Paid', 'Partially Paid', 'All']) ? $payment_status : 'All';
    if ($payment_status !== 'All') {
        $countQuery .= " AND c.payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }
    $search = htmlspecialchars(trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? ''), ENT_QUOTES, 'UTF-8');
    if ($search) {
        $countQuery .= " AND (c.ticket_number LIKE :search OR CONCAT(d.last_name, ' ', d.first_name) LIKE :search)";
        $params[':search'] = "%$search%";
    }
    $date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING) ?: '';
    $date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING) ?: '';
    if ($date_from) {
        $countQuery .= " AND c.apprehension_datetime >= :date_from";
        $params[':date_from'] = $date_from;
    }
    if ($date_to) {
        $countQuery .= " AND c.apprehension_datetime <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }

    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $recordsPerPage);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: Unable to fetch pagination data.</div>";
    error_log("Pagination PDOException: " . $e->getMessage());
    $totalRecords = 0;
    $totalPages = 1;
}
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Treasury Payment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKMSI 4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #6b7280;
            --accent: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --background: #f9fafb;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
        }

        body {
            background-color: var(--background);
            font-family: 'Roboto', sans-serif;
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary);
            color: white;
            padding: 1rem;
            transition: transform 0.3s ease, width 0.3s ease;
            position: fixed;
            top: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .sidebar-header h3 {
            display: none;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            padding: 0.75rem;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .sidebar a:hover {
            background-color: var(--primary-light);
            transform: translateX(3px);
        }

        .sidebar a.active {
            background-color: var(--primary-light);
            font-weight: 500;
        }

        .content {
            margin-left: 250px;
            flex: 1;
            padding: 1rem;
            transition: margin-left 0.3s ease;
        }

        .content.collapsed {
            margin-left: 60px;
        }

        .container {
            max-width: 100%;
            padding: 1.5rem;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }

        .container:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .header h4 {
            font-size: 0.9rem;
            font-weight: 400;
            margin-bottom: 0.25rem;
            opacity: 0.9;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
            text-align: center;
            border: 1px solid var(--border);
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-3px);
        }

        .summary-card i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .summary-card h5 {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .summary-card p {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }

        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }

        .filter-select, .date-input, .filter-input {
            border-radius: 6px;
            border: 1px solid var(--border);
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            background-color: white;
            transition: border-color 0.3s ease;
        }

        .filter-select {
            min-width: 150px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1rem;
        }

        .filter-select:focus, .date-input:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }

        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 700;
            padding: 1.2rem;
            text-align: left;
            border-bottom: 2px solid #d1d5db;
            position: sticky;
            top: 0;
            z-index: 20;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            transition: background-color 0.2s ease;
        }

        .table thead th:hover {
            background-color: var(--primary-light);
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .table tbody tr:hover {
            background-color: #e0f2fe;
            transition: background-color 0.2s ease;
        }

        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge.bg-success {
            background-color: var(--accent);
            color: white;
        }

        .badge.bg-danger {
            background-color: var(--danger);
            color: white;
        }

        .badge.bg-warning {
            background-color: var(--warning);
            color: white;
        }

        .btn-custom {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
        }

        .btn-success {
            background-color: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        .text-primary {
            color: var(--primary);
            text-decoration: none;
        }

        .text-primary:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: var(--primary);
            color: white;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border);
            padding: 1rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger);
        }

        .empty-state {
            text-align: center;
            padding: 1rem;
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .loading {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            padding: 0.75rem;
            background-color: var(--card-bg);
            border-radius: 6px;
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .page-item {
            margin: 0 0.25rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            color: var(--primary);
            border: 1px solid var(--border);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .page-link:hover {
            background-color: var(--primary-light);
            color: white;
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-item.disabled .page-link {
            color: var(--text-secondary);
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .content.collapsed {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.25rem;
            }

            .header h4 {
                font-size: 0.8rem;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-select, .date-input, .filter-input {
                width: 100%;
            }

            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .summary-card {
                margin-bottom: 0.75rem;
            }

            .pagination {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .table th, .table td {
                padding: 0.4rem;
                font-size: 0.8rem;
            }

            .badge {
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
            }

            .summary-card h5 {
                font-size: 0.85rem;
            }

            .summary-card p {
                font-size: 1.25rem;
            }
        }

        @media print {
            .sidebar, .filter-section, .btn-custom, .modal, .pagination, .loading {
                display: none;
            }

            .content {
                margin-left: 0;
            }

            .container {
                box-shadow: none;
                border: none;
                padding: 0.5rem;
            }

            .summary-section {
                display: block;
            }

            .summary-card {
                margin-bottom: 0.75rem;
                page-break-inside: avoid;
            }

            .table {
                font-size: 0.8rem;
            }

            .table thead th {
                position: static;
                box-shadow: none;
                background-color: #f1f5f9;
            }
        }
        .table th:first-child, .table td:first-child {
            width: 50px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar" aria-label="Navigation Sidebar">
        <div class="sidebar-header">
            <h3 class="text-lg font-medium">Menu</h3>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        </div>
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content" id="content">
        <div class="container">
      
            <div class="summary-section">
                <div class="summary-card">
                    <i class="fas fa-ticket-alt"></i>
                    <h5>Total Citations</h5>
                    <p><?php echo number_format($summary['total_citations'] ?? 0); ?></p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-check-circle"></i>
                    <h5>Paid Citations</h5>
                    <p><?php echo number_format($summary['paid_citations'] ?? 0); ?></p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-exclamation-circle"></i>
                    <h5>Unpaid Citations</h5>
                    <p><?php echo number_format($summary['unpaid_citations'] ?? 0); ?></p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-adjust"></i>
                    <h5>Partially Paid Citations</h5>
                    <p><?php echo number_format($summary['partially_paid_citations'] ?? 0); ?></p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <h5>Total Fines</h5>
                    <p>₱<?php echo number_format($summary['total_fines'] ?? 0, 2); ?></p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-coins"></i>
                    <h5>Total Paid</h5>
                    <p>₱<?php echo number_format($summary['total_paid'] ?? 0, 2); ?></p>
                </div>
            </div>

            <div class="filter-section" role="region" aria-label="Filter Citations">
                <select id="paymentStatusFilter" class="filter-select" aria-label="Filter by Payment Status">
                    <option value="All" <?php echo $payment_status === 'All' ? 'selected' : ''; ?>>All</option>
                    <option value="Unpaid" <?php echo $payment_status === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Paid" <?php echo $payment_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Partially Paid" <?php echo $payment_status === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                </select>
                <select id="sortFilter" class="filter-select" aria-label="Sort Citations">
                    <option value="apprehension_desc" <?php echo ($sort ?? 'apprehension_desc') === 'apprehension_desc' ? 'selected' : ''; ?>>Date (Newest)</option>
                    <option value="apprehension_asc" <?php echo ($sort ?? '') === 'apprehension_asc' ? 'selected' : ''; ?>>Date (Oldest)</option>
                    <option value="ticket_asc" <?php echo ($sort ?? '') === 'ticket_asc' ? 'selected' : ''; ?>>Ticket Number (Asc)</option>
                    <option value="driver_asc" <?php echo ($sort ?? '') === 'driver_asc' ? 'selected' : ''; ?>>Driver Name (A-Z)</option>
                    <option value="payment_asc" <?php echo ($sort ?? '') === 'payment_asc' ? 'selected' : ''; ?>>Payment Status (Paid)</option>
                    <option value="payment_desc" <?php echo ($sort ?? '') === 'payment_desc' ? 'selected' : ''; ?>>Payment Status (Unpaid)</option>
                </select>
                <select id="recordsPerPage" class="filter-select" aria-label="Records Per Page">
                    <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $recordsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $recordsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                </select>
                <input type="text" id="searchFilter" class="filter-input" placeholder="Search by ticket or driver name" value="<?php echo htmlspecialchars($search); ?>" aria-label="Search Citations">
                <input type="date" id="dateFromFilter" class="date-input" value="<?php echo htmlspecialchars($date_from); ?>" aria-label="Filter by Start Date" max="2025-07-07">
                <input type="date" id="dateToFilter" class="date-input" value="<?php echo htmlspecialchars($date_to); ?>" aria-label="Filter by End Date" max="2025-07-07">
                <button id="applyFilters" class="btn btn-primary btn-custom" title="Apply Filters" aria-label="Apply Filters"><i class="fas fa-filter"></i> Apply</button>
                <button id="clearFilters" class="btn btn-secondary btn-custom" title="Clear Filters" aria-label="Clear Filters"><i class="fas fa-times"></i> Clear</button>
                <button id="exportCsv" class="btn btn-success btn-custom" title="Export to CSV" aria-label="Export Citations to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <div id="filterError" class="alert alert-danger" style="display: none;">Please provide valid filter values.</div>
            </div>

            <div id="loading" class="loading" style="display: none;" aria-live="polite">
                <i class="fas fa-spinner fa-lg"></i> Loading...
            </div>

            <div id="citationTable" class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ticket #</th>
                            <th>Driver</th>
                            <th>License #</th>
                            <th>Plate #</th>
                            <th>Vehicle Type</th>
                            <th>Apprehension Date</th>
                            <th>Violations</th>
                            <th>Total Fine</th>
                            <th>Payment Status</th>
                            <th>Payment Amount</th>
                            <th>Payment Date</th>
                            <th>Reference #</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="citationTableBody"></tbody>
                </table>
                <div id="lazyLoadTrigger" style="height: 10px;"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4" role="navigation" aria-label="Citation Pagination">
                <p class="text-sm text-gray-600 mb-0">Showing <span id="showingStart">1</span> to <span id="showingEnd"><?php echo min($totalRecords, $recordsPerPage); ?></span> of <span id="totalRecords"><?php echo $totalRecords; ?></span> citations</p>
                <nav>
                    <ul class="pagination mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Driver Information Modal -->
    <div class="modal fade" id="driverModal" tabindex="-1" aria-labelledby="driverModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="driverModalLabel">Driver Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p><strong>License Number:</strong> <span id="driverLicense">-</span></p>
                        <p><strong>Name:</strong> <span id="driverName">-</span></p>
                        <p><strong>Address:</strong> <span id="driverAddress">-</span></p>
                        <p><strong>Total Fines:</strong> <span id="driverTotalFines">₱0.00</span></p>
                    </div>
                    <div>
                        <h6 class="font-medium text-base">Offense Records</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Offense</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="driverOffenses"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-custom" onclick="printModal('driverModal')"><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Processing Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Payment Processing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p><strong>License Number:</strong> <span id="paymentLicense">-</span></p>
                        <p><strong>Name:</strong> <span id="paymentName">-</span></p>
                        <p><strong>Address:</strong> <span id="paymentAddress">-</span></p>
                        <p><strong>Total Fines:</strong> <span id="paymentTotalFines">₱0.00</span></p>
                    </div>
                    <div>
                        <h6 class="font-medium text-base">Offense Records</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Date/Time</th>
                                        <th>Offense</th>
                                        <th>Fine</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentOffenses"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="font-medium text-base">Payment Details</h6>
                        <p><strong>Amount Due:</strong> <span id="amountDue">₱0.00</span></p>
                        <div class="mb-2">
                            <label for="paymentAmount" class="form-label">Payment Amount (₱):</label>
                            <input type="number" class="form-control" id="paymentAmount" min="0" step="0.01" aria-label="Payment Amount">
                        </div>
                        <p><strong>Change:</strong> <span id="changeAmount">₱0.00</span></p>
                        <div id="paymentError" class="alert alert-danger" style="display: none;">Please select at least one violation and enter a valid payment amount.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-custom" id="confirmPayment"><i class="fas fa-check"></i> Confirm</button>
                    <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
      const csrfToken = "<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>";
      let currentPage = <?php echo $page; ?>;
      let totalPages = <?php echo $totalPages; ?>;
      let totalRecords = <?php echo $totalRecords; ?>;
      let isLoading = false;
      let searchTimeout = null;

      document.addEventListener('DOMContentLoaded', () => {
        const elements = {
          sidebar: document.getElementById('sidebar'),
          sidebarToggle: document.getElementById('sidebarToggle'),
          content: document.getElementById('content'),
          paymentStatusFilter: document.getElementById('paymentStatusFilter'),
          sortFilter: document.getElementById('sortFilter'),
          recordsPerPage: document.getElementById('recordsPerPage'),
          searchFilter: document.getElementById('searchFilter'),
          dateFromFilter: document.getElementById('dateFromFilter'),
          dateToFilter: document.getElementById('dateToFilter'),
          applyFilters: document.getElementById('applyFilters'),
          clearFilters: document.getElementById('clearFilters'),
          exportCsv: document.getElementById('exportCsv'),
          filterError: document.getElementById('filterError'),
          loading: document.getElementById('loading'),
          citationTable: document.getElementById('citationTableBody'),
          lazyLoadTrigger: document.getElementById('lazyLoadTrigger'),
          showingStart: document.getElementById('showingStart'),
          showingEnd: document.getElementById('showingEnd'),
          totalRecords: document.getElementById('totalRecords'),
          pagination: document.getElementById('pagination'),
          driverModal: new bootstrap.Modal(document.getElementById('driverModal')),
          paymentModal: new bootstrap.Modal(document.getElementById('paymentModal')),
          driverLicense: document.getElementById('driverLicense'),
          driverName: document.getElementById('driverName'),
          driverAddress: document.getElementById('driverAddress'),
          driverTotalFines: document.getElementById('driverTotalFines'),
          driverOffenses: document.getElementById('driverOffenses'),
          paymentLicense: document.getElementById('paymentLicense'),
          paymentName: document.getElementById('paymentName'),
          paymentAddress: document.getElementById('paymentAddress'),
          paymentTotalFines: document.getElementById('paymentTotalFines'),
          paymentOffenses: document.getElementById('paymentOffenses'),
          amountDue: document.getElementById('amountDue'),
          paymentAmount: document.getElementById('paymentAmount'),
          changeAmount: document.getElementById('changeAmount'),
          confirmPayment: document.getElementById('confirmPayment'),
          paymentError: document.getElementById('paymentError')
        };

        // Sidebar toggle
        elements.sidebarToggle.addEventListener('click', () => {
          elements.sidebar.classList.toggle('collapsed');
          elements.content.classList.toggle('collapsed');
          elements.sidebar.classList.toggle('open');
        });

        // Debounce function for search input
        const debounce = (func, delay) => {
          return (...args) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => func.apply(null, args), delay);
          };
        };

        // Function to print receipt
        const printReceipt = (citationId, amount) => {
            console.log(`Printing receipt for citation ${citationId} with amount ₱${amount}`);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'print_receipt.php';
            form.target = '_blank';

            function add(name, value) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = name;
                inp.value = value;
                form.appendChild(inp);
            }

            const today = new Date().toISOString().slice(0,10);
            add('date', today);
            add('citation_id', citationId);
            add('amount1', amount.toFixed(2));
            add('amount2', '');
            add('amount_in_words', '');
            add('received[]', 'Cash');

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        };

        // Fetch citations
        const fetchCitations = (page = currentPage, append = false) => {
            if (isLoading) return;
            isLoading = true;
            elements.filterError.style.display = 'none';
            elements.loading.style.display = 'flex';
            if (!append) elements.citationTable.innerHTML = '';

            // Validate filter inputs
            if (elements.dateFromFilter.value && !elements.dateToFilter.value || !elements.dateFromFilter.value && elements.dateToFilter.value) {
                elements.filterError.textContent = 'Please provide both start and end dates or neither.';
                elements.filterError.style.display = 'block';
                elements.loading.style.display = 'none';
                isLoading = false;
                elements.filterError.scrollIntoView({ behavior: 'smooth' });
                return;
            }
            if (elements.dateFromFilter.value && elements.dateToFilter.value && new Date(elements.dateFromFilter.value) > new Date(elements.dateToFilter.value)) {
                elements.filterError.textContent = 'Start date cannot be after end date.';
                elements.filterError.style.display = 'block';
                elements.loading.style.display = 'none';
                isLoading = false;
                elements.filterError.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            const params = new URLSearchParams({
                page: page,
                records_per_page: elements.recordsPerPage.value,
                payment_status: elements.paymentStatusFilter.value,
                sort: elements.sortFilter.value,
                search: elements.searchFilter.value.trim(),
                date_from: elements.dateFromFilter.value,
                date_to: elements.dateToFilter.value,
                csrf_token: csrfToken
            });

            fetch(`fetch_payments.php?${params.toString()}`, { cache: 'no-store' })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error: ${response.status}, Response: ${text}`);
                        });
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error(`Unexpected content type: ${contentType || 'unknown'}`);
                    }
                    return response.json();
                })
                .then(data => {
                    elements.loading.style.display = 'none';
                    isLoading = false;
                    if (data.error) {
                        elements.citationTable.innerHTML = `<tr><td colspan="14" class="empty-state">${data.error}</td></tr>`;
                        console.error('Fetch error:', data.error);
                    } else {
                        if (data.html && data.rows && data.rows.length) {
                            const recordsPerPage = parseInt(elements.recordsPerPage.value);
                            const startRow = (page - 1) * recordsPerPage + 1;
                            let rowNumber = startRow;
                            const modifiedHtml = data.rows.map(row => {
                                const rowHtml = data.html.split('<tr>').slice(1).find(tr => tr.includes(row.ticket_number));
                                return `<tr><td>${rowNumber++}</td>${rowHtml}`;
                            }).join('');
                            elements.citationTable.insertAdjacentHTML(append ? 'beforeend' : 'afterbegin', modifiedHtml);
                        } else {
                            elements.citationTable.innerHTML = '<tr><td colspan="14" class="empty-state"><i class="fas fa-info-circle"></i> No citations found for the selected filters.</td></tr>';
                        }
                        totalRecords = data.totalRecords || 0;
                        totalPages = Math.ceil(totalRecords / parseInt(elements.recordsPerPage.value));
                        attachEventListeners();
                        updatePagination(page);
                    }
                })
                .catch(error => {
                    elements.loading.style.display = 'none';
                    isLoading = false;
                    elements.citationTable.innerHTML = `<tr><td colspan="14" class="empty-state">Error loading citations: ${error.message}</td></tr>`;
                    elements.filterError.textContent = `Error loading citations: ${error.message}`;
                    elements.filterError.style.display = 'block';
                    console.error('Fetch citations error:', error);
                });
        };

        // Update pagination
        const updatePagination = (current) => {
            const recordsPerPage = parseInt(elements.recordsPerPage.value);
            elements.showingStart.textContent = totalRecords > 0 ? ((current - 1) * recordsPerPage + 1) : 0;
            elements.showingEnd.textContent = Math.min(current * recordsPerPage, totalRecords);
            elements.totalRecords.textContent = totalRecords;

            elements.pagination.innerHTML = '';
            const maxPagesToShow = 5;
            const half = Math.floor(maxPagesToShow / 2);
            let startPage = Math.max(1, current - half);
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage < maxPagesToShow - 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            if (current > 1) {
                const prevLi = document.createElement('li');
                prevLi.className = 'page-item';
                prevLi.innerHTML = '<a class="page-link" href="#" aria-label="Previous Page">Previous</a>';
                prevLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        fetchCitations(currentPage);
                    }
                });
                elements.pagination.appendChild(prevLi);
            }

            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === current ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    fetchCitations(i);
                });
                elements.pagination.appendChild(li);
            }

            if (current < totalPages) {
                const nextLi = document.createElement('li');
                nextLi.className = 'page-item';
                nextLi.innerHTML = '<a class="page-link" href="#" aria-label="Next Page">Next</a>';
                nextLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        fetchCitations(currentPage);
                    }
                });
                elements.pagination.appendChild(nextLi);
            }
        };

        // Attach event listeners to table rows
        const attachEventListeners = () => {
            document.querySelectorAll('.driver-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const driverId = link.dataset.driverId;
                    elements.driverLicense.textContent = link.dataset.licenseNumber || 'N/A';
                    elements.driverName.textContent = link.textContent;
                    elements.driverAddress.textContent = [
                        link.dataset.zone || '',
                        link.dataset.barangay || '',
                        link.dataset.municipality || '',
                        link.dataset.province || ''
                    ].filter(Boolean).join(', ') || 'N/A';
                    fetchDriverOffenses(driverId);
                    elements.driverModal.show();
                });
            });

            document.querySelectorAll('.pay-now').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const citationId = button.dataset.citationId;
                    const driverId = button.dataset.driverId;
                    elements.paymentLicense.textContent = button.dataset.licenseNumber || 'N/A';
                    elements.paymentName.textContent = button.closest('tr').querySelector('.driver-link').textContent;
                    elements.paymentAddress.textContent = [
                        button.dataset.zone || '',
                        button.dataset.barangay || '',
                        button.dataset.municipality || '',
                        button.dataset.province || ''
                    ].filter(Boolean).join(', ') || 'N/A';
                    fetchPaymentOffenses(citationId, driverId);
                    elements.paymentModal.show();
                });
            });
        };

        // Fetch driver offenses
        const fetchDriverOffenses = (driverId) => {
            elements.loading.style.display = 'flex';
            elements.driverOffenses.innerHTML = '';
            console.log(`Fetching offenses for driverId: ${driverId}`);
            
            fetch(`get_driver_info.php?driver_id=${encodeURIComponent(driverId)}&csrf_token=${encodeURIComponent(csrfToken)}`, { 
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error: ${response.status}, Response: ${text}`);
                        });
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error(`Unexpected content type: ${contentType || 'unknown'}`);
                    }
                    return response.json();
                })
                .then(data => {
                    elements.loading.style.display = 'none';
                    let totalFines = 0;

                    if (data.error) {
                        elements.driverOffenses.innerHTML = `<tr><td colspan="4" class="empty-state">Error: ${data.error}</td></tr>`;
                        console.error('Fetch driver offenses error:', data.error);
                        return;
                    }

                    if (!data || !Array.isArray(data) || data.length === 0) {
                        elements.driverOffenses.innerHTML = '<tr><td colspan="4" class="empty-state"><i class="fas fa-info-circle"></i> No offenses found for this driver.</td></tr>';
                        elements.driverTotalFines.textContent = '₱0.00';
                        return;
                    }

                    data.forEach(offense => {
                        const fine = parseFloat(offense.fine) || 0;
                        totalFines += fine;
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${offense.apprehension_datetime || 'N/A'}</td>
                            <td>${offense.violation_type || 'Unknown'}</td>
                            <td>₱${fine.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                            <td>${offense.violation_payment_status || 'Unpaid'}</td>
                        `;
                        elements.driverOffenses.appendChild(row);
                    });
                    elements.driverTotalFines.textContent = `₱${totalFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                })
                .catch(error => {
                    elements.loading.style.display = 'none';
                    elements.driverOffenses.innerHTML = `<tr><td colspan="4" class="empty-state">Error loading offenses: ${error.message}</td></tr>`;
                    elements.driverTotalFines.textContent = '₱0.00';
                    console.error('Fetch driver offenses error:', error);
                });
        };

        // Fetch payment offenses
        const fetchPaymentOffenses = (citationId, driverId) => {
            elements.loading.style.display = 'flex';
            fetch(`fetch_payments.php?citation_id=${citationId}&driver_id=${driverId}&csrf_token=${csrfToken}`, { cache: 'no-store' })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error: ${response.status}, Response: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    elements.paymentOffenses.innerHTML = '';
                    let totalFines = 0;
                    let unpaidFines = 0;
                    if (data.error) {
                        elements.paymentOffenses.innerHTML = `<tr><td colspan="5" class="empty-state">${data.error}</td></tr>`;
                    } else if (!data.offenses || !data.offenses.length) {
                        elements.paymentOffenses.innerHTML = `<tr><td colspan="5" class="empty-state">No payment data found for citation ${citationId}</td></tr>`;
                    } else {
                        data.offenses.forEach(offense => {
                            const fine = parseFloat(offense.fine) || 0;
                            totalFines += fine;
                            const isPaid = offense.payment_status === 'Paid';
                            if (!isPaid) unpaidFines += fine;
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><input type="checkbox" class="violation-checkbox" data-violation-id="${offense.violation_id}" data-fine="${fine}" ${isPaid ? 'disabled' : 'checked'}></td>
                                <td>${offense.date || 'N/A'}</td>
                                <td>${offense.violation_type || 'Unknown'} ${offense.offense_count ? '(Offense ' + offense.offense_count + ')' : ''}</td>
                                <td>₱${fine.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                <td>${isPaid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-danger">Unpaid</span>'}</td>
                            `;
                            elements.paymentOffenses.appendChild(row);
                        });
                        elements.paymentTotalFines.textContent = `₱${totalFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        elements.amountDue.textContent = `₱${unpaidFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        elements.paymentAmount.value = unpaidFines.toFixed(2);
                        elements.changeAmount.textContent = '₱0.00';
                        attachViolationListeners(citationId);
                    }
                    elements.loading.style.display = 'none';
                })
                .catch(error => {
                    elements.paymentOffenses.innerHTML = `<tr><td colspan="5" class="empty-state">Error loading payment data: ${error.message}</td></tr>`;
                    elements.loading.style.display = 'none';
                    console.error('Fetch payment offenses error:', error);
                });
        };

        // Attach listeners to violation checkboxes
        const attachViolationListeners = (citationId) => {
            const checkboxes = document.querySelectorAll('.violation-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    let totalFines = 0;
                    checkboxes.forEach(cb => {
                        if (cb.checked) {
                            totalFines += parseFloat(cb.dataset.fine) || 0;
                        }
                    });
                    elements.amountDue.textContent = `₱${totalFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                    elements.paymentAmount.value = totalFines.toFixed(2);
                    const amount = parseFloat(elements.paymentAmount.value) || 0;
                    const change = amount - totalFines;
                    elements.changeAmount.textContent = `₱${change.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                    elements.paymentError.style.display = change < 0 ? 'block' : 'none';
                });
            });

            elements.paymentAmount.addEventListener('input', () => {
                const amount = parseFloat(elements.paymentAmount.value) || 0;
                const due = parseFloat(elements.amountDue.textContent.replace('₱', '').replace(',', '')) || 0;
                const change = amount - due;
                elements.changeAmount.textContent = `₱${change.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                elements.paymentError.style.display = change < 0 ? 'block' : 'none';
            });

            elements.confirmPayment.onclick = () => {
                const selectedViolations = Array.from(document.querySelectorAll('.violation-checkbox'))
                    .filter(cb => cb.checked)
                    .map(cb => cb.dataset.violationId);
                if (!selectedViolations.length) {
                    elements.paymentError.textContent = 'Please select at least one violation to pay.';
                    elements.paymentError.style.display = 'block';
                    return;
                }
                
                const amount = parseFloat(elements.paymentAmount.value) || 0;
                const due = parseFloat(elements.amountDue.textContent.replace('₱', '').replace(',', '')) || 0;
                if (amount < due) {
                    elements.paymentError.textContent = 'Payment amount must be at least the amount due.';
                    elements.paymentError.style.display = 'block';
                    return;
                }

                const formData = new FormData();
                formData.append('citation_id', citationId);
                formData.append('amount', amount.toFixed(2));
                formData.append('csrf_token', csrfToken);
                selectedViolations.forEach(id => formData.append('violation_ids[]', id));

                fetch('pay_citation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        elements.paymentModal.hide();
                        printReceipt(citationId, amount);
                        fetchCitations();
                    } else {
                        elements.paymentError.textContent = data.message;
                        elements.paymentError.style.display = 'block';
                    }
                })
                .catch(error => {
                    elements.paymentError.textContent = 'Error processing payment: ' + error.message;
                    elements.paymentError.style.display = 'block';
                    console.error('Payment error:', error);
                });
            };
        };

        // Clear filters
        elements.clearFilters.addEventListener('click', () => {
            elements.paymentStatusFilter.value = 'All';
            elements.sortFilter.value = 'apprehension_desc';
            elements.recordsPerPage.value = '20';
            elements.searchFilter.value = '';
            elements.dateFromFilter.value = '';
            elements.dateToFilter.value = '';
            currentPage = 1;
            fetchCitations();
        });

        // Debounced search input
        elements.searchFilter.addEventListener('input', debounce(() => {
            currentPage = 1;
            fetchCitations();
        }, 500));

        // Apply filters
        elements.applyFilters.addEventListener('click', () => {
            currentPage = 1;
            fetchCitations();
        });

        // Records per page change
        elements.recordsPerPage.addEventListener('change', () => {
            currentPage = 1;
            fetchCitations();
        });

        // Export CSV
        elements.exportCsv.addEventListener('click', () => {
            const params = new URLSearchParams({
                payment_status: elements.paymentStatusFilter.value,
                search: elements.searchFilter.value.trim(),
                date_from: elements.dateFromFilter.value,
                date_to: elements.dateToFilter.value,
                csrf_token: csrfToken
            });
            fetch(`fetch_payments.php?${params.toString()}`, { cache: 'no-store' })
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    const csv = ['Ticket #,Driver,License #,Plate #,Vehicle Type,Apprehension Date,Violations,Total Fine,Payment Status,Payment Amount,Payment Date,Reference #'];
                    if (data.rows && data.rows.length) {
                        data.rows.forEach(row => {
                            csv.push([
                                `"${row.ticket_number || ''}"`,
                                `"${row.driver_name || ''}"`,
                                `"${row.license_number || ''}"`,
                                `"${row.plate_mv_engine_chassis_no || ''}"`,
                                `"${row.vehicle_type || ''}"`,
                                `"${row.apprehension_datetime ? new Date(row.apprehension_datetime).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A'}"`,
                                `"${row.violations || 'None'}"`,
                                `"₱${Number(row.total_fine || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}"`,
                                `"${row.payment_status || 'Unpaid'}"`,
                                `"${row.payment_amount ? '₱' + Number(row.payment_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : 'N/A'}"`,
                                `"${row.payment_date ? new Date(row.payment_date).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A'}"`,
                                `"${row.reference_number || 'N/A'}"`
                            ].join(','));
                        });
                    }
                    const bom = '\uFEFF';
                    const blob = new Blob([bom + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = `citations_${new Date().toISOString().slice(0, 10)}.csv`;
                    link.click();
                })
                .catch(error => {
                    elements.filterError.textContent = `Error exporting CSV: ${error.message}`;
                    elements.filterError.style.display = 'block';
                    console.error('Export CSV error:', error);
                });
        });

        // Lazy loading with IntersectionObserver
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && currentPage < totalPages && !isLoading) {
                currentPage++;
                fetchCitations(currentPage, true);
            }
        }, { threshold: 0.1 });

        observer.observe(elements.lazyLoadTrigger);

        // Print modal
        window.printModal = (modalId) => {
            const modal = document.getElementById(modalId);
            const modalContent = modal.querySelector('.modal-content').outerHTML;
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print</title>
                        <style>
                            body { font-family: 'Roboto', sans-serif; padding: 20px; }
                            .modal-content { max-width: 600px; margin: auto; }
                            .modal-header, .modal-footer { background-color: #1e40af; color: white; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
                            th { background-color: #f1f5f9; }
                        </style>
                    </head>
                    <body>${modalContent}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        };

        // Initial load
        fetchCitations();
      });
    </script>
</body>
</html>