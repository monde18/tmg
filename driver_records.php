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
  <title>Driver Violation Records</title>
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

    /* Sidebar Styles */
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

    /* Content Styles */
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

    .sort-select, .search-input, .page-size-select {
      border-radius: 8px;
      border: 1px solid var(--border);
      padding: 0.5rem 0.75rem;
      font-size: 0.9rem;
      background-color: white;
      transition: all 0.3s ease;
      box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .sort-select, .page-size-select {
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

    .sort-select:focus, .search-input:focus, .page-size-select:focus {
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
      text-align: center;
      border-bottom: 2px solid var(--border);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      white-space: nowrap;
    }

    .table td {
      padding: 0.75rem;
      vertical-align: middle;
      text-align: center;
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

    .violation-list {
      text-align: left;
      padding-left: 20px;
      white-space: normal;
      line-height: 1.5;
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

    .empty-state {
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
      justify-content: center;
      gap: 0.5rem;
      margin: 0;
      flex-wrap: wrap;
    }

    .pagination .page-item .page-link {
      border-radius: 8px;
      color: var(--primary);
      border: 1px solid var(--border);
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      min-width: 2.5rem;
      text-align: center;
    }

    .pagination .page-item.active .page-link {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .pagination .page-item .page-link:hover {
      background-color: var(--primary-dark);
      color: white;
      transform: translateY(-1px);
    }

    .pagination .page-item.disabled .page-link {
      cursor: not-allowed;
      opacity: 0.5;
      transform: none;
    }

    .pagination-info {
      font-size: 0.9rem;
      color: var(--secondary);
    }

    @keyframes spin {
      100% {
        transform: rotate(360deg);
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 260px;
        transform: translateX(-100%);
        position: fixed;
        top: 0;
        left: 0;
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

      .sort-select, .search-input, .page-size-select {
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

      .sort-select, .search-input, .page-size-select {
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <div class="content" id="content">
    <div class="container">
      <div class="header">
        <h4>Republic of the Philippines</h4>
        <h4>Province of Cagayan • Municipality of Baggao</h4>
        <h1>Driver Violation Records</h1>
      </div>

      <div class="sort-filter">
        <a href="index.php" class="btn btn-primary btn-custom" aria-label="Add New Citation"><i class="fas fa-plus"></i> Add New Citation</a>
        <a href="records.php" class="btn btn-primary btn-custom" aria-label="View Citation Records"><i class="fas fa-file-alt"></i> View Citation Records</a>
        <select id="sortSelect" class="sort-select" aria-label="Sort Options">
          <option value="name_asc">Sort by Name (A-Z)</option>
          <option value="name_desc">Sort by Name (Z-A)</option>
          <option value="violation_count">Sort by Violation Count</option>
        </select>
        <input type="text" id="searchInput" class="search-input" placeholder="Search by Driver Name" aria-label="Search Drivers">
        <select id="pageSizeSelect" class="page-size-select" aria-label="Records per page">
          <option value="5">5 per page</option>
          <option value="10">10 per page</option>
          <option value="25">25 per page</option>
          <option value="50">50 per page</option>
        </select>
      </div>

      <div id="loading" class="loading" style="display: none;">
        <i class="fas fa-spinner fa-2x"></i> Loading records...
      </div>

      <div id="driverTable" class="table-responsive">
        <?php
        try {
          $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
          $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          // Pagination settings
          $recordsPerPage = isset($_GET['pageSize']) && is_numeric($_GET['pageSize']) ? (int)$_GET['pageSize'] : 5;
          $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
          $offset = ($page - 1) * $recordsPerPage;

          // Count total records for pagination
          $countSql = "
            SELECT COUNT(DISTINCT d.driver_id) as total
            FROM drivers d
            LEFT JOIN citations c ON d.driver_id = c.driver_id AND c.is_archived = 0
            LEFT JOIN violations v ON c.citation_id = v.citation_id
          ";
          $countParams = [];
          $countWhereClauses = [];
          $search = isset($_GET['search']) ? trim($_GET['search']) : '';
          if ($search) {
            $countWhereClauses[] = "CONCAT(d.last_name, ' ', d.first_name) LIKE :search";
            $countParams['search'] = "%$search%";
          }
          if (!empty($countWhereClauses)) {
            $countSql .= " WHERE " . implode(" AND ", $countWhereClauses);
          }
          $countStmt = $conn->prepare($countSql);
          $countStmt->execute($countParams);
          $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
          $totalPages = ceil($totalRecords / $recordsPerPage);

          // Main query with detailed offense counts
          $sql = "
            SELECT d.driver_id, 
                   CONCAT(d.last_name, ', ', d.first_name, ' ', COALESCE(d.middle_initial, ''), ' ', COALESCE(d.suffix, '')) AS driver_name,
                   d.license_number,
                   d.license_type,
                   COUNT(v.violation_id) AS violation_count,
                   GROUP_CONCAT(
                     CONCAT(v.violation_type, ' - ', 
                       CASE v.offense_count
                         WHEN 1 THEN '1st Offense'
                         WHEN 2 THEN '2nd Offense'
                         WHEN 3 THEN '3rd Offense'
                         ELSE v.offense_count
                       END
                     ) SEPARATOR '\n'
                   ) AS violation_list,
                   GROUP_CONCAT(v.offense_count SEPARATOR '\n') AS offense_counts,
                   GROUP_CONCAT(
                     CASE v.violation_type
                       WHEN 'NO HELMET (Driver)' THEN 150
                       WHEN 'NO HELMET (Backrider)' THEN 150
                       WHEN 'NO DRIVER’S LICENSE / MINOR' THEN 500
                       WHEN 'NO / EXPIRED VEHICLE REGISTRATION' THEN 2500
                       WHEN 'NO / DEFECTIVE PARTS & ACCESSORIES' THEN 500
                       WHEN 'NOISY MUFFLER (98db above)' THEN 
                         CASE v.offense_count WHEN 1 THEN 2500 WHEN 2 THEN 500 WHEN 3 THEN 2500 END
                       WHEN 'NO MUFFLER ATTACHED' THEN 2500
                       WHEN 'RECKLESS / ARROGANT DRIVING' THEN 
                         CASE v.offense_count WHEN 1 THEN 500 WHEN 2 THEN 750 WHEN 3 THEN 1000 END
                       WHEN 'DISREGARDING TRAFFIC SIGN' THEN 150
                       WHEN 'ILLEGAL MODIFICATION' THEN 500
                       WHEN 'PASSENGER ON TOP OF THE VEHICLE' THEN 150
                       WHEN 'ILLEGAL PARKING' THEN 
                         CASE v.offense_count WHEN 1 THEN 200 WHEN 2 THEN 500 WHEN 3 THEN 2500 END
                       WHEN 'ROAD OBSTRUCTION' THEN 
                         CASE v.offense_count WHEN 1 THEN 200 WHEN 2 THEN 500 WHEN 3 THEN 2500 END
                       WHEN 'BLOCKING PEDESTRIAN LANE' THEN 
                         CASE v.offense_count WHEN 1 THEN 200 WHEN 2 THEN 500 WHEN 3 THEN 2500 END
                       WHEN 'LOADING/UNLOADING IN PROHIBITED ZONE' THEN 
                         CASE v.offense_count WHEN 1 THEN 200 WHEN 2 THEN 500 WHEN 3 THEN 2500 END
                       WHEN 'DOUBLE PARKING' THEN 
                         CASE v.offense_count WHEN 1 THEN 200 WHEN 2 THEN 500 WHEN 3 THEN 1500 END
                       WHEN 'DRUNK DRIVING' THEN 
                         CASE v.offense_count WHEN 1 THEN 500 WHEN 2 THEN 1000 WHEN 3 THEN 1500 END
                       WHEN 'COLORUM OPERATION' THEN 
                         CASE v.offense_count WHEN 1 THEN 2500 WHEN 2 THEN 3000 WHEN 3 THEN 3000 END
                       WHEN 'NO TRASHBIN' THEN 
                         CASE v.offense_count WHEN 1 THEN 1000 WHEN 2 THEN 2000 WHEN 3 THEN 2500 END
                       WHEN 'DRIVING IN SHORT / SANDO' THEN 
                         CASE v.offense_count WHEN 1 THEN 200 WHEN 2 THEN 500 WHEN 3 THEN 1000 END
                       WHEN 'OVERLOADED PASSENGER' THEN 
                         CASE v.offense_count WHEN 1 THEN 500 WHEN 2 THEN 750 WHEN 3 THEN 1000 END
                       WHEN 'OVER CHARGING / UNDER CHARGING' THEN 
                         CASE v.offense_count WHEN 1 THEN 500 WHEN 2 THEN 750 WHEN 3 THEN 1000 END
                       WHEN 'REFUSAL TO CONVEY PASSENGER/S' THEN 
                         CASE v.offense_count WHEN 1 THEN 500 WHEN 2 THEN 750 WHEN 3 THEN 1000 END
                       WHEN 'DRAG RACING' THEN 
                         CASE v.offense_count WHEN 1 THEN 1000 WHEN 2 THEN 1500 WHEN 3 THEN 2500 END
                       WHEN 'NO ENHANCED OPLAN VISA STICKER' THEN 300
                       WHEN 'FAILURE TO PRESENT E-OV MATCH CARD' THEN 200
                       ELSE 200
                     END SEPARATOR '\n') AS fines
            FROM drivers d
            LEFT JOIN citations c ON d.driver_id = c.driver_id AND c.is_archived = 0
            LEFT JOIN violations v ON c.citation_id = v.citation_id
          ";

          $params = [];
          $whereClauses = [];

          if ($search) {
            $whereClauses[] = "CONCAT(d.last_name, ' ', d.first_name) LIKE :search";
            $params['search'] = "%$search%";
          }

          if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
          }

          $sql .= " GROUP BY d.driver_id, driver_name, d.license_number, d.license_type";

          $sql .= " HAVING violation_count > 0 OR COUNT(c.citation_id) = 0";

          $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
          switch ($sort) {
            case 'name_desc':
              $sql .= " ORDER BY driver_name DESC";
              break;
            case 'violation_count':
              $sql .= " ORDER BY violation_count DESC, driver_name";
              break;
            case 'name_asc':
            default:
              $sql .= " ORDER BY driver_name";
              break;
          }

          $sql .= " LIMIT :limit OFFSET :offset";
          $stmt = $conn->prepare($sql);
          $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
          $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
          foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
          }
          $stmt->execute();
          $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if (empty($drivers)) {
            echo "<p class='empty-state'><i class='fas fa-info-circle'></i> No driver records found.</p>";
          } else {
            echo "<table class='table table-bordered table-striped' id='citationTable'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th><i class='fas fa-hashtag me-2'></i>#</th>";
            echo "<th><i class='fas fa-user me-2'></i>Driver Name</th>";
            echo "<th><i class='fas fa-id-card me-2'></i>License Number</th>";
            echo "<th><i class='fas fa-id-badge me-2'></i>License Type</th>";
            echo "<th><i class='fas fa-exclamation-triangle me-2'></i>Violation</th>";
            echo "<th><i class='fas fa-sort-numeric-up me-2'></i>Offense Count</th>";
            echo "<th><i class='fas fa-money-bill-wave me-2'></i>Fine (₱)</th>";
            echo "<th><i class='fas fa-cog me-2'></i>Action</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            $rowNumber = $offset + 1;
            foreach ($drivers as $driver) {
              echo "<tr>";
              echo "<td>" . $rowNumber . "</td>";
              echo "<td>" . htmlspecialchars($driver['driver_name']) . "</td>";
              echo "<td>" . htmlspecialchars($driver['license_number'] ?? 'N/A') . "</td>";
              echo "<td>" . htmlspecialchars($driver['license_type'] ?? 'N/A') . "</td>";
              echo "<td class='violation-list'>" . ($driver['violation_list'] ? nl2br(htmlspecialchars($driver['violation_list'])) : 'None') . "</td>";
              echo "<td class='violation-list'>" . ($driver['offense_counts'] ? nl2br(htmlspecialchars($driver['offense_counts'])) : 'None') . "</td>";
              echo "<td class='violation-list'>" . ($driver['fines'] ? nl2br(htmlspecialchars($driver['fines'])) : 'None') . "</td>";
              echo "<td>";
              echo "<a href='add_violation_form.php?driver_id=" . htmlspecialchars($driver['driver_id']) . "' class='btn btn-sm btn-primary btn-custom' aria-label='Add Violation'><i class='fas fa-plus'></i> Add Violation</a>";
              echo "</td>";
              echo "</tr>";
              $rowNumber++;
            }
            echo "</tbody>";
            echo "</table>";

            // Improved Pagination
            echo "<div class='pagination-container'>";
            echo "<div class='pagination-info'>";
            $startRecord = $offset + 1;
            $endRecord = min($offset + $recordsPerPage, $totalRecords);
            echo "Showing $startRecord to $endRecord of $totalRecords records";
            echo "</div>";
            echo "<nav aria-label='Page navigation'>";
            echo "<ul class='pagination'>";

            // First page
            echo "<li class='page-item" . ($page == 1 ? " disabled" : "") . "'>";
            echo "<a class='page-link' href='?page=1&sort=$sort&search=" . urlencode($search) . "&pageSize=$recordsPerPage' aria-label='First Page'>";
            echo "<span aria-hidden='true'><i class='fas fa-angle-double-left'></i></span></a>";
            echo "</li>";

            // Previous page
            $prevPage = $page > 1 ? $page - 1 : 1;
            echo "<li class='page-item" . ($page == 1 ? " disabled" : "") . "'>";
            echo "<a class='page-link' href='?page=$prevPage&sort=$sort&search=" . urlencode($search) . "&pageSize=$recordsPerPage' aria-label='Previous'>";
            echo "<span aria-hidden='true'><i class='fas fa-angle-left'></i></span></a>";
            echo "</li>";

            // Page numbers (show 2 pages before and after current page)
            $range = 2;
            $start = max(1, $page - $range);
            $end = min($totalPages, $page + $range);

            if ($start > 1) {
              echo "<li class='page-item'><span class='page-link'>...</span></li>";
            }

            for ($i = $start; $i <= $end; $i++) {
              echo "<li class='page-item" . ($i == $page ? " active" : "") . "'>";
              echo "<a class='page-link' href='?page=$i&sort=$sort&search=" . urlencode($search) . "&pageSize=$recordsPerPage'>$i</a>";
              echo "</li>";
            }

            if ($end < $totalPages) {
              echo "<li class='page-item'><span class='page-link'>...</span></li>";
            }

            // Next page
            $nextPage = $page < $totalPages ? $page + 1 : $totalPages;
            echo "<li class='page-item" . ($page == $totalPages ? " disabled" : "") . "'>";
            echo "<a class='page-link' href='?page=$nextPage&sort=$sort&search=" . urlencode($search) . "&pageSize=$recordsPerPage' aria-label='Next'>";
            echo "<span aria-hidden='true'><i class='fas fa-angle-right'></i></span></a>";
            echo "</li>";

            // Last page
            echo "<li class='page-item" . ($page == $totalPages ? " disabled" : "") . "'>";
            echo "<a class='page-link' href='?page=$totalPages&sort=$sort&search=" . urlencode($search) . "&pageSize=$recordsPerPage' aria-label='Last Page'>";
            echo "<span aria-hidden='true'><i class='fas fa-angle-double-right'></i></span></a>";
            echo "</li>";

            echo "</ul>";
            echo "</nav>";
            echo "</div>";
          }
        } catch (PDOException $e) {
          echo "<p class='empty-state'><i class='fas fa-exclamation-circle'></i> Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        $conn = null;
        ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const content = document.getElementById('content');
      const loadingDiv = document.getElementById('loading');
      const driverTable = document.getElementById('driverTable');
      const sortSelect = document.getElementById('sortSelect');
      const searchInput = document.getElementById('searchInput');
      const pageSizeSelect = document.getElementById('pageSizeSelect');

      // Sidebar toggle
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        sidebar.classList.toggle('collapsed');
        sidebarToggle.classList.toggle('active');
        content.classList.toggle('collapsed');

        if (window.innerWidth <= 768) {
          if (sidebar.classList.contains('open')) {
            sidebarToggle.style.left = '270px';
          } else {
            sidebarToggle.style.left = '10px';
          }
        }
      });

      // Close sidebar if clicked outside on mobile
      document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && sidebar.classList.contains('open')) {
          sidebar.classList.remove('open');
          sidebarToggle.classList.remove('active');
          sidebarToggle.style.left = '10px';
        }
      });

      // Adjust layout on window resize
      window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
          sidebar.classList.remove('open');
          sidebarToggle.style.left = '10px';
          content.classList.toggle('collapsed', sidebar.classList.contains('collapsed'));
        }
      });

      // Show loading state
      if (loadingDiv && driverTable) {
        loadingDiv.style.display = 'block';
        driverTable.style.opacity = '0';
        setTimeout(() => {
          loadingDiv.style.display = 'none';
          driverTable.style.opacity = '1';

          // Add hover effect for table rows
          const rows = document.querySelectorAll('.table tr');
          rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
              row.style.cursor = 'pointer';
            });
            row.addEventListener('mouseleave', () => {
              row.style.cursor = 'default';
            });
          });
        }, 300);
      }

      // Handle sort selection
      if (sortSelect) {
        sortSelect.addEventListener('change', function() {
          const sortValue = this.value;
          const url = new URL(window.location);
          url.searchParams.set('sort', sortValue);
          url.searchParams.set('page', '1');
          window.location.href = url.toString();
        });

        const urlParams = new URLSearchParams(window.location.search);
        const sortParam = urlParams.get('sort') || 'name_asc';
        sortSelect.value = sortParam;
      }

      // Handle page size selection
      if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
          const pageSize = this.value;
          const url = new URL(window.location);
          url.searchParams.set('pageSize', pageSize);
          url.searchParams.set('page', '1');
          window.location.href = url.toString();
        });

        const urlParams = new URLSearchParams(window.location.search);
        const pageSizeParam = urlParams.get('pageSize') || '5';
        pageSizeSelect.value = pageSizeParam;
      }

      // Search functionality
      if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
          const url = new URL(window.location);
          url.searchParams.set('search', searchInput.value);
          url.searchParams.set('page', '1');
          window.location.href = url.toString();
        }, 300));

        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        searchInput.value = searchParam;
      }

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
    });
  </script>
</body>
</html>