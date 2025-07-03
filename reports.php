<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Traffic Citation Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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

        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }

        .filter-select, .date-input {
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

        .filter-select:focus, .date-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .report-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f1f5f9;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .report-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .table-responsive {
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th, td {
            padding: 0.75rem;
            border: 1px solid var(--border);
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            color: var(--primary);
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .no-data {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 1rem;
        }

        .loading {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1rem;
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        .btn-custom {
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #374151;
            transform: translateY(-1px);
        }

        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: none;
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header h1 { font-size: 1.25rem; }
            .header h4 { font-size: 0.8rem; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-select, .date-input { width: 100%; max-width: none; }
            .chart-container { height: 250px; }
        }

        @media print {
            .sidebar, .filter-section, .btn-custom { display: none; }
            .content { margin-left: 0; }
            .container { box-shadow: none; border: none; margin: 0; padding: 1rem; }
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
                <h1>Traffic Citation Reports</h1>
            </div>

            <div class="filter-section" role="region" aria-label="Report Filters">
                <select id="periodSelect" class="filter-select" aria-label="Select Report Period">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly" selected>Yearly</option>
                    <option value="custom">Custom Range</option>
                </select>
                <select id="yearSelect" class="filter-select" aria-label="Select Year"></select>
                <input type="date" id="startDate" class="date-input" style="display: none;" aria-label="Start Date">
                <input type="date" id="endDate" class="date-input" style="display: none;" aria-label="End Date">
                <select id="violationTypeSelect" class="filter-select" aria-label="Select Violation Type">
                    <option value="">All Violations</option>
                    <?php
                    try {
                        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $stmt = $conn->query("SELECT violation_type FROM violation_types ORDER BY violation_type");
                        $violation_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($violation_types as $type) {
                            echo "<option value=\"" . htmlspecialchars($type) . "\">" . htmlspecialchars($type) . "</option>";
                        }
                        $conn = null;
                    } catch (PDOException $e) {
                        error_log("Error fetching violation types: " . $e->getMessage());
                        echo "<option value=\"\">Error loading violation types</option>";
                    }
                    ?>
                </select>
                <select id="vehicleTypeSelect" class="filter-select" aria-label="Select Vehicle Type">
                    <option value="">All Vehicle Types</option>
                    <?php
                    try {
                        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $stmt = $conn->query("SELECT DISTINCT vehicle_type FROM vehicles WHERE vehicle_type IS NOT NULL ORDER BY vehicle_type");
                        $vehicle_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($vehicle_types as $type) {
                            echo "<option value=\"" . htmlspecialchars($type) . "\">" . htmlspecialchars($type) . "</option>";
                        }
                        $conn = null;
                    } catch (PDOException $e) {
                        error_log("Error fetching vehicle types: " . $e->getMessage());
                        echo "<option value=\"\">Error loading vehicle types</option>";
                    }
                    ?>
                </select>
                <button id="applyFilter" class="btn btn-primary btn-custom" title="Apply Filter" aria-label="Apply Filter"><i class="fas fa-filter"></i> Apply</button>
                <div id="filterError" class="error-message">Please provide valid filter values.</div>
            </div>

            <div id="loading" class="loading" style="display: none;" aria-live="polite">
                <i class="fas fa-spinner fa-2x"></i> Generating reports...
            </div>

            <!-- Most Common Violations -->
            <div class="report-section" id="violationsReport" role="region" aria-labelledby="violationsHeader">
                <h3 id="violationsHeader">Most Common Violations</h3>
                <div class="table-responsive">
                    <table id="violationsTable" aria-describedby="violationsHeader">
                        <thead>
                            <tr>
                                <th scope="col">Violation Type</th>
                                <th scope="col">Count</th>
                                <th scope="col">Total Fines (₱)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="chart-container">
                    <canvas id="violationsChart" aria-label="Bar chart of most common violations"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="violations" title="Export to CSV" aria-label="Export Violations to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="violations" title="Export to PDF" aria-label="Export Violations to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Barangays with Most Violations -->
            <div class="report-section" id="barangaysReport" role="region" aria-labelledby="barangaysHeader">
                <h3 id="barangaysHeader">Barangays with Most Violations</h3>
                <div class="table-responsive">
                    <table id="barangaysTable" aria-describedby="barangaysHeader">
                        <thead>
                            <tr>
                                <th scope="col">Barangay</th>
                                <th scope="col">Citation Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="chart-container">
                    <canvas id="barangaysChart" aria-label="Bar chart of barangays with most violations"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="barangays" title="Export to CSV" aria-label="Export Barangays to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="barangays" title="Export to PDF" aria-label="Export Barangays to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Payment Status Overview -->
            <div class="report-section" id="paymentStatusReport" role="region" aria-labelledby="paymentStatusHeader">
                <h3 id="paymentStatusHeader">Payment Status Overview</h3>
                <div class="table-responsive">
                    <table id="paymentStatusTable" aria-describedby="paymentStatusHeader">
                        <thead>
                            <tr>
                                <th scope="col">Status</th>
                                <th scope="col">Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="chart-container">
                    <canvas id="paymentStatusChart" aria-label="Pie chart of payment status"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="paymentStatus" title="Export to CSV" aria-label="Export Payment Status to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="paymentStatus" title="Export to PDF" aria-label="Export Payment Status to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Violation Trends -->
            <div class="report-section" id="trendsReport" role="region" aria-labelledby="trendsHeader">
                <h3 id="trendsHeader">Violation Trends Over Time</h3>
                <div class="chart-container">
                    <canvas id="trendsChart" aria-label="Line chart of violation trends over time"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="trends" title="Export to CSV" aria-label="Export Trends to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="trends" title="Export to PDF" aria-label="Export Trends to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Fine Revenue Over Time -->
            <div class="report-section" id="revenueReport" role="region" aria-labelledby="revenueHeader">
                <h3 id="revenueHeader">Fine Revenue Over Time</h3>
                <div class="chart-container">
                    <canvas id="revenueChart" aria-label="Line chart of fine revenue over time"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="revenue" title="Export to CSV" aria-label="Export Revenue to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="revenue" title="Export to PDF" aria-label="Export Revenue to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Vehicle Type Breakdown -->
            <div class="report-section" id="vehicleTypeReport" role="region" aria-labelledby="vehicleTypeHeader">
                <h3 id="vehicleTypeHeader">Vehicle Type Breakdown</h3>
                <div class="table-responsive">
                    <table id="vehicleTypeTable" aria-describedby="vehicleTypeHeader">
                        <thead>
                            <tr>
                                <th scope="col">Vehicle Type</th>
                                <th scope="col">Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="chart-container">
                    <canvas id="vehicleTypeChart" aria-label="Pie chart of vehicle types"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="vehicleType" title="Export to CSV" aria-label="Export Vehicle Types to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="vehicleType" title="Export to PDF" aria-label="Export Vehicle Types to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Top Apprehension Locations -->
            <div class="report-section" id="locationsReport" role="region" aria-labelledby="locationsHeader">
                <h3 id="locationsHeader">Top Apprehension Locations</h3>
                <div class="table-responsive">
                    <table id="locationsTable" aria-describedby="locationsHeader">
                        <thead>
                            <tr>
                                <th scope="col">Location</th>
                                <th scope="col">Citation Count</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="chart-container">
                    <canvas id="locationsChart" aria-label="Bar chart of top apprehension locations"></canvas>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="locations" title="Export to CSV" aria-label="Export Locations to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="locations" title="Export to PDF" aria-label="Export Locations to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>

            <!-- Repeat Offenders -->
            <div class="report-section" id="repeatOffendersReport" role="region" aria-labelledby="repeatOffendersHeader">
                <h3 id="repeatOffendersHeader">Repeat Offenders</h3>
                <div class="table-responsive">
                    <table id="repeatOffendersTable" aria-describedby="repeatOffendersHeader">
                        <thead>
                            <tr>
                                <th scope="col">Driver Name</th>
                                <th scope="col">License Number</th>
                                <th scope="col">Citation Count</th>
                                <th scope="col">Total Fines (₱)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button class="btn btn-secondary btn-custom export-csv" data-report="repeatOffenders" title="Export to CSV" aria-label="Export Repeat Offenders to CSV"><i class="fas fa-file-csv"></i> Export CSV</button>
                <button class="btn btn-secondary btn-custom export-pdf" data-report="repeatOffenders" title="Export to PDF" aria-label="Export Repeat Offenders to PDF"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const csrfToken = "<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>";
        let reportData = {};

        document.addEventListener('DOMContentLoaded', () => {
            const elements = {
                sidebar: document.getElementById('sidebar'),
                sidebarToggle: document.getElementById('sidebarToggle'),
                periodSelect: document.getElementById('periodSelect'),
                yearSelect: document.getElementById('yearSelect'),
                startDate: document.getElementById('startDate'),
                endDate: document.getElementById('endDate'),
                violationTypeSelect: document.getElementById('violationTypeSelect'),
                vehicleTypeSelect: document.getElementById('vehicleTypeSelect'),
                applyFilter: document.getElementById('applyFilter'),
                filterError: document.getElementById('filterError'),
                loading: document.getElementById('loading'),
                violationsTable: document.getElementById('violationsTable').querySelector('tbody'),
                violationsChart: document.getElementById('violationsChart').getContext('2d'),
                barangaysTable: document.getElementById('barangaysTable').querySelector('tbody'),
                barangaysChart: document.getElementById('barangaysChart').getContext('2d'),
                paymentStatusTable: document.getElementById('paymentStatusTable').querySelector('tbody'),
                paymentStatusChart: document.getElementById('paymentStatusChart').getContext('2d'),
                trendsChart: document.getElementById('trendsChart').getContext('2d'),
                revenueChart: document.getElementById('revenueChart').getContext('2d'),
                vehicleTypeTable: document.getElementById('vehicleTypeTable').querySelector('tbody'),
                vehicleTypeChart: document.getElementById('vehicleTypeChart').getContext('2d'),
                locationsTable: document.getElementById('locationsTable').querySelector('tbody'),
                locationsChart: document.getElementById('locationsChart').getContext('2d'),
                repeatOffendersTable: document.getElementById('repeatOffendersTable').querySelector('tbody')
            };

            let charts = {};

            // Sidebar toggle
            elements.sidebarToggle.addEventListener('click', () => {
                elements.sidebar.classList.toggle('open');
            });

            // Populate year select
            const currentYear = new Date().getFullYear();
            for (let year = currentYear - 5; year <= currentYear; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) option.selected = true;
                elements.yearSelect.appendChild(option);
            }

            // Toggle custom date inputs
            elements.periodSelect.addEventListener('change', () => {
                const isCustom = elements.periodSelect.value === 'custom';
                elements.startDate.style.display = isCustom ? 'block' : 'none';
                elements.endDate.style.display = isCustom ? 'block' : 'none';
                elements.yearSelect.style.display = isCustom ? 'none' : 'block';
                if (!isCustom) {
                    elements.startDate.value = '';
                    elements.endDate.value = '';
                }
            });

            // Fetch and render reports
            const fetchReports = () => {
                elements.filterError.style.display = 'none';
                const period = elements.periodSelect.value;
                const year = elements.yearSelect.value;
                const startDate = elements.startDate.value;
                const endDate = elements.endDate.value;

                if (period === 'custom' && (!startDate || !endDate)) {
                    elements.filterError.textContent = 'Please provide both start and end dates for custom range.';
                    elements.filterError.style.display = 'block';
                    elements.filterError.scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                if (period === 'custom' && new Date(startDate) > new Date(endDate)) {
                    elements.filterError.textContent = 'Start date cannot be after end date.';
                    elements.filterError.style.display = 'block';
                    elements.filterError.scrollIntoView({ behavior: 'smooth' });
                    return;
                }

                let dateFilter = '';
                dateFilter += period === 'custom' ? `&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}` : `&period=${encodeURIComponent(period)}&year=${encodeURIComponent(year)}`;
                dateFilter += elements.violationTypeSelect.value ? `&violation_type=${encodeURIComponent(elements.violationTypeSelect.value)}` : '';
                dateFilter += elements.vehicleTypeSelect.value ? `&vehicle_type=${encodeURIComponent(elements.vehicleTypeSelect.value)}` : '';

                console.log('Fetching reports with:', { period, year, startDate, endDate, violationType: elements.violationTypeSelect.value, vehicleType: elements.vehicleTypeSelect.value, csrfToken });
                elements.loading.style.display = 'block';
                fetch(`fetch_reports.php?csrf_token=${encodeURIComponent(csrfToken)}${dateFilter}`)
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(`HTTP error: ${response.status}, Response: ${text}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        elements.loading.style.display = 'none';
                        if (data.error) {
                            alert(`Error: ${data.error}`);
                            console.error('Data error:', data.error);
                            return;
                        }
                        console.log('Received data:', data);
                        reportData = data;
                        renderViolations(data.violations || []);
                        renderBarangays(data.barangays || []);
                        renderPaymentStatus(data.payment_status || []);
                        renderTrends(data.trends || []);
                        renderRevenue(data.revenue || []);
                        renderVehicleTypes(data.vehicle_types || []);
                        renderLocations(data.locations || []);
                        renderRepeatOffenders(data.repeat_offenders || []);
                    })
                    .catch(error => {
                        elements.loading.style.display = 'none';
                        alert('Error fetching reports. Please try again.');
                        console.error('Fetch error:', error);
                    });
            };

            // Render violations
            const renderViolations = (data) => {
                elements.violationsTable.innerHTML = '';
                if (!data.length) {
                    elements.violationsTable.innerHTML = '<tr><td colspan="3" class="no-data">No data available</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.violation_type || 'Unknown'}</td>
                            <td>${item.count || 0}</td>
                            <td>₱${Number(item.total_fines || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                        `;
                        elements.violationsTable.appendChild(row);
                    });
                }

                if (charts.violations) charts.violations.destroy();
                charts.violations = new Chart(elements.violationsChart, {
                    type: 'bar',
                    data: {
                        labels: data.length ? data.map(item => item.violation_type || 'Unknown') : ['No Data'],
                        datasets: [{
                            label: 'Violation Count',
                            data: data.length ? data.map(item => item.count || 0) : [0],
                            backgroundColor: 'rgba(30, 64, 175, 0.6)',
                            borderColor: 'rgba(30, 64, 175, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true, title: { display: true, text: 'Count' } } },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `${context.dataset.label}: ${context.raw} (₱${Number(data[context.dataIndex].total_fines || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })})`
                                }
                            }
                        },
                        accessibility: { description: 'Bar chart showing the count and total fines of the top 10 most common traffic violations' }
                    }
                });
            };

            // Render barangays
            const renderBarangays = (data) => {
                elements.barangaysTable.innerHTML = '';
                if (!data.length) {
                    elements.barangaysTable.innerHTML = '<tr><td colspan="2" class="no-data">No data available</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.barangay || 'Unknown'}</td>
                            <td>${item.count || 0}</td>
                        `;
                        elements.barangaysTable.appendChild(row);
                    });
                }

                if (charts.barangays) charts.barangays.destroy();
                charts.barangays = new Chart(elements.barangaysChart, {
                    type: 'bar',
                    data: {
                        labels: data.length ? data.map(item => item.barangay || 'Unknown') : ['No Data'],
                        datasets: [{
                            label: 'Citation Count',
                            data: data.length ? data.map(item => item.count || 0) : [0],
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true, title: { display: true, text: 'Citation Count' } } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.raw}` } }
                        },
                        accessibility: { description: 'Bar chart showing the citation count for the top 10 barangays with most violations' }
                    }
                });
            };

            // Render payment status
            const renderPaymentStatus = (data) => {
                elements.paymentStatusTable.innerHTML = '';
                if (!data.length) {
                    elements.paymentStatusTable.innerHTML = '<tr><td colspan="2" class="no-data">No data available</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.status || 'Unknown'}</td>
                            <td>${item.count || 0}</td>
                        `;
                        elements.paymentStatusTable.appendChild(row);
                    });
                }

                if (charts.paymentStatus) charts.paymentStatus.destroy();
                charts.paymentStatus = new Chart(elements.paymentStatusChart, {
                    type: 'pie',
                    data: {
                        labels: data.length ? data.map(item => item.status || 'Unknown') : ['No Data'],
                        datasets: [{
                            data: data.length ? data.map(item => item.count || 0) : [0],
                            backgroundColor: ['rgba(16, 185, 129, 0.6)', 'rgba(220, 38, 38, 0.6)', 'rgba(100, 116, 139, 0.6)'],
                            borderColor: ['rgba(16, 185, 129, 1)', 'rgba(220, 38, 38, 1)', 'rgba(100, 116, 139, 1)'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            title: { display: true, text: 'Payment Status Distribution' },
                            tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw}` } }
                        },
                        accessibility: { description: 'Pie chart showing the distribution of citation payment statuses (Paid vs Unpaid)' }
                    }
                });
            };

            // Render trends
            const renderTrends = (data) => {
                if (charts.trends) charts.trends.destroy();
                charts.trends = new Chart(elements.trendsChart, {
                    type: 'line',
                    data: {
                        labels: data.length ? data.map(item => item.period || 'Unknown') : ['No Data'],
                        datasets: [{
                            label: 'Citations',
                            data: data.length ? data.map(item => item.count || 0) : [0],
                            borderColor: 'rgba(59, 130, 246, 0.6)',
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Citation Count' } },
                            x: { title: { display: true, text: 'Period' } }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.raw}` } }
                        },
                        accessibility: { description: 'Line chart showing citation trends over time by selected period' }
                    }
                });
            };

            // Render revenue
            const renderRevenue = (data) => {
                if (charts.revenue) charts.revenue.destroy();
                charts.revenue = new Chart(elements.revenueChart, {
                    type: 'line',
                    data: {
                        labels: data.length ? data.map(item => item.period || 'Unknown') : ['No Data'],
                        datasets: [{
                            label: 'Total Fines (₱)',
                            data: data.length ? data.map(item => item.total_fines || 0) : [0],
                            borderColor: 'rgba(236, 72, 153, 0.6)',
                            backgroundColor: 'rgba(236, 72, 153, 0.2)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Total Fines (₱)' } },
                            x: { title: { display: true, text: 'Period' } }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `₱${Number(context.raw).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
                                }
                            }
                        },
                        accessibility: { description: 'Line chart showing total fine revenue over time by selected period' }
                    }
                });
            };

            // Render vehicle types
            const renderVehicleTypes = (data) => {
                elements.vehicleTypeTable.innerHTML = '';
                if (!data.length) {
                    elements.vehicleTypeTable.innerHTML = '<tr><td colspan="2" class="no-data">No data available</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.vehicle_type || 'Unknown'}</td>
                            <td>${item.count || 0}</td>
                        `;
                        elements.vehicleTypeTable.appendChild(row);
                    });
                }

                if (charts.vehicleType) charts.vehicleType.destroy();
                charts.vehicleType = new Chart(elements.vehicleTypeChart, {
                    type: 'pie',
                    data: {
                        labels: data.length ? data.map(item => item.vehicle_type || 'Unknown') : ['No Data'],
                        datasets: [{
                            data: data.length ? data.map(item => item.count || 0) : [0],
                            backgroundColor: [
                                '#1e40af', '#10b981', '#f59e0b', '#dc2626', '#6b7280',
                                '#8b5cf6', '#4b5563', '#ec4899', '#eab308', '#64748b'
                            ],
                            borderColor: '#ffffff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            title: { display: true, text: 'Vehicle Type Distribution' },
                            tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw}` } }
                        },
                        accessibility: { description: 'Pie chart showing the distribution of vehicle types in citations' }
                    }
                });
            };

            // Render locations
            const renderLocations = (data) => {
                elements.locationsTable.innerHTML = '';
                if (!data.length) {
                    elements.locationsTable.innerHTML = '<tr><td colspan="2" class="no-data">No data available</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.place_of_apprehension || 'Unknown'}</td>
                            <td>${item.count || 0}</td>
                        `;
                        elements.locationsTable.appendChild(row);
                    });
                }

                if (charts.locations) charts.locations.destroy();
                charts.locations = new Chart(elements.locationsChart, {
                    type: 'bar',
                    data: {
                        labels: data.length ? data.map(item => item.place_of_apprehension || 'Unknown') : ['No Data'],
                        datasets: [{
                            label: 'Citation Count',
                            data: data.length ? data.map(item => item.count || 0) : [0],
                            backgroundColor: 'rgba(245, 158, 11, 0.6)',
                            borderColor: 'rgba(245, 158, 11, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true, title: { display: true, text: 'Citation Count' } } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.raw}` } }
                        },
                        accessibility: { description: 'Bar chart showing the citation count for the top 10 apprehension locations' }
                    }
                });
            };

            // Render repeat offenders
            const renderRepeatOffenders = (data) => {
                elements.repeatOffendersTable.innerHTML = '';
                if (!data.length) {
                    elements.repeatOffendersTable.innerHTML = '<tr><td colspan="4" class="no-data">No data available</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.driver_name || 'Unknown'}</td>
                            <td>${item.license_number || 'N/A'}</td>
                            <td>${item.citation_count || 0}</td>
                            <td>₱${Number(item.total_fines || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                        `;
                        elements.repeatOffendersTable.appendChild(row);
                    });
                }
            };

            // Export CSV
            document.querySelectorAll('.export-csv').forEach(btn => {
                btn.addEventListener('click', () => {
                    const report = btn.dataset.report;
                    if (report === 'trends' || report === 'revenue') {
                        const data = reportData[report] || [];
                        let csv = report === 'trends' ? ['Period,Citation Count'] : ['Period,Total Fines (₱)'];
                        data.forEach(item => {
                            const value = report === 'trends' ? (item.count || 0) : Number(item.total_fines || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                            csv.push(`"${item.period || 'Unknown'}","${value}"`);
                        });
                        const bom = '\uFEFF';
                        const blob = new Blob([bom + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = `${report}_report_${new Date().toISOString().slice(0, 10)}.csv`;
                        link.click();
                    } else {
                        const table = document.getElementById(`${report}Table`);
                        if (!table) {
                            alert('Error: Table not found for export.');
                            return;
                        }
                        const rows = table.querySelectorAll('tr');
                        let csv = [];
                        rows.forEach(row => {
                            const cells = Array.from(row.cells).map(cell => {
                                let text = cell.textContent.trim().replace(/"/g, '""');
                                text = text.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
                                if (text.match(/^[+=@-]/)) text = `'${text}'`;
                                return `"${text}"`;
                            });
                            csv.push(cells.join(','));
                        });
                        const bom = '\uFEFF';
                        const blob = new Blob([bom + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = `${report}_report_${new Date().toISOString().slice(0, 10)}.csv`;
                        link.click();
                    }
                });
            });

            // Export PDF
            document.querySelectorAll('.export-pdf').forEach(btn => {
                btn.addEventListener('click', () => {
                    const report = btn.dataset.report;
                    const element = document.getElementById(`${report}Report`);
                    if (!element) {
                        alert('Error: Report section not found for export.');
                        return;
                    }
                    html2pdf().from(element).set({
                        margin: 10,
                        filename: `${report}_report_${new Date().toISOString().slice(0, 10)}.pdf`,
                        html2canvas: { scale: 2, useCORS: true },
                        jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
                    }).save();
                });
            });

            // Apply filter
            elements.applyFilter.addEventListener('click', fetchReports);

            // Initial load
            fetchReports();
        });
    </script>
</body>
</html>