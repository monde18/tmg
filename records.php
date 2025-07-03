<?php
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Traffic Citation Records</title>
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
      overflow: hidden;
      height: 100vh;
      display: flex;
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
      background-color: var(--primary-dark);
      transform: translateX(4px);
    }

    .sidebar a.active {
      background-color: #3b82f6;
      font-weight: 600;
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
        position: absolute;
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
      max-height: calc(100vh - 2rem);
      margin: 0 auto;
      padding: 1.5rem;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow-y: auto;
    }

    .container:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .header {
      background: linear-gradient(135deg, var(--primary), #3b82f6);
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
      font-size: 1rem;
      font-weight: 500;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      opacity: 0.85;
      margin-bottom: 0.25rem;
    }

    .header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      margin: 0;
    }

    .sort-filter {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
      align-items: center;
    }

    .sort-select, .search-input, .records-per-page {
      border-radius: 8px;
      border: 1px solid var(--border);
      padding: 0.5rem 0.75rem;
      font-size: 0.9rem;
      background-color: white;
      transition: all 0.3s ease;
      box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .sort-select, .records-per-page {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.5rem center;
      background-size: 1rem;
      min-width: 150px;
      max-width: 200px;
    }

    .search-input {
      flex: 1;
      max-width: 250px;
    }

    .sort-select:focus, .search-input:focus, .records-per-page:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .table-responsive {
      overflow-x: auto;
      border-radius: 8px;
      background-color: white;
      max-height: 50vh;
    }

    .table th {
      background-color: #f1f5f9;
      color: var(--primary);
      font-weight: 600;
      padding: 0.75rem;
      text-align: left;
      border-bottom: 2px solid var(--border);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      white-space: nowrap;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .table td {
      padding: 0.75rem;
      vertical-align: middle;
      border-bottom: 1px solid var(--border);
      font-size: 0.85rem;
      white-space: nowrap;
    }

    .table tr {
      transition: background-color 0.2s ease;
    }

    .table tr:hover {
      background-color: #f8fafc;
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
      line-height: 1.5;
    }

    .btn-primary {
      background-color: var(--primary);
      color: white;
      border: none;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-1px);
    }

    .btn-secondary {
      background-color: var(--secondary);
      color: white;
      border: none;
    }

    .btn-secondary:hover {
      background-color: #4b5563;
      transform: translateY(-1px);
    }

    .btn-archive {
      background-color: #9ca3af;
      color: white;
      border: none;
    }

    .btn-archive:hover {
      background-color: #6b7280;
      transform: translateY(-1px);
    }

    .btn-danger {
      background-color: var(--danger);
      color: white;
      border: none;
    }

    .btn-danger:hover {
      background-color: #dc2626;
      transform: translateY(-1px);
    }

    .btn-success {
      background-color: var(--success);
      color: white;
      border: none;
    }

    .btn-success:hover {
      background-color: #16a34a;
      transform: translateY(-1px);
    }

    .debug, .empty-state {
      color: var(--danger);
      background-color: #fef2f2;
      padding: 0.75rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      text-align: center;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .loading {
      text-align: center;
      padding: 1.5rem;
      color: var(--secondary);
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

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      align-items: center;
      justify-content: center;
      z-index: 2000;
    }

    .modal.show {
      display: flex;
      opacity: 1;
    }

    .modal-content {
      background-color: white;
      padding: 1.5rem;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
      position: relative;
      transform: scale(0.9);
      transition: transform 0.3s ease-in-out;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .modal.show .modal-content {
      transform: scale(1);
    }

    .close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--secondary);
      transition: color 0.2s ease;
    }

    .close:hover {
      color: var(--primary-dark);
    }

    .modal-content h2 {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 1rem;
    }

    .modal-content .driver-info, .modal-content .offense-table, .modal-content .payment-form {
      padding: 1rem;
      margin-bottom: 1rem;
    }

    .modal-content .driver-info {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }

    .modal-content .driver-info .photo-placeholder {
      width: 100px;
      height: 100px;
      background-color: var(--border);
      border-radius: 50%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      color: #6b7280;
    }

    .modal-content .driver-info .details {
      flex-grow: 1;
    }

    .modal-content .driver-info p {
      margin: 0.5rem 0;
      font-size: 0.9rem;
    }

    .modal-content .offense-table table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    .modal-content .offense-table th, .modal-content .offense-table td {
      padding: 0.5rem;
      border: 1px solid var(--border);
      text-align: left;
    }

    .modal-content .offense-table th {
      background-color: #f1f5f9;
      color: var(--primary);
      font-weight: 600;
    }

    .modal-content .offense-table .total-row {
      font-weight: 600;
      background-color: #e2e8f0;
    }

    .modal-content .payment-form input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      margin-bottom: 0.75rem;
      font-size: 0.9rem;
      transition: border-color 0.3s ease;
    }

    .modal-content .payment-form input:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .modal-content .payment-form p {
      margin: 0.5rem 0;
      font-size: 0.9rem;
    }

    .timeline-container {
      position: relative;
      padding: 1.5rem 0;
    }

    .timeline-item {
      position: relative;
      margin-bottom: 1.5rem;
      padding-left: 2rem;
      border-left: 3px solid var(--primary);
    }

    .timeline-item::before {
      content: '';
      position: absolute;
      left: -8px;
      top: 0;
      width: 14px;
      height: 14px;
      background-color: var(--primary);
      border-radius: 50%;
      border: 2px solid white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .timeline-item h5 {
      font-weight: 600;
      color: var(--primary);
      font-size: 1rem;
    }

    .timeline-item p {
      margin: 0.5rem 0;
      font-size: 0.9rem;
    }

    @keyframes spin {
      100% {
        transform: rotate(360deg);
      }
    }

    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 1.5rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .pagination {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .page-item {
      display: flex;
    }

    .page-link {
      padding: 0.5rem 1rem;
      border: 1px solid var(--border);
      background-color: white;
      color: var(--primary);
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .page-link:hover {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary-dark);
      transform: translateY(-1px);
    }

    .page-item.active .page-link {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary-dark);
      font-weight: 600;
    }

    .page-item.disabled .page-link {
      color: #9ca3af;
      cursor: not-allowed;
      background-color: #f8fafc;
      border-color: #e5e7eb;
    }

    .pagination-info {
      font-size: 0.9rem;
      color: var(--secondary);
    }

    .ellipsis {
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      color: var(--secondary);
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

      .sort-filter {
        flex-direction: column;
        align-items: stretch;
      }

      .sort-select, .search-input, .records-per-page {
        width: 100%;
        max-width: none;
      }

      .table th, .table td {
        font-size: 0.75rem;
        padding: 0.5rem;
      }

      .btn-custom {
        padding: 0.3rem 0.5rem;
        font-size: 0.75rem;
      }

      .modal-content {
        width: 95%;
        padding: 1rem;
        max-height: 70vh;
      }

      .modal-content .driver-info {
        flex-direction: column;
        text-align: center;
      }

      .modal-content .driver-info .photo-placeholder {
        margin: 0 auto 0.75rem;
      }

      .pagination-container {
        flex-direction: column;
        align-items: center;
      }
    }

    @media (max-width: 480px) {
      .table th, .table td {
        font-size: 0.7rem;
        padding: 0.4rem;
      }

      .btn-custom {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
      }

      .sort-select, .search-input, .records-per-page {
        font-size: 0.8rem;
      }

      .modal-content {
        padding: 0.75rem;
      }

      .page-link {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h3 class="text-lg font-semibold">Menu</h3>
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </div>
    <?php include 'sidebar.php'; ?>
  </div>

  <div class="content">
    <div class="container">
      <div class="header">
        <h4>Republic of the Philippines</h4>
        <h4>Province of Cagayan • Municipality of Baggao</h4>
        <h1>Traffic Citation Records</h1>
      </div>

      <div class="sort-filter">
        <a href="index.php" class="btn btn-primary btn-custom" aria-label="Add New Citation"><i class="fas fa-plus"></i> Add New Citation</a>
        <a href="driver_records.php" class="btn btn-primary btn-custom" aria-label="View Driver Records"><i class="fas fa-users"></i> View Driver Records</a>
        <a href="?show_archived=1" class="btn btn-secondary btn-custom" aria-label="View Archived Citations"><i class="fas fa-archive"></i> View Archived Citations</a>
        <select id="sortSelect" class="sort-select" aria-label="Sort Options">
          <option value="apprehension_desc">Sort by Date (Newest)</option>
          <option value="apprehension_asc">Sort by Date (Oldest)</option>
          <option value="ticket_asc">Sort by Ticket Number (Asc)</option>
          <option value="driver_asc">Sort by Driver Name (A-Z)</option>
          <option value="payment_asc">Sort by Payment Status (Unpaid)</option>
          <option value="payment_desc">Sort by Payment Status (Paid)</option>
        </select>
        <input type="text" id="searchInput" class="search-input" placeholder="Search by Driver Name or Ticket Number" aria-label="Search Citations">
        <select id="recordsPerPage" class="records-per-page" aria-label="Records Per Page">
          <option value="10">Show 10</option>
          <option value="20" selected>Show 20</option>
          <option value="30">Show 30</option>
          <option value="50">Show 50</option>
          <option value="100">Show 100</option>
        </select>
        <select id="bulkActions" class="sort-select" style="max-width: 150px;" aria-label="Bulk Actions">
          <option value="">Bulk Actions</option>
          <option value="archive">Archive Selected</option>
          <option value="unarchive">Unarchive Selected</option>
          <option value="delete">Delete Selected</option>
        </select>
        <button id="applyBulk" class="btn btn-primary btn-custom" aria-label="Apply Bulk Action">Apply</button>
        <button id="exportCSV" class="btn btn-secondary btn-custom" aria-label="Export to CSV"><i class="fas fa-file-csv"></i> Export to CSV</button>
        <button id="toggleView" class="btn btn-secondary btn-custom" aria-label="Toggle Timeline View"><i class="fas fa-stream"></i> Timeline View</button>
        <div class="dropdown">
          <button class="btn btn-secondary btn-custom dropdown-toggle" type="button" id="columnDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-columns"></i> Columns
          </button>
          <ul class="dropdown-menu" aria-labelledby="columnDropdown">
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="0" checked> Ticket Number</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="1" checked> Driver Name</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="2" checked> License Number</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="3" checked> Vehicle Plate</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="4" checked> Vehicle Type</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="5" checked> Apprehension Date</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="6" checked> Violations</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="7" checked> Payment Status</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="8" checked> Archiving Reason</label></li>
            <li><label class="dropdown-item"><input type="checkbox" class="column-toggle" data-column="9" checked> Actions</label></li>
          </ul>
        </div>
      </div>

      <div id="loading" class="loading" style="display: none;">
        <i class="fas fa-spinner fa-2x"></i> Loading citations...
      </div>

      <div id="citationTable" class="table-responsive">
        <?php
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 1;
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $recordsPerPage = isset($_GET['records_per_page']) ? intval($_GET['records_per_page']) : 20;
            $offset = ($page - 1) * $recordsPerPage;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            $query = "
                SELECT c.citation_id, c.ticket_number, 
                       COALESCE(CONCAT(d.last_name, ', ', d.first_name, 
                              IF(d.middle_initial != '', CONCAT(' ', d.middle_initial), ''), 
                              IF(d.suffix != '', CONCAT(' ', d.suffix), '')), 'Unknown') AS driver_name,
                       d.driver_id, d.license_number, d.zone, d.barangay, d.municipality, d.province, 
                       COALESCE(v.plate_mv_engine_chassis_no, 'N/A') AS plate_mv_engine_chassis_no, 
                       COALESCE(v.vehicle_type, 'N/A') AS vehicle_type, 
                       c.apprehension_datetime, c.payment_status,
                       GROUP_CONCAT(CONCAT(COALESCE(vl.violation_type, 'Unknown'), ' (Offense ', COALESCE(vl.offense_count, 1), ')') SEPARATOR ', ') AS violations,
                       vl2.violation_id IS NOT NULL AS is_tro,
                       r.remark_text AS archiving_reason,
                       COALESCE(SUM(
                           CASE COALESCE(vl.offense_count, 1)
                               WHEN 1 THEN COALESCE(vt.fine_amount_1, 200.00)
                               WHEN 2 THEN COALESCE(vt.fine_amount_2, 200.00)
                               WHEN 3 THEN COALESCE(vt.fine_amount_3, 200.00)
                               ELSE 200.00
                           END
                       ), 0) AS total_fine
                FROM citations c
                LEFT JOIN drivers d ON c.driver_id = d.driver_id
                LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
                LEFT JOIN violations vl ON c.citation_id = vl.citation_id
                LEFT JOIN violation_types vt ON vl.violation_type = vt.violation_type
                LEFT JOIN violations vl2 ON vl2.citation_id = c.citation_id AND vl2.violation_type = 'Traffic Restriction Order Violation'
                LEFT JOIN remarks r ON c.citation_id = r.citation_id
                WHERE c.is_archived = :is_archived
            ";
            $params = [':is_archived' => $show_archived ? 1 : 0];
            if ($search) {
                $query .= " AND (c.ticket_number LIKE :search OR COALESCE(CONCAT(d.last_name, ' ', d.first_name), '') LIKE :search)";
                $params[':search'] = "%$search%";
            }

            $allowedSorts = ['apprehension_desc', 'apprehension_asc', 'ticket_asc', 'driver_asc', 'payment_asc', 'payment_desc'];
            $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSorts) ? $_GET['sort'] : 'apprehension_desc';
            switch ($sort) {
                case 'apprehension_asc':
                    $query .= " GROUP BY c.citation_id ORDER BY c.apprehension_datetime ASC";
                    break;
                case 'ticket_asc':
                    $query .= " GROUP BY c.citation_id ORDER BY c.ticket_number ASC";
                    break;
                case 'driver_asc':
                    $query .= " GROUP BY c.citation_id ORDER BY d.last_name, d.first_name ASC";
                    break;
                case 'payment_asc':
                    $query .= " GROUP BY c.citation_id ORDER BY c.payment_status ASC";
                    break;
                case 'payment_desc':
                    $query .= " GROUP BY c.citation_id ORDER BY c.payment_status DESC";
                    break;
                case 'apprehension_desc':
                default:
                    $query .= " GROUP BY c.citation_id ORDER BY c.apprehension_datetime DESC";
                    break;
            }

            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $recordsPerPage;
            $params[':offset'] = $offset;

            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }

            error_log("Main Query: " . $query);
            error_log("Main Params: " . print_r($params, true));

            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo "<p class='empty-state'><i class='fas fa-info-circle'></i> No " . ($show_archived ? "archived" : "active") . " citations found.</p>";
            } else {
                echo "<table class='table table-bordered table-striped'>";
                echo "<thead>";
                echo "<tr>";
                echo "<th><input type='checkbox' id='selectAll' aria-label='Select All Citations'></th>";
                echo "<th><i class='fas fa-ticket-alt me-2'></i>Ticket Number</th>";
                echo "<th><i class='fas fa-user me-2'></i>Driver Name</th>";
                echo "<th><i class='fas fa-id-card me-2'></i>License Number</th>";
                echo "<th><i class='fas fa-car me-2'></i>Vehicle Plate</th>";
                echo "<th><i class='fas fa-car-side me-2'></i>Vehicle Type</th>";
                echo "<th><i class='fas fa-clock me-2'></i>Apprehension Date</th>";
                echo "<th><i class='fas fa-exclamation-triangle me-2'></i>Violations</th>";
                echo "<th><i class='fas fa-money-bill-wave me-2'></i>Payment Status</th>";
                echo "<th><i class='fas fa-info-circle me-2'></i>Archiving Reason</th>";
                echo "<th><i class='fas fa-cog me-2'></i>Actions</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                foreach ($rows as $row) {
                    echo "<tr>";
                    echo "<td><input type='checkbox' class='select-citation' value='" . $row['citation_id'] . "' aria-label='Select Citation'></td>";
                    echo "<td>" . htmlspecialchars($row['ticket_number']) . "</td>";
                    echo "<td><a href='#' class='driver-link text-primary' data-driver-id='" . $row['driver_id'] . "' data-zone='" . htmlspecialchars($row['zone'] ?? '') . "' data-barangay='" . htmlspecialchars($row['barangay'] ?? '') . "' data-municipality='" . htmlspecialchars($row['municipality'] ?? '') . "' data-province='" . htmlspecialchars($row['province'] ?? '') . "' aria-label='View Driver Details'>" . htmlspecialchars($row['driver_name']) . "</a></td>";
                    echo "<td>" . htmlspecialchars($row['license_number'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['plate_mv_engine_chassis_no']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['vehicle_type']) . "</td>";
                    echo "<td>" . ($row['apprehension_datetime'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($row['apprehension_datetime']))) : 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['violations'] ?? 'None') . "</td>";
                    echo "<td>" . ($row['payment_status'] == 'Paid' ? '<span class="badge bg-success">Paid</span>' : ($row['payment_status'] == 'Partially Paid' ? '<span class="badge bg-warning">Partially Paid</span>' : '<span class="badge bg-danger">Unpaid</span>')) . "</td>";
                    echo "<td>" . htmlspecialchars($row['archiving_reason'] ?? 'N/A') . "</td>";
                    echo "<td class='d-flex gap-2'>";
                    if (!$show_archived) {
                        echo "<a href='edit_citation.php?id=" . $row['citation_id'] . "' class='btn btn-sm btn-primary btn-custom' aria-label='Edit Citation'><i class='fas fa-edit'></i> Edit</a>";
                        echo "<a href='delete_citation.php?id=" . $row['citation_id'] . "' class='btn btn-sm btn-danger btn-custom' onclick='return confirm(\"Are you sure you want to delete this citation?\")' aria-label='Delete Citation'><i class='fas fa-trash'></i> Delete</a>";
                    }
                    $actionText = $show_archived ? "Unarchive" : "Archive";
                    $iconClass = $show_archived ? "fa-box-open" : "fa-archive";
                    echo "<button class='btn btn-sm btn-archive archive-btn' data-id='" . $row['citation_id'] . "' data-action='" . ($show_archived ? 0 : 1) . "' data-is-tro='" . ($row['is_tro'] ? '1' : '0') . "' aria-label='$actionText Citation'><i class='fas " . $iconClass . "'></i> $actionText</button>";
                    if ($row['payment_status'] == 'Unpaid' && !$show_archived) {
                        echo "<a href='#' class='btn btn-sm btn-success btn-custom pay-now' data-citation-id='" . $row['citation_id'] . "' data-driver-id='" . $row['driver_id'] . "' data-zone='" . htmlspecialchars($row['zone'] ?? '') . "' data-barangay='" . htmlspecialchars($row['barangay'] ?? '') . "' data-municipality='" . htmlspecialchars($row['municipality'] ?? '') . "' data-province='" . htmlspecialchars($row['province'] ?? '') . "' data-license-number='" . htmlspecialchars($row['license_number'] ?? '') . "' aria-label='Pay Citation'><i class='fas fa-credit-card'></i> Pay Now</a>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
            }
        } catch(PDOException $e) {
            echo "<p class='debug'><i class='fas fa-exclamation-circle'></i> Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            error_log("PDOException: " . $e->getMessage());
        }
        $conn = null;
        ?>
      </div>

      <?php
      try {
          $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
          $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          $countQuery = "SELECT COUNT(DISTINCT c.citation_id) as total 
                        FROM citations c 
                        LEFT JOIN drivers d ON c.driver_id = d.driver_id
                        WHERE c.is_archived = :is_archived";
          $params = [':is_archived' => $show_archived ? 1 : 0];
          if ($search) {
              $countQuery .= " AND (c.ticket_number LIKE :search OR COALESCE(CONCAT(d.last_name, ' ', d.first_name), '') LIKE :search)";
              $params[':search'] = "%$search%";
          }

          $countStmt = $conn->prepare($countQuery);
          foreach ($params as $key => $value) {
              $countStmt->bindValue($key, $value, PDO::PARAM_STR);
          }

          error_log("Count Query: " . $countQuery);
          error_log("Count Params: " . print_r($params, true));

          $countStmt->execute();
          $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
          $totalPages = ceil($totalRecords / $recordsPerPage);
      } catch(PDOException $e) {
          echo "<p class='debug'><i class='fas fa-exclamation-circle'></i> Error: " . htmlspecialchars($e->getMessage()) . "</p>";
          error_log("PDOException: " . $e->getMessage());
          $totalRecords = 0;
          $totalPages = 1;
      }
      $conn = null;
      ?>
    </div>

    <div class="pagination-container" id="paginationContainer" data-total-records="<?php echo $totalRecords; ?>" data-total-pages="<?php echo $totalPages; ?>" data-current-page="<?php echo $page; ?>" data-records-per-page="<?php echo $recordsPerPage; ?>">
      <div class="pagination-info">
        Showing <span id="recordStart"><?php echo $offset + 1; ?></span> to <span id="recordEnd"><?php echo min($offset + $recordsPerPage, $totalRecords); ?></span> of <span id="totalRecords"><?php echo $totalRecords; ?></span> citations
      </div>
      <nav aria-label="Page navigation">
        <ul class="pagination" id="pagination"></ul>
      </nav>
    </div>

    <div id="timelineView" style="display: none;">
      <div class="timeline-container"></div>
    </div>
  </div>
  </div>

  <div id="archiveModal" class="modal" role="dialog" aria-labelledby="archiveModalTitle" aria-hidden="true">
    <div class="modal-content">
      <span class="close" aria-label="Close Archive Modal">×</span>
      <h2 id="archiveModalTitle">Remarks Note: Reason for Archiving</h2>
      <input type="text" id="remarksReason" class="form-control mb-3" placeholder="Enter reason for archiving/unarchiving (max 255 characters)" maxlength="255" aria-label="Reason for Archiving">
      <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
      <button id="confirmArchive" class="btn btn-primary btn-custom" aria-label="Confirm Archiving">Confirm</button>
      <button id="cancelArchive" class="btn btn-secondary btn-custom" aria-label="Cancel Archiving">Cancel</button>
    </div>
  </div>

  <div id="driverInfoModal" class="modal" role="dialog" aria-labelledby="driverInfoTitle" aria-hidden="true">
    <div class="modal-content">
      <span class="close" aria-label="Close Driver Info Modal">×</span>
      <h2 id="driverInfoTitle">Driver Information</h2>
      <div class="driver-info">
        <div class="photo-placeholder">No Photo</div>
        <div class="details">
          <p><strong>License Number:</strong> <span id="licenseNumber"></span></p>
          <p><strong>Name:</strong> <span id="driverName"></span></p>
          <p><strong>Address:</strong> <span id="driverAddress"></span></p>
          <p><strong>Total Fines:</strong> <span id="totalFines">₱0.00</span></p>
        </div>
      </div>
      <div class="offense-table">
        <h3>Offense Records</h3>
        <table>
          <thead>
            <tr>
              <th>Date/Time</th>
              <th>Offense</th>
              <th>Fine</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="offenseRecords"></tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="2"><strong>Total</strong></td>
              <td><strong id="totalFineDisplay">₱0.00</strong></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <button id="printModal" class="btn btn-secondary btn-custom" aria-label="Print Driver Info"><i class="fas fa-print"></i> Print</button>
      <button id="closeModal" class="btn btn-primary btn-custom" aria-label="Close Driver Info Modal">Close</button>
    </div>
  </div>

  <div id="paymentModal" class="modal" role="dialog" aria-labelledby="paymentModalTitle" aria-hidden="true">
    <div class="modal-content">
      <span class="close" aria-label="Close Payment Modal">×</span>
      <h2 id="paymentModalTitle">Payment Processing</h2>
      <div class="driver-info">
        <div class="photo-placeholder">No Photo</div>
        <div class="details">
          <p><strong>License Number:</strong> <span id="paymentLicenseNumber"></span></p>
          <p><strong>Name:</strong> <span id="paymentDriverName"></span></p>
          <p><strong>Address:</strong> <span id="paymentDriverAddress"></span></p>
          <p><strong>Total Fines:</strong> <span id="paymentTotalFines">₱0.00</span></p>
        </div>
      </div>
      <div class="offense-table">
        <h3>Offense Records</h3>
        <table>
          <thead>
            <tr>
              <th>Select</th>
              <th>Date/Time</th>
              <th>Offense</th>
              <th>Fine</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="paymentOffenseRecords"></tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="2"><strong>Total</strong></td>
              <td><strong id="paymentTotalFineDisplay">₱0.00</strong></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="payment-form">
        <h3>Payment Details</h3>
        <p><strong>Amount Due:</strong> <span id="amountDue">₱0.00</span></p>
        <input type="number" id="cashInput" step="0.01" min="0" placeholder="Enter cash amount" required aria-label="Cash Amount">
        <p><strong>Change:</strong> <span id="changeDisplay">₱0.00</span></p>
        <div id="paymentError" class="alert alert-danger" style="display: none;"></div>
        <button id="confirmPayment" class="btn btn-success btn-custom" aria-label="Confirm Payment">Confirm Payment</button>
        <button id="cancelPayment" class="btn btn-secondary btn-custom" aria-label="Cancel Payment">Cancel</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
const showArchived = <?php echo $show_archived ? '1' : '0'; ?>;

document.addEventListener('DOMContentLoaded', () => {
  const loadingDiv = document.getElementById('loading');
  const citationTable = document.getElementById('citationTable');
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const content = document.querySelector('.content');

  const showModal = (modal) => {
    if (document.querySelectorAll('.modal.show').length > 0) return; // Prevent multiple modals
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
  };

  const hideModal = (modal) => {
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
  };

  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    content.style.marginLeft = sidebar.classList.contains('open') ? '200px' : '0';
  });

  loadingDiv.style.display = 'block';
  citationTable.style.opacity = '0';
  setTimeout(() => {
    loadingDiv.style.display = 'none';
    citationTable.style.opacity = '1';
  }, 300);

  const updateRowHoverEffects = () => {
    const rows = document.querySelectorAll('.table tr');
    rows.forEach(row => {
      row.addEventListener('mouseenter', () => row.style.cursor = 'pointer');
      row.addEventListener('mouseleave', () => row.style.cursor = 'default');
    });
  };
  updateRowHoverEffects();

  const sortSelect = document.getElementById('sortSelect');
  const searchInput = document.getElementById('searchInput');
  const recordsPerPageSelect = document.getElementById('recordsPerPage');
  const urlParams = new URLSearchParams(window.location.search);
  const sortParam = urlParams.get('sort') || 'apprehension_desc';
  const searchParam = urlParams.get('search') || '';
  const recordsPerPage = urlParams.get('records_per_page') || '20';
  sortSelect.value = sortParam;
  searchInput.value = searchParam;
  recordsPerPageSelect.value = recordsPerPage;
  let currentPage = parseInt(urlParams.get('page')) || 1;

  function fetchTableData(search, sort, showArchived, page, recordsPerPage) {
    loadingDiv.style.display = 'block';
    citationTable.style.opacity = '0';
    const params = new URLSearchParams({
      search: encodeURIComponent(search),
      sort: encodeURIComponent(sort),
      show_archived: encodeURIComponent(showArchived),
      page: encodeURIComponent(page),
      records_per_page: encodeURIComponent(recordsPerPage),
      csrf_token: encodeURIComponent(csrfToken)
    });
    fetch('fetch_citations.php?' + params.toString(), {
      method: 'GET',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => {
      if (!response.ok) {
        return response.text().then(text => {
          throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
        });
      }
      return response.text();
    })
    .then(data => {
      if (data.trim() === '') {
        citationTable.innerHTML = "<p class='empty-state'><i class='fas fa-info-circle'></i> No citations found.</p>";
      } else {
        citationTable.innerHTML = data;
      }
      loadingDiv.style.display = 'none';
      citationTable.style.opacity = '1';
      updateRowHoverEffects();
      attachEventListeners();
      updatePagination(page, parseInt(recordsPerPage));
    })
    .catch(error => {
      loadingDiv.style.display = 'none';
      citationTable.innerHTML = `<p class='debug'><i class='fas fa-exclamation-circle'></i> Error: ${error.message}</p>`;
      console.error('Fetch error:', error);
    });
  }

  fetchTableData(searchParam, sortParam, showArchived, currentPage, recordsPerPage);

  sortSelect.addEventListener('change', () => {
    currentPage = 1;
    fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
  });

  searchInput.addEventListener('input', debounce(() => {
    currentPage = 1;
    fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
  }, 500));

  recordsPerPageSelect.addEventListener('change', () => {
    currentPage = 1;
    fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
  });

  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  function updatePagination(currentPage, recordsPerPage) {
    const paginationContainer = document.getElementById('paginationContainer');
    const pagination = document.getElementById('pagination');
    const totalRecords = parseInt(paginationContainer.dataset.totalRecords);
    const totalPages = parseInt(paginationContainer.dataset.totalPages);
    const maxPagesToShow = 5;

    pagination.innerHTML = '';

    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">«</a>`;
    pagination.appendChild(prevLi);

    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    if (endPage - startPage < maxPagesToShow - 1) {
      startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    if (startPage > 1) {
      const firstPage = document.createElement('li');
      firstPage.className = 'page-item';
      firstPage.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
      pagination.appendChild(firstPage);

      if (startPage > 2) {
        const ellipsis = document.createElement('li');
        ellipsis.className = 'page-item disabled';
        ellipsis.innerHTML = `<span class="ellipsis">...</span>`;
        pagination.appendChild(ellipsis);
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      const pageLi = document.createElement('li');
      pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
      pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
      pagination.appendChild(pageLi);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        const ellipsis = document.createElement('li');
        ellipsis.className = 'page-item disabled';
        ellipsis.innerHTML = `<span class="ellipsis">...</span>`;
        pagination.appendChild(ellipsis);
      }

      const lastPage = document.createElement('li');
      lastPage.className = 'page-item';
      lastPage.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
      pagination.appendChild(lastPage);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">»</a>`;
    pagination.appendChild(nextLi);

    const recordStart = (currentPage - 1) * recordsPerPage + 1;
    const recordEnd = Math.min(currentPage * recordsPerPage, totalRecords);
    document.getElementById('recordStart').textContent = recordStart;
    document.getElementById('recordEnd').textContent = recordEnd;
    document.getElementById('totalRecords').textContent = totalRecords;

    document.querySelectorAll('.page-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const page = parseInt(link.getAttribute('data-page'));
        if (page && !link.parentElement.classList.contains('disabled')) {
          currentPage = page;
          fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
        }
      });
    });
  }

  updatePagination(currentPage, parseInt(recordsPerPageSelect.value));

  const archiveModal = document.getElementById('archiveModal');
  const closeArchiveModal = document.getElementById('cancelArchive');
  const confirmArchive = document.getElementById('confirmArchive');
  const remarksReason = document.getElementById('remarksReason');
  const errorMessage = document.getElementById('errorMessage');
  let currentCitationId = null;
  let currentAction = null;
  let isTRO = null;

  const attachEventListeners = () => {
    document.querySelectorAll('.archive-btn').forEach(button => {
      button.addEventListener('click', () => {
        currentCitationId = button.getAttribute('data-id');
        currentAction = button.getAttribute('data-action');
        isTRO = button.getAttribute('data-is-tro') === '1';
        showModal(archiveModal);
        remarksReason.value = '';
        errorMessage.style.display = 'none';
        remarksReason.focus();
        if (isTRO) {
          remarksReason.setAttribute('required', 'required');
          document.querySelector('#archiveModal h2').textContent = 'Remarks Note: Reason for TRO Archiving';
        } else {
          remarksReason.removeAttribute('required');
          document.querySelector('#archiveModal h2').textContent = 'Remarks Note: Reason for Archiving';
        }
      });
    });

    document.querySelectorAll('.driver-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const driverId = link.getAttribute('data-driver-id');
        const zone = link.getAttribute('data-zone');
        const barangay = link.getAttribute('data-barangay');
        const municipality = link.getAttribute('data-municipality');
        const province = link.getAttribute('data-province');

        loadingDiv.style.display = 'block';
        fetch(`fetch_payments.php?driver_id=${encodeURIComponent(driverId)}&csrf_token=${encodeURIComponent(csrfToken)}`, {
          headers: { 'Accept': 'application/json' }
        })
          .then(response => {
            if (!response.ok) {
              return response.text().then(text => {
                throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
              });
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              throw new Error(`Unexpected response format: ${contentType || 'unknown'}`);
            }
            return response.json();
          })
          .then(data => {
            loadingDiv.style.display = 'none';
            if (data.error) {
              throw new Error(data.error);
            }

            const driver = data.rows[0] || {};
            document.getElementById('licenseNumber').textContent = driver.license_number || 'N/A';
            document.getElementById('driverName').textContent = driver.driver_name || 'N/A';
            document.getElementById('driverAddress').textContent = `${zone ? zone + ', ' : ''}${barangay ? barangay + ', ' : ''}${municipality}, ${province}`;
            const offenseTable = document.getElementById('offenseRecords');
            offenseTable.innerHTML = '';
            let totalFine = 0;
            (data.rows[0]?.violations || '').split(', ').forEach(viol => {
              const [type, count] = viol.match(/^(.*?)\s*\(Offense\s*(\d+)\)$/) || [viol, 1];
              const fine = parseFloat(data.rows[0]?.total_fine) / (data.rows[0]?.violations.split(', ').length || 1) || 0;
              totalFine += fine;
              const row = document.createElement('tr');
              row.innerHTML = `
                <td>${data.rows[0]?.apprehension_datetime ? new Date(data.rows[0].apprehension_datetime).toLocaleString() : 'N/A'}</td>
                <td>${type}</td>
                <td>₱${fine.toFixed(2)}</td>
                <td>${data.rows[0]?.payment_status || 'Unpaid'}</td>
              `;
              offenseTable.appendChild(row);
            });
            document.getElementById('totalFines').textContent = `₱${totalFine.toFixed(2)}`;
            document.getElementById('totalFineDisplay').textContent = `₱${totalFine.toFixed(2)}`;

            const modal = document.getElementById('driverInfoModal');
            showModal(modal);
          })
          .catch(error => {
            loadingDiv.style.display = 'none';
            document.getElementById('licenseNumber').textContent = 'Error';
            document.getElementById('offenseRecords').innerHTML = `<tr><td colspan="4">Error loading data: ${error.message}</td></tr>`;
            console.error('Fetch error:', error);
          });
      });

    document.querySelectorAll('.pay-now').forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const citationId = button.getAttribute('data-citation-id');
        const driverId = button.getAttribute('data-driver-id');
        const zone = button.getAttribute('data-zone');
        const barangay = button.getAttribute('data-barangay');
        const municipality = button.getAttribute('data-municipality');
        const province = button.getAttribute('data-province');
        const licenseNumber = button.getAttribute('data-license-number');

        loadingDiv.style.display = 'block';
        fetch(`fetch_payments.php?citation_id=${encodeURIComponent(citationId)}&driver_id=${encodeURIComponent(driverId)}&csrf_token=${encodeURIComponent(csrfToken)}`, {
          headers: { 'Accept': 'application/json' }
        })
          .then(response => {
            if (!response.ok) {
              return response.text().then(text => {
                throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
              });
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              throw new Error(`Unexpected response format: ${contentType || 'unknown'}`);
            }
            return response.json();
          })
          .then(data => {
            loadingDiv.style.display = 'none';
            if (data.error) {
              throw new Error(data.error);
            }

            document.getElementById('paymentLicenseNumber').textContent = licenseNumber || 'N/A';
            document.getElementById('paymentDriverName').textContent = button.closest('tr').querySelector('.driver-link').textContent || 'N/A';
            document.getElementById('paymentDriverAddress').textContent = `${zone ? zone + ', ' : ''}${barangay ? barangay + ', ' : ''}${municipality}, ${province}`;
            
            const offenseTable = document.getElementById('paymentOffenseRecords');
            offenseTable.innerHTML = '';
            let totalFine = 0;
            let unpaidFine = 0;

            data.offenses.forEach(offense => {
              const fine = parseFloat(offense.fine) || 0;
              totalFine += fine;
              const isPaid = offense.payment_status === 'Paid';
              if (!isPaid) unpaidFine += fine;
              const row = document.createElement('tr');
              row.innerHTML = `
                <td><input type="checkbox" class="violation-checkbox" data-violation-id="${offense.violation_id}" data-fine="${fine}" ${isPaid ? 'disabled' : 'checked'}></td>
                <td>${offense.date || 'N/A'}</td>
                <td>${offense.violation_type || 'Unknown'} ${offense.offense_count ? '(Offense ' + offense.offense_count + ')' : ''}</td>
                <td>₱${fine.toFixed(2)}</td>
                <td>${isPaid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-danger">Unpaid</span>'}</td>
              `;
              offenseTable.appendChild(row);
            });

            document.getElementById('paymentTotalFines').textContent = `₱${totalFine.toFixed(2)}`;
            document.getElementById('paymentTotalFineDisplay').textContent = `₱${totalFine.toFixed(2)}`;
            document.getElementById('amountDue').textContent = `₱${unpaidFine.toFixed(2)}`;

            const cashInput = document.getElementById('cashInput');
            const changeDisplay = document.getElementById('changeDisplay');
            const paymentError = document.getElementById('paymentError');

            cashInput.value = '';
            changeDisplay.textContent = '₱0.00';
            paymentError.style.display = 'none';

            const newCashInput = cashInput.cloneNode(true);
            cashInput.parentNode.replaceChild(newCashInput, cashInput);

            newCashInput.addEventListener('input', () => {
              const cash = parseFloat(newCashInput.value) || 0;
              const change = cash - unpaidFine;
              changeDisplay.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
              if (change < 0) {
                paymentError.textContent = 'Insufficient cash amount.';
                paymentError.style.display = 'block';
              } else {
                paymentError.style.display = 'none';
              }
            });

            const paymentModal = document.getElementById('paymentModal');
            paymentModal.dataset.citationId = citationId;
            showModal(paymentModal);
          })
          .catch(error => {
            loadingDiv.style.display = 'none';
            alert('Error loading citation data: ' + error.message);
            console.error('Pay Now fetch error for citationId ' + citationId + ':', error);
          });
      });

      closeArchiveModal.addEventListener('click', () => {
        hideModal(archiveModal);
        errorMessage.style.display = 'none';
      });

      confirmArchive.addEventListener('click', () => {
        const reason = remarksReason.value.trim();
        errorMessage.style.display = 'none';

        if (isTRO && !reason) {
          errorMessage.textContent = 'A remarks note is required for archiving/unarchiving a TRO.';
          errorMessage.style.display = 'block';
          return;
        }

        if (reason.length > 255) {
          errorMessage.textContent = 'Remarks note exceeds 255 characters.';
          errorMessage.style.display = 'block';
          return;
        }

        fetch('archive_citation.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `id=${encodeURIComponent(currentCitationId)}&archive=${encodeURIComponent(currentAction)}&remarksReason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          alert(data.message);
          if (data.status === 'success') {
            fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
          }
        })
        .catch(error => {
          alert('Error archiving citation: ' + error.message);
        });

        hideModal(archiveModal);
      });

      let isOutsideClick = false;
      window.addEventListener('click', (event) => {
        if (event.target === archiveModal && !isOutsideClick) {
          isOutsideClick = true;
          hideModal(archiveModal);
          errorMessage.style.display = 'none';
        } else {
          isOutsideClick = false;
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && archiveModal.classList.contains('show')) {
          hideModal(archiveModal);
          errorMessage.style.display = 'none';
        }
      });

      document.getElementById('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.select-citation').forEach(checkbox => {
          checkbox.checked = e.target.checked;
        });
      });

      document.getElementById('applyBulk').addEventListener('click', () => {
        const action = document.getElementById('bulkActions').value;
        if (!action) return alert('Please select an action.');

        const selected = Array.from(document.querySelectorAll('.select-citation:checked')).map(checkbox => checkbox.value);
        if (selected.length === 0) return alert('Please select at least one citation.');

        if (action === 'delete' && !confirm('Are you sure you want to delete the selected citations?')) return;

        fetch('bulk_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=${encodeURIComponent(action)}&ids=${encodeURIComponent(JSON.stringify(selected))}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          alert(data.message);
          if (data.status === 'success') fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
        })
        .catch(error => alert('Error: ' + error.message));
      });

      document.getElementById('exportCSV').addEventListener('click', () => {
        const rows = document.querySelectorAll('#citationTable table tr');
        let csv = [];
        const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent.trim());
        csv.push(headers.join(','));

        for (let i = 1; i < rows.length; i++) {
          const cols = Array.from(rows[i].querySelectorAll('td')).map(td => {
            let text = td.textContent.trim().replace(/"/g, '""');
            if (text.match(/^[+=@-]/)) text = `'${text}`;
            return `"${text}"`;
          });
          csv.push(cols.join(','));
        }

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Traffic_Citation_Records.csv';
        link.click();
      });

      document.getElementById('toggleView').addEventListener('click', () => {
        const tableView = document.querySelector('#citationTable table');
        const timelineView = document.getElementById('timelineView');
        if (tableView && tableView.style.display !== 'none') {
          tableView.style.display = 'none';
          timelineView.style.display = 'block';
          document.getElementById('toggleView').innerHTML = '<i class="fas fa-table"></i> Table View';

          const rows = document.querySelectorAll('#citationTable table tbody tr');
          const timelineContainer = timelineView.querySelector('.timeline-container');
          timelineContainer.innerHTML = '';
          rows.forEach(row => {
            const cols = row.querySelectorAll('td');
            const item = document.createElement('div');
            item.className = 'timeline-item';
            item.innerHTML = `
              <h5>${cols[1].textContent} - ${cols[2].textContent}</h5>
              <p><strong>Date:</strong> ${cols[6].textContent}</p>
              <p><strong>Violations:</strong> ${cols[7].textContent}</p>
              <p><strong>Vehicle:</strong> ${cols[4].textContent} (${cols[5].textContent})</p>
            `;
            timelineContainer.appendChild(item);
          });
        } else {
          tableView.style.display = 'block';
          timelineView.style.display = 'none';
          document.getElementById('toggleView').innerHTML = '<i class="fas fa-stream"></i> Timeline View';
        }
      });

      document.getElementById('closeModal').addEventListener('click', () => {
        hideModal(document.getElementById('driverInfoModal'));
      });

      document.getElementById('printModal').addEventListener('click', () => {
        window.print();
      });

      document.querySelector('#driverInfoModal .close').addEventListener('click', () => {
        hideModal(document.getElementById('driverInfoModal'));
      });

      let isDriverModalClick = false;
      window.addEventListener('click', (event) => {
        const modal = document.getElementById('driverInfoModal');
        if (event.target === modal && !isDriverModalClick) {
          isDriverModalClick = true;
          hideModal(modal);
        } else {
          isDriverModalClick = false;
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.getElementById('driverInfoModal').classList.contains('show')) {
          hideModal(document.getElementById('driverInfoModal'));
        }
      });

      document.getElementById('confirmPayment').addEventListener('click', () => {
        const cashInput = document.getElementById('cashInput');
        const changeDisplay = document.getElementById('changeDisplay');
        const paymentError = document.getElementById('paymentError');
        const paymentModal = document.getElementById('paymentModal');
        const citationId = paymentModal.dataset.citationId;
        const cash = parseFloat(cashInput.value) || 0;
        const unpaidFines = parseFloat(document.getElementById('amountDue').textContent.replace('₱', '')) || 0;

        if (cash <SCRIPT unpaidFines) {
          paymentError.textContent = 'Insufficient cash amount.';
          paymentError.style.display = 'block';
          return;
        }

        const change = cash - unpaidFines;

        loadingDiv.style.display = 'block';
        fetch('pay_citation.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `citation_id=${encodeURIComponent(citationId)}&amount=${encodeURIComponent(cash)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
          if (!response.ok) {
            return response.text().then(text => {
              throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
            });
          }
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Unexpected response format: ${contentType || 'unknown'}`);
          }
          return response.json();
        })
        .then(data => {
          loadingDiv.style.display = 'none';
          if (data.status === 'success') {
            const receiptUrl = `receipt.php?citation_id=${encodeURIComponent(citationId)}&amount_paid=${encodeURIComponent(cash)}&change=${encodeURIComponent(change)}&payment_date=${encodeURIComponent(data.payment_date)}`;
            window.open(receiptUrl, '_blank');
            fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
          } else {
            alert(data.message);
          }
        })
        .catch(error => {
          loadingDiv.style.display = 'none';
          alert('Error processing payment: ' + error.message);
          console.error('Fetch error:', error);
        });

        hideModal(paymentModal);
      });

      document.getElementById('cancelPayment').addEventListener('click', () => {
        hideModal(document.getElementById('paymentModal'));
      });

      document.querySelector('#paymentModal .close').addEventListener('click', () => {
        hideModal(document.getElementById('paymentModal'));
      });

      let isPaymentModalClick = false;
      window.addEventListener('click', (event) => {
        const paymentModal = document.getElementById('paymentModal');
        if (event.target === paymentModal && !isPaymentModalClick) {
          isPaymentModalClick = true;
          hideModal(paymentModal);
        } else {
          isPaymentModalClick = false;
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.getElementById('paymentModal').classList.contains('show')) {
          hideModal(document.getElementById('paymentModal'));
        }
      });

      document.querySelectorAll('.column-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
          const columnIndex = checkbox.getAttribute('data-column');
          const cells = document.querySelectorAll(`#citationTable table th:nth-child(${parseInt(columnIndex) + 2}), #citationTable table td:nth-child(${parseInt(columnIndex) + 2})`);
          cells.forEach(cell => {
            cell.style.display = checkbox.checked ? '' : 'none';
          });
          localStorage.setItem(`column_${columnIndex}`, checkbox.checked);
        });
      });

      document.querySelectorAll('.column-toggle').forEach(checkbox => {
        const columnIndex = checkbox.getAttribute('data-column');
        const saved = localStorage.getItem(`column_${columnIndex}`);
        if (saved === 'false') {
          checkbox.checked = false;
          const cells = document.querySelectorAll(`#citationTable table th:nth-child(${parseInt(columnIndex) + 2}), #citationTable table td:nth-child(${parseInt(columnIndex) + 2})`);
          cells.forEach(cell => cell.style.display = 'none');
        }
      });
    });
    </SCRIPT>