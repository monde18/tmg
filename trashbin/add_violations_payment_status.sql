<?php
session_start();
require_once 'config.php';

header('Content-Type: text/html');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get parameters from request
    $show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 1;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $recordsPerPage = isset($_GET['records_per_page']) ? intval($_GET['records_per_page']) : 20;
    $offset = ($page - 1) * $recordsPerPage;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Sort validation with whitelist
    $allowedSorts = ['apprehension_desc', 'apprehension_asc', 'ticket_asc', 'driver_asc', 'payment_asc', 'payment_desc'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSorts) ? $_GET['sort'] : 'apprehension_desc';

    // Main query with LEFT JOIN for is_tro and violation_types
    $query = "
        SELECT c.citation_id, c.ticket_number, 
               CONCAT(d.last_name, ', ', d.first_name, 
                      IF(d.middle_initial != '', CONCAT(' ', d.middle_initial), ''), 
                      IF(d.suffix != '', CONCAT(' ', d.suffix), '')) AS driver_name,
               d.driver_id, d.license_number, d.zone, d.barangay, d.municipality, d.province, 
               v.plate_mv_engine_chassis_no, v.vehicle_type, 
               c.apprehension_datetime, c.payment_status,
               GROUP_CONCAT(CONCAT(vl.violation_type, ' (Offense ', vl.offense_count, ')') SEPARATOR ', ') AS violations,
               vl2.violation_id IS NOT NULL AS is_tro,
               r.remark_text AS archiving_reason,
               COALESCE(SUM(
                   CASE vl.offense_count
                       WHEN 1 THEN vt.fine_amount_1
                       WHEN 2 THEN vt.fine_amount_2
                       WHEN 3 THEN vt.fine_amount_3
                       ELSE 150.00
                   END
               ), 0) AS total_fine
        FROM citations c
        JOIN drivers d ON c.driver_id = d.driver_id
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        LEFT JOIN violations vl ON c.citation_id = vl.citation_id
        LEFT JOIN violation_types vt ON vl.violation_type = vt.violation_type
        LEFT JOIN violations vl2 ON vl2.citation_id = c.citation_id AND vl2.violation_type = 'Traffic Restriction Order Violation'
        LEFT JOIN remarks r ON c.citation_id = r.citation_id
        WHERE c.is_archived = :is_archived
    ";

    if ($search) {
        $query .= " AND (c.ticket_number LIKE :search OR CONCAT(d.last_name, ' ', d.first_name) LIKE :search)";
    }

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

    // Prepare and bind
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':is_archived', $show_archived ? 1 : 0, PDO::PARAM_INT);
    if ($search) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Debugging
    error_log("Main Query: " . $query);
    error_log("Main Params: " . print_r([
        ':is_archived' => $show_archived ? 1 : 0,
        ':search' => $search ? "%$search%" : null,
        ':limit' => $recordsPerPage,
        ':offset' => $offset
    ], true));

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched Rows for Citation 22751: " . print_r(array_filter($rows, fn($row) => $row['citation_id'] == 22751), true)); // Debug specific citation

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
            echo "<td>" . htmlspecialchars($row['plate_mv_engine_chassis_no'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['vehicle_type'] ?? '') . "</td>";
            echo "<td>" . ($row['apprehension_datetime'] ? htmlspecialchars($row['apprehension_datetime']) : 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['violations'] ?? 'None') . "</td>";
            echo "<td>";
            switch ($row['payment_status']) {
                case 'Paid':
                    echo '<span class="badge bg-success">Paid</span>';
                    break;
                case 'Partially Paid':
                    echo '<span class="badge bg-warning">Partially Paid</span>';
                    break;
                case 'Unpaid':
                default:
                    echo '<span class="badge bg-danger">Unpaid</span>';
                    break;
            }
            echo "</td>";
            echo "<td>" . htmlspecialchars($row['archiving_reason'] ?? 'N/A') . "</td>";
            echo "<td class='d-flex gap-2'>";
            if (!$show_archived) {
                echo "<a href='edit_citation.php?id=" . $row['citation_id'] . "' class='btn btn-sm btn-primary btn-custom' aria-label='Edit Citation'><i class='fas fa-edit'></i> Edit</a>";
                echo "<a href='delete_citation.php?id=" . $row['citation_id'] . "' class='btn btn-sm btn-danger btn-custom' onclick='return confirm(\"Are you sure you want to delete this citation?\")' aria-label='Delete Citation'><i class='fas fa-trash'></i> Delete</a>";
            }
            $actionText = $show_archived ? "Unarchive" : "Archive";
            $iconClass = $show_archived ? "fa-box-open" : "fa-archive";
            echo "<button class='btn btn-sm btn-archive archive-btn' data-id='" . $row['citation_id'] . "' data-action='" . ($show_archived ? 0 : 1) . "' data-is-tro='" . ($row['is_tro'] ? '1' : '0') . "' aria-label='$actionText Citation'><i class='fas " . $iconClass . "'></i> $actionText</button>";
            if ($row['payment_status'] != 'Paid' && !$show_archived) {
                echo "<a href='#' class='btn btn-sm btn-success btn-custom pay-now' data-citation-id='" . $row['citation_id'] . "' data-driver-id='" . $row['driver_id'] . "' data-zone='" . htmlspecialchars($row['zone'] ?? '') . "' data-barangay='" . htmlspecialchars($row['barangay'] ?? '') . "' data-municipality='" . htmlspecialchars($row['municipality'] ?? '') . "' data-province='" . htmlspecialchars($row['province'] ?? '') . "' aria-label='Pay Citation'><i class='fas fa-credit-card'></i> Pay Now</a>";
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