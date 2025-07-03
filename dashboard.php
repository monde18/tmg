<?php
session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Database connection
$host = '127.0.0.1';
$db = 'traffic_citation_db';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Data Fetch Functions
function getScalar($sql) {
    global $conn;
    $r = $conn->query($sql);
    $row = $r->fetch_array();
    return $row[0] ?? 0;
}

function getRows($sql) {
    global $conn;
    $r = $conn->query($sql);
    return $r->fetch_all(MYSQLI_ASSOC);
}

// KPI values
$total_citations = getScalar("SELECT COUNT(*) FROM citations");
$unpaid_citations = getScalar("SELECT COUNT(*) FROM citations WHERE payment_status = 'Unpaid' AND is_archived=0");
$total_revenue = getScalar("SELECT SUM(payment_amount) FROM citations WHERE payment_status='Paid'");
$new_this_month = getScalar("SELECT COUNT(*) FROM citations WHERE YEAR(apprehension_datetime)=YEAR(CURDATE()) AND MONTH(apprehension_datetime)=MONTH(CURDATE()) AND is_archived=0");
$avg_fine = getScalar("SELECT AVG(payment_amount) FROM citations WHERE payment_status='Paid' AND payment_amount>0");
$outstanding = getScalar("SELECT SUM(v.fine_amount*v.offense_count) FROM violations v JOIN citations c ON v.citation_id=c.citation_id WHERE c.payment_status='Unpaid' AND c.is_archived=0");
$top_violations = getRows("SELECT violation_type, COUNT(*) AS cnt FROM violations GROUP BY violation_type ORDER BY cnt DESC LIMIT 5");
$monthly_rev = getRows("SELECT DATE_FORMAT(payment_date,'%M %Y') AS month, SUM(payment_amount) AS total FROM citations WHERE payment_status='Paid' GROUP BY month ORDER BY payment_date DESC LIMIT 12");
$by_vehicle = getRows("SELECT veh.vehicle_type, COUNT(*) AS cnt FROM citations c JOIN vehicles veh ON c.vehicle_id=veh.vehicle_id GROUP BY veh.vehicle_type");

// Get recent citations
$sql = "
    SELECT c.*, d.last_name, d.first_name, v.vehicle_type
    FROM citations c
    JOIN drivers d ON c.driver_id = d.driver_id
    JOIN vehicles v ON c.vehicle_id = v.vehicle_id
    ORDER BY c.apprehension_datetime DESC
    LIMIT 10";
$recent_citations = getRows($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traffic Citation Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .kpi-card {
            transition: all 0.3s ease;
        }
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .card-section {
            transition: all 0.3s ease;
        }
        .card-section:hover {
            transform: translateY(-3px);
        }
        .table tbody tr {
            transition: all 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: #e0e7ff;
            transform: translateY(-2px);
        }
        .content {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            height: 100vh;
            margin-left: 260px;
            transition: margin-left 0.3s ease-in-out;
        }
        .content.collapsed {
            margin-left: 80px;
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
            }
            .content.collapsed {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content" id="content">
        <div class="container">
            <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
                <div class="kpi-card bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <i class="fas fa-ticket-alt text-blue-500 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Total Citations</h3>
                            <p class="text-2xl font-bold"><?php echo $total_citations; ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <i class="fas fa-money-bill-wave text-red-500 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Unpaid Citations</h3>
                            <p class="text-2xl font-bold"><?php echo $unpaid_citations; ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <i class="fas fa-dollar-sign text-green-500 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Total Revenue</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <i class="fas fa-journal-plus text-indigo-500 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">New This Month</h3>
                            <p class="text-2xl font-bold"><?php echo $new_this_month; ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <i class="fas fa-cash-stack text-teal-500 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Average Fine</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($avg_fine, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="kpi-card bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Outstanding Amount</h3>
                            <p class="text-2xl font-bold">₱<?php echo number_format($outstanding, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Violations -->
            <h2 class="text-2xl font-semibold mb-4">Top 5 Violations</h2>
            <div class="card-section bg-white p-6 rounded-lg shadow mb-8">
                <ul class="space-y-2">
                    <?php foreach($top_violations as $v): ?>
                        <li class="flex justify-between items-center py-2">
                            <span class="text-gray-700"><?php echo htmlspecialchars($v['violation_type']); ?></span>
                            <span class="bg-gray-200 text-gray-800 px-3 py-1 rounded-full"><?php echo $v['cnt']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="card-section bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-4">Monthly Revenue</h2>
                    <canvas id="revChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>
                <div class="card-section bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-4">Citations by Vehicle Type</h2>
                    <canvas id="vehChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <div class="relative">
                        <input type="text" id="search" class="w-64 p-2 pl-10 border rounded-lg" placeholder="Search citations...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <select id="filter" class="p-2 border rounded-lg">
                        <option value="all">All Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Unpaid">Unpaid</option>
                    </select>
                </div>
            </div>

            <!-- Recent Citations Table -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-left">Ticket #</th>
                            <th class="p-3 text-left">Driver</th>
                            <th class="p-3 text-left">Vehicle</th>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Location</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Amount</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="citations-table">
                        <?php foreach ($recent_citations as $citation): ?>
                            <tr class="border-t">
                                <td class="p-3"><?php echo htmlspecialchars($citation['ticket_number']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($citation['last_name'] . ', ' . $citation['first_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($citation['vehicle_type']); ?></td>
                                <td class="p-3"><?php echo date('M d, Y H:i', strtotime($citation['apprehension_datetime'])); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($citation['place_of_apprehension']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded <?php echo $citation['payment_status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $citation['payment_status']; ?>
                                    </span>
                                </td>
                                <td class="p-3">₱<?php echo number_format($citation['payment_amount'], 2); ?></td>
                                <td class="p-3">
                                    <a href="view_citation.php?id=<?php echo $citation['citation_id']; ?>" class="text-blue-500 hover:underline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const sidebarToggle = document.getElementById('sidebarToggle');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            } else {
                sidebar.classList.add('open');
            }
        });

        // Charts
        const revCtx = document.getElementById('revChart').getContext('2d');
        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_rev, 'month')); ?>,
                datasets: [{
                    label: '₱ Revenue',
                    data: <?php echo json_encode(array_column($monthly_rev, 'total')); ?>,
                    borderColor: '#1e40af',
                    backgroundColor: 'rgba(30, 58, 138, 0.2)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { callback: value => '₱' + value.toLocaleString() } } },
                plugins: {
                    legend: { labels: { font: { size: 14 } } },
                    tooltip: { callbacks: { label: context => `₱${context.raw.toLocaleString()}` } }
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
                    backgroundColor: ['#1e40af', '#10b981', '#f59e0b', '#dc2626', '#8b5cf6'],
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { font: { size: 12 }, boxWidth: 10 } },
                    tooltip: { callbacks: { label: context => `${context.label}: ${context.raw}` } }
                }
            }
        });

        // Search and Filter functionality
        const searchInput = document.getElementById('search');
        const filterSelect = document.getElementById('filter');
        const tableBody = document.getElementById('citations-table');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterStatus = filterSelect.value;

            fetch('filter_citations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    search: searchTerm,
                    status: filterStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                tableBody.innerHTML = '';
                data.forEach(citation => {
                    const row = document.createElement('tr');
                    row.className = 'border-t';
                    row.innerHTML = `
                        <td class="p-3">${citation.ticket_number}</td>
                        <td class="p-3">${citation.last_name}, ${citation.first_name}</td>
                        <td class="p-3">${citation.vehicle_type}</td>
                        <td class="p-3">${new Date(citation.apprehension_datetime).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric' })}</td>
                        <td class="p-3">${citation.place_of_apprehension}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded ${citation.payment_status === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${citation.payment_status}
                            </span>
                        </td>
                        <td class="p-3">₱${parseFloat(citation.payment_amount).toFixed(2)}</td>
                        <td class="p-3">
                            <a href="view_citation.php?id=${citation.citation_id}" class="text-blue-500 hover:underline">View</a>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            });
        }

        searchInput.addEventListener('input', filterTable);
        filterSelect.addEventListener('change', filterTable);
    </script>
</body>
</html>