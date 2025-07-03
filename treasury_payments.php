<?php
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
    $paidRows = $conn->query("SELECT citation_id, ticket_number, payment_amount FROM citations WHERE payment_status = 'Paid' AND is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    error_log("Paid Citations: " . print_r($paidRows, true));
} catch (PDOException $e) {
    $summary = ['total_citations' => 0, 'paid_citations' => 0, 'unpaid_citations' => 0, 'total_fines' => 0, 'total_paid' => 0];
    error_log("Summary PDOException: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #4b5563;
            --accent: #10b981;
            --danger: #dc2626;
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
            line-height: 1.6;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: 220px;
            height: 100vh;
            background-color: var(--primary);
            padding: 1rem;
            color: white;
            transition: transform 0.3s ease;
            z-index: 1000;
            flex-shrink: 0;
            overflow-y: auto;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .sidebar a:hover {
            background-color: var(--primary-light);
            transform: translateX(4px);
        }

        .sidebar a.active {
            background-color: var(--primary-light);
            font-weight: 500;
        }

        .content {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            height: 100vh;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                left: 0;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 1.5rem;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
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
            height: 6px;
            background: linear-gradient(90deg, var(--warning), #facc15);
        }

        .header h4 {
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 0.25rem;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.02em;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-card i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .summary-card h5 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .summary-card p {
            font-size: 1.5rem;
            font-weight: 700;
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
            border-radius: 8px;
            border: 1px solid var(--border);
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            background-color: white;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1rem;
            min-width: 150px;
        }

        .filter-select:focus, .date-input:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
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
            font-weight: 500;
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            white-space: nowrap;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
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

        .btn-custom {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background-color 0.2s ease, transform 0.2s ease;
            text-decoration: none;
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
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #374151;
            transform: translateY(-2px);
        }

        .text-primary {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .text-primary:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .modal-content {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background-color: var(--primary);
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border);
            padding: 1rem;
        }

        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: none;
        }

        .payment-history {
            margin-top: 2rem;
            padding: 1rem;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .payment-history h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header h1 { font-size: 1.25rem; }
            .header h4 { font-size: 0.8rem; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-select, .date-input, .filter-input { width: 100%; max-width: none; }
            .table th, .table td { padding: 0.5rem 0.75rem; font-size: 0.85rem; }
            .btn-custom { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
            .summary-card { margin-bottom: 1rem; }
        }

        @media (max-width: 576px) {
            .table th, .table td { padding: 0.4rem 0.5rem; font-size: 0.75rem; }
            .badge { padding: 0.3rem 0.6rem; font-size: 0.75rem; }
            .summary-card h5 { font-size: 0.9rem; }
            .summary-card p { font-size: 1.2rem; }
        }

        @media print {
            .sidebar, .filter-section, .btn-custom, .modal, .pagination { display: none; }
            .content { margin-left: 0; }
            .container { box-shadow: none; border: none; margin: 0; padding: 1rem; }
            .summary-section { display: block; }
            .summary-card { margin-bottom: 1rem; }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar" aria-label="Navigation Sidebar">
        <div class="sidebar-header">
            <h3 class="text-lg font-semibold">Menu</h3>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        </div>
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content">
        <div class="container">
            <div class="header">
                <h4>Republic of the Philippines</h4>
                <h4>Province of Cagayan • Municipality of Baggao</h4>
                <h1>Treasury Payment Management</h1>
            </div>

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
                    <option value="Unpaid" selected>Unpaid</option>
                    <option value="Paid">Paid</option>
                    <option value="All">All</option>
                </select>
                <select id="sortFilter" class="filter-select" aria-label="Sort Citations">
                    <option value="apprehension_desc" selected>Date (Newest)</option>
                    <option value="apprehension_asc">Date (Oldest)</option>
                    <option value="ticket_asc">Ticket Number (Asc)</option>
                    <option value="driver_asc">Driver Name (A-Z)</option>
                    <option value="payment_asc">Payment Status (Paid)</option>
                    <option value="payment_desc">Payment Status (Unpaid)</option>
                </select>
                <select id="recordsPerPage" class="filter-select" aria-label="Records Per Page">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <input type="text" id="searchFilter" class="filter-input" placeholder="Search by ticket or driver name" aria-label="Search Citations">
                <input type="date" id="dateFromFilter" class="date-input" aria-label="Filter by Start Date" max="2025-07-02">
                <input type="date" id="dateToFilter" class="date-input" aria-label="Filter by End Date" max="2025-07-02">
                <button id="applyFilters" class="btn btn-primary btn-custom" title="Apply Filters" aria-label="Apply Filters"><i class="fas fa-filter"></i> Apply</button>
                <button id="exportCsv" class="btn btn-secondary btn-custom" title="Export to CSV" aria-label="Export Citations to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <div id="filterError" class="error-message">Please provide valid filter values.</div>
            </div>

            <div id="loading" class="loading" style="display: none;" aria-live="polite">
                <i class="fas fa-spinner fa-2x"></i> Loading citations...
            </div>

            <div id="citationTable"></div>

            <?php
            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $countQuery = "SELECT COUNT(DISTINCT c.ticket_number) as total 
                              FROM citations c 
                              JOIN drivers d ON c.driver_id = d.driver_id 
                              WHERE c.is_archived = 0";
                $params = [];
                $payment_status = filter_input(INPUT_GET, 'payment_status', FILTER_SANITIZE_STRING) ?: 'Unpaid';
                $payment_status = in_array($payment_status, ['Unpaid', 'Paid', 'All']) ? $payment_status : 'Unpaid';
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
                $recordsPerPage = filter_input(INPUT_GET, 'records_per_page', FILTER_VALIDATE_INT) ?: 20;
                $totalPages = ceil($totalRecords / $recordsPerPage);
            } catch (PDOException $e) {
                echo "<div class='error-message'>Error: Unable to fetch pagination data.</div>";
                error_log("Pagination PDOException: " . $e->getMessage());
                $totalRecords = 0;
                $totalPages = 1;
            }
            $conn = null;
            ?>

            <div class="pagination mt-3" role="navigation" aria-label="Citation Pagination">
                <p>Showing <span id="showingStart">1</span> to <span id="showingEnd"><?php echo min($totalRecords, $recordsPerPage); ?></span> of <span id="totalRecords"><?php echo $totalRecords; ?></span> citations</p>
                <nav>
                    <ul class="pagination">
                        <li class="page-item"><a class="page-link" href="#" id="prevPage" aria-label="Previous Page">Previous</a></li>
                        <li class="page-item"><a class="page-link" href="#" id="nextPage" aria-label="Next Page">Next</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Payment History -->
            <div class="payment-history" role="region" aria-labelledby="paymentHistoryHeader">
                <h3 id="paymentHistoryHeader">Payment History (Last 30 Days)</h3>
                <div id="paymentHistoryTable" class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Driver</th>
                                <th>Payment Amount</th>
                                <th>Payment Date</th>
                                <th>Reference #</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Information Modal -->
    <div class="modal fade" id="driverModal" tabindex="-1" aria-labelledby="driverModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="driverModalLabel">Driver Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="https://via.placeholder.com/100" alt="No Photo" class="rounded-circle" style="width: 100px; height: 100px;">
                    </div>
                    <div class="mb-3">
                        <p><strong>License Number:</strong> <span id="driverLicense">-</span></p>
                        <p><strong>Name:</strong> <span id="driverName">-</span></p>
                        <p><strong>Address:</strong> <span id="driverAddress">-</span></p>
                        <p><strong>Total Fines:</strong> <span id="driverTotalFines">₱0.00</span></p>
                    </div>
                    <div>
                        <h6>Offense Records</h6>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-custom" onclick="printModal('driverModal')"><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Processing Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Payment Processing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img src="https://via.placeholder.com/100" alt="No Photo" class="rounded-circle" style="width: 100px; height: 100px;">
                    </div>
                    <div class="mb-3">
                        <p><strong>License Number:</strong> <span id="paymentLicense">-</span></p>
                        <p><strong>Name:</strong> <span id="paymentName">-</span></p>
                        <p><strong>Address:</strong> <span id="paymentAddress">-</span></p>
                        <p><strong>Total Fines:</strong> <span id="paymentTotalFines">₱0.00</span></p>
                    </div>
                    <div>
                        <h6>Offense Records</h6>
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
                    <div class="mt-3">
                        <h6>Payment Details</h6>
                        <p><strong>Amount Due:</strong> <span id="amountDue">₱0.00</span></p>
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">Payment Amount (₱):</label>
                            <input type="number" class="form-control" id="paymentAmount" min="0" step="0.01" aria-label="Payment Amount">
                        </div>
                        <p><strong>Change:</strong> <span id="changeAmount">₱0.00</span></p>
                        <div id="paymentError" class="error-message">Please select at least one violation and enter a valid payment amount.</div>
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
        let currentPage = 1;
        let totalPages = <?php echo $totalPages; ?>;
        let totalRecords = <?php echo $totalRecords; ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const elements = {
                sidebar: document.getElementById('sidebar'),
                sidebarToggle: document.getElementById('sidebarToggle'),
                paymentStatusFilter: document.getElementById('paymentStatusFilter'),
                sortFilter: document.getElementById('sortFilter'),
                recordsPerPage: document.getElementById('recordsPerPage'),
                searchFilter: document.getElementById('searchFilter'),
                dateFromFilter: document.getElementById('dateFromFilter'),
                dateToFilter: document.getElementById('dateToFilter'),
                applyFilters: document.getElementById('applyFilters'),
                exportCsv: document.getElementById('exportCsv'),
                filterError: document.getElementById('filterError'),
                loading: document.getElementById('loading'),
                citationTable: document.getElementById('citationTable'),
                showingStart: document.getElementById('showingStart'),
                showingEnd: document.getElementById('showingEnd'),
                totalRecords: document.getElementById('totalRecords'),
                prevPage: document.getElementById('prevPage'),
                nextPage: document.getElementById('nextPage'),
                paymentHistoryTable: document.getElementById('paymentHistoryTable').querySelector('tbody'),
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
                elements.sidebar.classList.toggle('open');
            });

            // Fetch citations
            const fetchCitations = () => {
                elements.filterError.style.display = 'none';
                if (elements.dateFromFilter.value && !elements.dateToFilter.value || !elements.dateFromFilter.value && elements.dateToFilter.value) {
                    elements.filterError.textContent = 'Please provide both start and end dates or neither.';
                    elements.filterError.style.display = 'block';
                    elements.filterError.scrollIntoView({ behavior: 'smooth' });
                    return;
                }
                if (elements.dateFromFilter.value && elements.dateToFilter.value && new Date(elements.dateFromFilter.value) > new Date(elements.dateToFilter.value)) {
                    elements.filterError.textContent = 'Start date cannot be after end date.';
                    elements.filterError.style.display = 'block';
                    elements.filterError.scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                elements.loading.style.display = 'flex';
                const params = new URLSearchParams({
                    page: currentPage,
                    records_per_page: elements.recordsPerPage.value,
                    payment_status: elements.paymentStatusFilter.value,
                    sort: elements.sortFilter.value,
                    search: elements.searchFilter.value,
                    date_from: elements.dateFromFilter.value,
                    date_to: elements.dateToFilter.value,
                    csrf_token: csrfToken
                });
                fetch(`fetch_payments.php?${params.toString()}`)
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(`HTTP error: ${response.status}, Response: ${text}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            elements.citationTable.innerHTML = `<div class="error-message">${data.error}</div>`;
                        } else {
                            elements.citationTable.innerHTML = data.html;
                            totalRecords = data.totalRecords;
                            totalPages = Math.ceil(totalRecords / parseInt(elements.recordsPerPage.value));
                            attachEventListeners();
                            updatePagination();
                        }
                        elements.loading.style.display = 'none';
                    })
                    .catch(error => {
                        elements.loading.style.display = 'none';
                        elements.filterError.textContent = 'Error loading citations: ' + error.message;
                        elements.filterError.style.display = 'block';
                        console.error('Fetch error:', error);
                    });
            };

            // Fetch payment history
            const fetchPaymentHistory = () => {
                const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
                fetch(`fetch_payments.php?payment_status=Paid&date_from=${thirtyDaysAgo}&csrf_token=${csrfToken}`)
                    .then(response => response.json())
                    .then(data => {
                        elements.paymentHistoryTable.innerHTML = '';
                        if (!data.rows.length) {
                            elements.paymentHistoryTable.innerHTML = '<tr><td colspan="5" class="no-data">No payment history in the last 30 days.</td></tr>';
                        } else {
                            data.rows.forEach(row => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>${row.ticket_number || 'N/A'}</td>
                                    <td>${row.driver_name || 'Unknown'}</td>
                                    <td>₱${Number(row.payment_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                    <td>${row.payment_date ? new Date(row.payment_date).toLocaleString() : 'N/A'}</td>
                                    <td>${row.reference_number || 'N/A'}</td>
                                `;
                                elements.paymentHistoryTable.appendChild(tr);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching payment history:', error);
                        elements.paymentHistoryTable.innerHTML = '<tr><td colspan="5" class="no-data">Error loading payment history.</td></tr>';
                    });
            };

            // Update pagination
            const updatePagination = () => {
                const recordsPerPage = parseInt(elements.recordsPerPage.value);
                elements.showingStart.textContent = ((currentPage - 1) * recordsPerPage + 1);
                elements.showingEnd.textContent = Math.min(currentPage * recordsPerPage, totalRecords);
                elements.totalRecords.textContent = totalRecords;
                elements.prevPage.parentElement.classList.toggle('disabled', currentPage === 1);
                elements.nextPage.parentElement.classList.toggle('disabled', currentPage === totalPages);
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
                fetch(`fetch_driver_offenses.php?driver_id=${driverId}&csrf_token=${csrfToken}`)
                    .then(response => response.json())
                    .then(data => {
                        elements.driverOffenses.innerHTML = '';
                        let totalFines = 0;
                        data.offenses.forEach(offense => {
                            const fine = parseFloat(offense.fine) || 0;
                            totalFines += fine;
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${offense.date || 'N/A'}</td>
                                <td>${offense.violation_type || 'Unknown'}</td>
                                <td>₱${fine.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                <td>${offense.payment_status || 'Unpaid'}</td>
                            `;
                            elements.driverOffenses.appendChild(row);
                        });
                        elements.driverTotalFines.textContent = `₱${totalFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                    })
                    .catch(error => {
                        elements.driverOffenses.innerHTML = '<tr><td colspan="4" class="text-center">Error loading offenses</td></tr>';
                        console.error('Error fetching driver offenses:', error);
                    });
            };

            // Fetch payment offenses
            const fetchPaymentOffenses = (citationId, driverId) => {
                fetch(`fetch_payments.php?citation_id=${citationId}&driver_id=${driverId}&csrf_token=${csrfToken}`)
                    .then(response => response.json())
                    .then(data => {
                        elements.paymentOffenses.innerHTML = '';
                        let totalFines = 0;
                        data.offenses.forEach(offense => {
                            const fine = parseFloat(offense.fine) || 0;
                            totalFines += fine;
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><input type="checkbox" class="violation-checkbox" data-violation-id="${offense.violation_id}" data-fine="${fine}" checked></td>
                                <td>${offense.date || 'N/A'}</td>
                                <td>${offense.violation_type || 'Unknown'}</td>
                                <td>₱${fine.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                <td>${offense.payment_status || 'Unpaid'}</td>
                            `;
                            elements.paymentOffenses.appendChild(row);
                        });
                        elements.paymentTotalFines.textContent = `₱${totalFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        elements.amountDue.textContent = `₱${totalFines.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        elements.paymentAmount.value = totalFines.toFixed(2);
                        elements.changeAmount.textContent = '₱0.00';
                        attachViolationListeners(citationId);
                    })
                    .catch(error => {
                        elements.paymentOffenses.innerHTML = '<tr><td colspan="5" class="text-center">Error loading offenses</td></tr>';
                        console.error('Error fetching payment offenses:', error);
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
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                elements.paymentModal.hide();
                                fetchCitations();
                                fetchPaymentHistory();
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

            // Export CSV
            elements.exportCsv.addEventListener('click', () => {
                const params = new URLSearchParams({
                    payment_status: elements.paymentStatusFilter.value,
                    search: elements.searchFilter.value,
                    date_from: elements.dateFromFilter.value,
                    date_to: elements.dateToFilter.value,
                    csrf_token: csrfToken
                });
                fetch(`fetch_payments.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        const csv = ['Ticket #,Driver,License #,Plate #,Vehicle Type,Apprehension Date,Violations,Total Fine,Payment Status,Payment Amount,Payment Date,Reference #'];
                        data.rows.forEach(row => {
                            csv.push([
                                `"${row.ticket_number || ''}"`,
                                `"${row.driver_name || ''}"`,
                                `"${row.license_number || ''}"`,
                                `"${row.plate_mv_engine_chassis_no || ''}"`,
                                `"${row.vehicle_type || ''}"`,
                                `"${row.apprehension_datetime ? new Date(row.apprehension_datetime).toLocaleString() : 'N/A'}"`,
                                `"${row.violations || 'None'}"`,
                                `"₱${Number(row.total_fine || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}"`,
                                `"${row.payment_status || 'Unpaid'}"`,
                                `"${row.payment_amount ? '₱' + Number(row.payment_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : 'N/A'}"`,
                                `"${row.payment_date ? new Date(row.payment_date).toLocaleString() : 'N/A'}"`,
                                `"${row.reference_number || 'N/A'}"`
                            ].join(','));
                        });
                        const bom = '\uFEFF';
                        const blob = new Blob([bom + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = `citations_${new Date().toISOString().slice(0, 10)}.csv`;
                        link.click();
                    })
                    .catch(error => {
                        alert('Error exporting CSV: ' + error.message);
                        console.error('Export error:', error);
                    });
            });

            // Pagination
            elements.prevPage.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    fetchCitations();
                }
            });

            elements.nextPage.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    fetchCitations();
                }
            });

            // Apply filters
            elements.applyFilters.addEventListener('click', () => {
                currentPage = 1;
                fetchCitations();
            });

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
            fetchPaymentHistory();
        });
    </script>
</body>
</html>