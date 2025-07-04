<?php
session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
require 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Data Fetch Functions
    function getScalar($sql, $conn, $params = []) {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchColumn();
            error_log("Query: $sql | Result: " . ($result !== false ? $result : '0'));
            return $result !== false ? $result : 0;
        } catch (PDOException $e) {
            error_log("Query Error: $sql | Error: " . $e->getMessage());
            return 0;
        }
    }

    function getRows($sql, $conn, $params = []) {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            error_log("Query: $sql | Rows: " . count($result));
            return $result;
        } catch (PDOException $e) {
            error_log("Query Error: $sql | Error: " . $e->getMessage());
            return [];
        }
    }

    // Force cache refresh for testing (remove after confirming fix)
    unset($_SESSION['dashboard_kpi']);

    // Cache KPI data
    if (!isset($_SESSION['dashboard_kpi'])) {
        $total_citations = getScalar("SELECT COUNT(*) FROM citations", $conn);
        $unpaid_citations = getScalar("SELECT COUNT(*) FROM citations WHERE payment_status = 'Unpaid'", $conn);
        $total_revenue = getScalar("SELECT COALESCE(SUM(payment_amount), 0) FROM citations WHERE payment_status = 'Paid'", $conn);
        $new_this_month = getScalar(
            "SELECT COUNT(*) FROM citations WHERE YEAR(apprehension_datetime) = :year AND MONTH(apprehension_datetime) = :month",
            $conn,
            [':year' => date('Y'), ':month' => date('m')]
        );
        $expected_fine = getScalar(
            "SELECT COALESCE(SUM(CASE v.offense_count
                WHEN 1 THEN vt.fine_amount_1
                WHEN 2 THEN vt.fine_amount_2
                ELSE vt.fine_amount_3
            END), 0)
            FROM violations v
            JOIN citations c ON v.citation_id = c.citation_id
            JOIN violation_types vt ON v.violation_type = vt.violation_type
            WHERE c.payment_status IN ('Unpaid', 'Partially Paid')",
            $conn
        );
        $todays_revenue = getScalar(
            "SELECT COALESCE(SUM(payment_amount), 0)
            FROM citations
            WHERE payment_status = 'Paid' AND DATE(payment_date) = :today",
            $conn,
            [':today' => date('Y-m-d')]
        );
        $top_violations = getRows(
            "SELECT v.violation_type, COUNT(*) AS cnt
            FROM violations v
            GROUP BY v.violation_type
            ORDER BY cnt DESC
            LIMIT 5",
            $conn
        );

        // Generate last 12 months for revenue
        $monthly_rev = [];
        for ($i = 0; $i < 12; $i++) {
            $month = date('F Y', strtotime("-$i months"));
            $monthly_rev[$month] = ['month' => $month, 'total' => 0];
        }
        $rev_data = getRows(
            "SELECT DATE_FORMAT(payment_date, '%M %Y') AS month, COALESCE(SUM(payment_amount), 0) AS total
            FROM citations
            WHERE payment_status = 'Paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY payment_date DESC",
            $conn
        );
        foreach ($rev_data as $row) {
            $monthly_rev[$row['month']] = $row;
        }
        $monthly_rev = array_values($monthly_rev);

        $by_vehicle = getRows(
            "SELECT veh.vehicle_type, COUNT(*) AS cnt
            FROM citations c
            JOIN vehicles veh ON c.vehicle_id = veh.vehicle_id
            GROUP BY veh.vehicle_type
            ORDER BY cnt DESC",
            $conn
        );

        $_SESSION['dashboard_kpi'] = compact(
            'total_citations',
            'unpaid_citations',
            'total_revenue',
            'new_this_month',
            'expected_fine',
            'todays_revenue',
            'top_violations',
            'monthly_rev',
            'by_vehicle'
        );
    }
    extract($_SESSION['dashboard_kpi']);

    // Get recent citations
    $recent_citations = getRows(
        "SELECT c.*, d.last_name, d.first_name, v.vehicle_type
        FROM citations c
        JOIN drivers d ON c.driver_id = d.driver_id
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        ORDER BY c.apprehension_datetime DESC
        LIMIT 10",
        $conn
    );
} catch (PDOException $e) {
    error_log("PDOException in dashboard.php: " . $e->getMessage());
    $total_citations = $unpaid_citations = $total_revenue = $new_this_month = $expected_fine = $todays_revenue = 0;
    $top_violations = $monthly_rev = $by_vehicle = $recent_citations = [];
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Citation Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #3f51b5; /* Material Blue 500 */
            --primary-dark: #303f9f; /* Blue 700 */
            --accent: #ff4081; /* Pink A200 */
            --success: #4caf50; /* Green 500 */
            --warning: #ff9800; /* Orange 500 */
            --danger: #f44336; /* Red 500 */
            --info: #00bcd4; /* Cyan 500 */
            --surface: #ffffff; /* White for cards */
            --background: #f5f5f5; /* Light grey background */
            --text-primary: #212121; /* Dark text */
            --text-secondary: #757575; /* Grey text */
            --elevation-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.08);
            --elevation-shadow-hover: 0 3px 6px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

        .content {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }

        .content.collapsed {
            margin-left: 80px;
        }

        .section-header {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 32px;
            color: var(--primary);
            position: relative;
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 48px;
            height: 4px;
            background: var(--primary);
        }

        .kpi-card {
            background: var(--surface);
            padding: 24px;
            border-radius: 8px;
            box-shadow: var(--elevation-shadow);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .kpi-card.total-citations { background: #e8eaf6; } /* Blue 50 */
        .kpi-card.unpaid-citations { background: #ffebee; } /* Red 50 */
        .kpi-card.total-revenue { background: #e8f5e9; } /* Green 50 */
        .kpi-card.new-this-month { background: #e3f2fd; } /* Blue 50 (lighter) */
        .kpi-card.expected-fine { background: #e0f7fa; } /* Cyan 50 */
        .kpi-card.todays-revenue { background: #fff3e0; } /* Orange 50 */

        .kpi-card:hover {
            box-shadow: var(--elevation-shadow-hover);
            transform: translateY(-4px);
        }

        .kpi-icon {
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        .kpi-card h3 {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-secondary);
            margin: 0 0 8px 0;
        }

        .kpi-card p {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .card-section {
            background: var(--surface);
            padding: 32px;
            border-radius: 8px;
            box-shadow: var(--elevation-shadow);
            transition: box-shadow 0.3s ease;
            margin-bottom: 48px;
        }

        .card-section:hover {
            box-shadow: var(--elevation-shadow-hover);
        }

        .accordion-item {
            border: none;
            margin-bottom: 16px;
            background: var(--surface);
            border-radius: 8px !important;
            box-shadow: var(--elevation-shadow);
        }

        .accordion-header {
            background: var(--surface);
            border-radius: 8px;
        }

        .accordion-button {
            background: var(--surface) !important;
            color: var(--text-primary);
            font-weight: 500;
            padding: 16px;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .accordion-button:not(.collapsed) {
            background: #e8eaf6 !important; /* Blue 50 */
            color: var(--primary);
        }

        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%233f51b5'%3e%3cpath d='M.293 4.293a1 1 0 011.414 0L8 10.586l6.293-6.293a1 1 0 111.414 1.414l-7 7a1 1 0 01-1.414 0l-7-7a1 1 0 010-1.414z'/%3e%3c/svg%3e");
        }

        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%233f51b5'%3e%3cpath d='M.293 11.707a1 1 0 001.414 0L8 5.414l6.293 6.293a1 1 0 001.414-1.414l-7-7a1 1 0 00-1.414 0l-7 7a1 1 0 000 1.414z'/%3e%3c/svg%3e");
        }

        .accordion-body {
            padding: 24px;
            background: #fafafa;
            border-radius: 0 0 8px 8px;
        }

        .form-control, .form-select {
            border: 1px solid rgba(0,0,0,0.38);
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: var(--surface);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(63,81,181,0.2);
            outline: none;
        }

        .form-control-container {
            position: relative;
            margin-bottom: 24px;
        }

        .form-control-container label {
            position: absolute;
            top: 12px;
            left: 16px;
            color: var(--text-secondary);
            font-size: 16px;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .form-control-container input:focus + label,
        .form-control-container input:not(:placeholder-shown) + label {
            top: -8px;
            left: 12px;
            font-size: 12px;
            color: var(--primary);
            background: var(--surface);
            padding: 0 4px;
        }

        .action-link {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            position: relative;
            padding: 4px 8px;
            border-radius: 4px;
            overflow: hidden;
            transition: background 0.3s ease;
        }

        .action-link:hover {
            background: rgba(63,81,181,0.12);
        }

        .action-link::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(63,81,181,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .action-link:active::after {
            width: 200px;
            height: 200px;
        }

        .fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--primary);
            color: white;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--elevation-shadow);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .fab:hover {
            box-shadow: var(--elevation-shadow-hover);
            transform: scale(1.1);
        }

        .fab i.fa-spin {
            animation: spin 1s linear infinite;
        }

        .loader {
            border: 4px solid #f5f5f5;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            margin: 16px auto;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 24px;
            }
            .content.collapsed {
                margin-left: 0;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-toggle {
                display: block;
                position: fixed;
                top: 16px;
                left: 16px;
                z-index: 1100;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 4px;
                padding: 12px;
                box-shadow: var(--elevation-shadow);
                transition: transform 0.3s ease;
            }
            .sidebar.open ~ .content .sidebar-toggle {
                left: 260px;
            }
            .kpi-card {
                margin-bottom: 24px;
            }
            .fab {
                bottom: 16px;
                right: 16px;
                width: 48px;
                height: 48px;
            }
            .accordion-button {
                font-size: 14px;
            }
            .accordion-body {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="container mx-auto">
            <h1 class="section-header">Traffic Citation Dashboard</h1>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                <div class="kpi-card total-citations">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div>
                        <div>
                            <h3>Total Citations</h3>
                            <p><?php echo number_format($total_citations); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card unpaid-citations">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div>
                            <h3>Unpaid Citations</h3>
                            <p><?php echo number_format($unpaid_citations); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card total-revenue">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div>
                            <h3>Total Revenue</h3>
                            <p>₱<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card new-this-month">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon"><i class="fas fa-plus-circle"></i></div>
                        <div>
                            <h3>New This Month</h3>
                            <p><?php echo number_format($new_this_month); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card expected-fine">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon"><i class="fas fa-money-check-alt"></i></div>
                        <div>
                            <h3>Expected Fine to be Collected</h3>
                            <p>₱<?php echo number_format($expected_fine, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card todays-revenue">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon"><i class="fas fa-calendar-day"></i></div>
                        <div>
                            <h3>Today's Revenue</h3>
                            <p>₱<?php echo number_format($todays_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Violations -->
            <h2 class="section-header">Top 5 Violations</h2>
            <div class="card-section">
                <ul class="list-unstyled">
                    <?php foreach ($top_violations as $v): ?>
                        <li class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span><?php echo htmlspecialchars($v['violation_type']); ?></span>
                            <span class="badge bg-primary rounded-pill"><?php echo number_format($v['cnt']); ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($top_violations)): ?>
                        <li class="text-center py-4 text-secondary">No violations recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Charts -->
            <h2 class="section-header">Analytics</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                <div class="card-section">
                    <h2 class="text-20 font-weight-500 mb-4">Monthly Revenue</h2>
                    <canvas id="revChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="card-section">
                    <h2 class="text-20 font-weight-500 mb-4">Citations by Vehicle Type</h2>
                    <canvas id="vehChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Search and Filter -->
            <h2 class="section-header">Search Citations</h2>
            <div class="card-section">
                <div class="d-flex flex-column flex-sm-row gap-4">
                    <div class="form-control-container flex-fill">
                        <input type="text" id="search" class="form-control w-100" placeholder=" " />
                        <label>Search citations</label>
                    </div>
                    <div class="d-flex gap-4 flex-fill">
                        <select id="filter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="Paid">Paid</option>
                            <option value="Unpaid">Unpaid</option>
                            <option value="Partially Paid">Partially Paid</option>
                        </select>
                        <input type="date" id="startDate" class="form-control" placeholder="Start Date">
                        <input type="date" id="endDate" class="form-control" placeholder="End Date">
                    </div>
                </div>
            </div>

            <!-- Recent Citations Accordion -->
            <h2 class="section-header">Recent Citations</h2>
            <div class="card-section">
                <div id="loading" class="loader"></div>
                <div class="accordion" id="citationsAccordion">
                    <?php foreach ($recent_citations as $index => $citation): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                    <div class="d-flex justify-content-between w-100 align-items-center">
                                        <span><?php echo htmlspecialchars($citation['ticket_number']); ?> - <?php echo htmlspecialchars($citation['last_name'] . ', ' . $citation['first_name']); ?></span>
                                        <span class="badge <?php echo $citation['payment_status'] === 'Paid' ? 'bg-success' : ($citation['payment_status'] === 'Partially Paid' ? 'bg-warning' : 'bg-danger'); ?> rounded-pill">
                                            <?php echo htmlspecialchars($citation['payment_status']); ?>
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#citationsAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6"><strong>Vehicle:</strong> <?php echo htmlspecialchars($citation['vehicle_type']); ?></div>
                                        <div class="col-md-6"><strong>Date:</strong> <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($citation['apprehension_datetime']))); ?></div>
                                        <div class="col-md-6"><strong>Location:</strong> <?php echo htmlspecialchars($citation['place_of_apprehension']); ?></div>
                                        <div class="col-md-6"><strong>Amount:</strong> ₱<?php echo number_format($citation['payment_amount'] ?? 0, 2); ?></div>
                                        <div class="col-md-12">
                                            <a href="view_citation.php?id=<?php echo htmlspecialchars($citation['citation_id']); ?>" class="action-link">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent_citations)): ?>
                        <div class="text-center py-4 text-secondary">No citations found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Floating Action Button -->
            <button id="refreshKpi" class="fab"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const searchInput = document.getElementById('search');
            const filterSelect = document.getElementById('filter');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const accordion = document.getElementById('citationsAccordion');
            const loading = document.getElementById('loading');
            const refreshBtn = document.getElementById('refreshKpi');

            // Sidebar Toggle
            sidebarToggle?.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('collapsed');
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('open');
                    sidebarToggle.style.left = sidebar.classList.contains('open') ? '260px' : '16px';
                }
            });

            // KPI Refresh
            refreshBtn.addEventListener('click', () => {
                refreshBtn.querySelector('i').classList.add('fa-spin');
                fetch('refresh_kpi.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    refreshBtn.querySelector('i').classList.remove('fa-spin');
                    document.querySelector('.kpi-card.total-citations p').textContent = data.total_citations.toLocaleString();
                    document.querySelector('.kpi-card.unpaid-citations p').textContent = data.unpaid_citations.toLocaleString();
                    document.querySelector('.kpi-card.total-revenue p').textContent = `₱${data.total_revenue.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                    document.querySelector('.kpi-card.new-this-month p').textContent = data.new_this_month.toLocaleString();
                    document.querySelector('.kpi-card.expected-fine p').textContent = `₱${data.expected_fine.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                    document.querySelector('.kpi-card.todays-revenue p').textContent = `₱${data.todays_revenue.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                })
                .catch(error => {
                    console.error('Refresh Error:', error);
                    refreshBtn.querySelector('i').classList.remove('fa-spin');
                });
            });

            // Charts
            const revCtx = document.getElementById('revChart').getContext('2d');
            new Chart(revCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_rev, 'month')); ?>,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: <?php echo json_encode(array_column($monthly_rev, 'total')); ?>,
                        borderColor: 'var(--primary)',
                        backgroundColor: 'rgba(63,81,181,0.2)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'var(--primary-dark)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: value => '₱' + value.toLocaleString() } },
                        x: { reverse: true }
                    },
                    plugins: {
                        legend: { labels: { font: { size: 14, family: 'Roboto', weight: '500' } } },
                        tooltip: { callbacks: { label: context => `₱${context.raw.toLocaleString()}` } }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuad'
                    }
                }
            });

            const vehCtx = document.getElementById('vehChart').getContext('2d');
            new Chart(vehCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($by_vehicle, 'vehicle_type')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($by_vehicle, 'cnt')); ?>,
                        backgroundColor: ['#3f51b5', '#4caf50', '#ff9800', '#f44336', '#9c27b0', '#00bcd4'],
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { font: { size: 12, family: 'Roboto', weight: '500' }, boxWidth: 12 } },
                        tooltip: { callbacks: { label: context => `${context.label}: ${context.raw}` } }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuad'
                    }
                }
            });

            // Search and Filter
            function filterTable() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                const filterStatus = filterSelect.value;
                const start = startDate.value;
                const end = endDate.value;
                loading.style.display = 'block';
                accordion.innerHTML = '';

                fetch('filter_citations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                    },
                    body: JSON.stringify({
                        search: searchTerm,
                        status: filterStatus,
                        startDate: start,
                        endDate: end
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    loading.style.display = 'none';
                    if (data.length === 0) {
                        accordion.innerHTML = '<div class="text-center py-4 text-secondary">No citations found.</div>';
                        return;
                    }
                    data.forEach((citation, index) => {
                        const accordionItem = document.createElement('div');
                        accordionItem.className = 'accordion-item';
                        accordionItem.innerHTML = `
                            <h2 class="accordion-header" id="heading${index}">
                                <button class="accordion-button ${index !== 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="collapse${index}">
                                    <div class="d-flex justify-content-between w-100 align-items-center">
                                        <span>${citation.ticket_number} - ${citation.last_name}, ${citation.first_name}</span>
                                        <span class="badge ${
                                            citation.payment_status === 'Paid' ? 'bg-success' :
                                            citation.payment_status === 'Partially Paid' ? 'bg-warning' :
                                            'bg-danger'
                                        } rounded-pill">
                                            ${citation.payment_status}
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="heading${index}" data-bs-parent="#citationsAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6"><strong>Vehicle:</strong> ${citation.vehicle_type}</div>
                                        <div class="col-md-6"><strong>Date:</strong> ${new Date(citation.apprehension_datetime).toLocaleString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                            hour: 'numeric',
                                            minute: 'numeric'
                                        })}</div>
                                        <div class="col-md-6"><strong>Location:</strong> ${citation.place_of_apprehension}</div>
                                        <div class="col-md-6"><strong>Amount:</strong> ₱${parseFloat(citation.payment_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</div>
                                        <div class="col-md-12">
                                            <a href="view_citation.php?id=${citation.citation_id}" class="action-link">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        accordion.appendChild(accordionItem);
                    });
                })
                .catch(error => {
                    loading.style.display = 'none';
                    console.error('Fetch Error:', error);
                    accordion.innerHTML = '<div class="text-center py-4 text-danger">Error loading citations.</div>';
                });
            }

            searchInput.addEventListener('input', filterTable);
            filterSelect.addEventListener('change', filterTable);
            startDate.addEventListener('change', filterTable);
            endDate.addEventListener('change', filterTable);
        });
    </script>
</body>
</html>