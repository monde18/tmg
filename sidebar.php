<?php
// sidebar.php
?>
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

  /* Updated Sidebar Styles */
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

  /* Responsive Adjustments */
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
  }
</style>