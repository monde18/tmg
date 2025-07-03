<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $receivedToken = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING) ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    error_log("Received CSRF Token: $receivedToken, Session CSRF Token: $sessionToken at " . date('Y-m-d H:i:s'));
    if (empty($receivedToken) || $receivedToken !== $sessionToken) {
        throw new Exception('Invalid CSRF token');
    }

    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $citation_id = filter_input(INPUT_GET, 'citation_id', FILTER_VALIDATE_INT);
    $driver_id = filter_input(INPUT_GET, 'driver_id', FILTER_VALIDATE_INT);

    if ($citation_id !== null && $driver_id !== null) {
        $citationCheck = $conn->prepare("SELECT 1 FROM citations WHERE citation_id = :citation_id LIMIT 1");
        $citationCheck->execute([':citation_id' => $citation_id]);
        $driverCheck = $conn->prepare("SELECT 1 FROM drivers WHERE driver_id = :driver_id LIMIT 1");
        $driverCheck->execute([':driver_id' => $driver_id]);

        if (!$citationCheck->fetchColumn()) {
            $errorMsg = "Citation_id $citation_id not found";
            error_log($errorMsg . " at " . date('Y-m-d H:i:s'));
            echo json_encode(['error' => $errorMsg]);
            exit;
        }
        if (!$driverCheck->fetchColumn()) {
            $errorMsg = "Driver_id $driver_id not found";
            error_log($errorMsg . " at " . date('Y-m-d H:i:s'));
            echo json_encode(['error' => $errorMsg]);
            exit;
        }

        $query = "
            SELECT v.violation_id, v.violation_type, v.offense_count, c.apprehension_datetime,
                   COALESCE(
                       CASE v.offense_count
                           WHEN 1 THEN vt.fine_amount_1
                           WHEN 2 THEN vt.fine_amount_2
                           WHEN 3 THEN vt.fine_amount_3
                           ELSE 500.00
                       END, 500.00
                   ) AS fine,
                   c.payment_status,
                   v.payment_status AS violation_payment_status
            FROM violations v
            JOIN citations c ON v.citation_id = c.citation_id
            LEFT JOIN violation_types vt ON UPPER(v.violation_type) = UPPER(vt.violation_type)
            WHERE v.citation_id = :citation_id AND v.driver_id = :driver_id
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([':citation_id' => $citation_id, ':driver_id' => $driver_id]);
        $offenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($offenses)) {
            error_log("No offenses found for citation_id $citation_id, driver_id $driver_id at " . date('Y-m-d H:i:s'));
            echo json_encode(['error' => "No payment data found for citation $citation_id"]);
            exit;
        }
        error_log("Fetched offenses for citation $citation_id: " . print_r($offenses, true));
        $offenses = array_map(function($offense) {
            return [
                'violation_id' => $offense['violation_id'],
                'violation_type' => $offense['violation_type'],
                'offense_count' => $offense['offense_count'],
                'date' => $offense['apprehension_datetime'] ? date('Y-m-d H:i', strtotime($offense['apprehension_datetime'])) : 'N/A',
                'fine' => $offense['fine'],
                'payment_status' => $offense['violation_payment_status'] ?? 'Unpaid'
            ];
        }, $offenses);
        echo json_encode(['offenses' => $offenses], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
    $recordsPerPage = filter_input(INPUT_GET, 'records_per_page', FILTER_VALIDATE_INT) ?: 20;
    $offset = ($page - 1) * $recordsPerPage;
    $search = htmlspecialchars(trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? ''), ENT_QUOTES, 'UTF-8');
    $payment_status = filter_input(INPUT_GET, 'payment_status', FILTER_SANITIZE_STRING) ?? 'All';
    $payment_status = in_array($payment_status, ['Unpaid', 'Paid', 'Partially Paid', 'All']) ? $payment_status : 'All';
    $date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING) ?: '';
    $date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING) ?: '';

    $query = "
        SELECT c.citation_id, c.ticket_number, 
               COALESCE(CONCAT(d.last_name, ', ', d.first_name, 
                      IF(d.middle_initial != '', CONCAT(' ', d.middle_initial), ''), 
                      IF(d.suffix != '', CONCAT(' ', d.suffix), '')), 'Unknown') AS driver_name,
               c.driver_id, d.license_number, d.zone, d.barangay, d.municipality, d.province, 
               COALESCE(v.plate_mv_engine_chassis_no, 'N/A') AS plate_mv_engine_chassis_no, 
               COALESCE(v.vehicle_type, 'N/A') AS vehicle_type, 
               c.apprehension_datetime, c.payment_status, c.payment_amount, c.payment_date,
               c.reference_number,
               GROUP_CONCAT(
                   CONCAT(vl.violation_type, ' (Offense ', vl.offense_count, ' - ₱', 
                          COALESCE(
                              CASE vl.offense_count
                                  WHEN 1 THEN vt.fine_amount_1
                                  WHEN 2 THEN vt.fine_amount_2
                                  WHEN 3 THEN vt.fine_amount_3
                                  ELSE 500.00
                              END, 500.00
                          ), ')'
                   ) SEPARATOR ', '
               ) AS violations,
               COALESCE(SUM(
                   COALESCE(
                       CASE vl.offense_count
                           WHEN 1 THEN vt.fine_amount_1
                           WHEN 2 THEN vt.fine_amount_2
                           WHEN 3 THEN vt.fine_amount_3
                           ELSE 500.00
                       END, 500.00
                   )
               ), 0) AS total_fine
        FROM citations c
        LEFT JOIN drivers d ON c.driver_id = d.driver_id
        LEFT JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        LEFT JOIN violations vl ON c.citation_id = vl.citation_id
        LEFT JOIN violation_types vt ON UPPER(vl.violation_type) = UPPER(vt.violation_type)
        WHERE c.is_archived = 0
    ";
    $params = [];
    if ($payment_status !== 'All') {
        $query .= " AND c.payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }
    if ($search) {
        $query .= " AND (c.ticket_number LIKE :search OR COALESCE(CONCAT(d.last_name, ' ', d.first_name), '') LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($date_from) {
        $query .= " AND c.apprehension_datetime >= :date_from";
        $params[':date_from'] = $date_from;
    }
    if ($date_to) {
        $query .= " AND c.apprehension_datetime <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }

    $query .= " GROUP BY c.citation_id";
    $sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?? 'apprehension_desc';
    $allowedSorts = ['apprehension_desc', 'apprehension_asc', 'ticket_asc', 'driver_asc', 'payment_asc', 'payment_desc'];
    $sort = in_array($sort, $allowedSorts) ? $sort : 'apprehension_desc';
    switch ($sort) {
        case 'apprehension_asc':
            $query .= " ORDER BY c.apprehension_datetime ASC";
            break;
        case 'ticket_asc':
            $query .= " ORDER BY c.ticket_number ASC";
            break;
        case 'driver_asc':
            $query .= " ORDER BY d.last_name, d.first_name ASC";
            break;
        case 'payment_asc':
            $query .= " ORDER BY c.payment_status ASC";
            break;
        case 'payment_desc':
            $query .= " ORDER BY c.payment_status DESC";
            break;
        case 'apprehension_desc':
        default:
            $query .= " ORDER BY c.apprehension_datetime DESC";
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

    error_log("Query: $query");
    error_log("Params: " . print_r($params, true));

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total records for pagination
    $countQuery = "SELECT COUNT(DISTINCT c.ticket_number) as total 
                   FROM citations c 
                   LEFT JOIN drivers d ON c.driver_id = d.driver_id
                   WHERE c.is_archived = 0";
    $countParams = [];
    if ($payment_status !== 'All') {
        $countQuery .= " AND c.payment_status = :payment_status";
        $countParams[':payment_status'] = $payment_status;
    }
    if ($search) {
        $countQuery .= " AND (c.ticket_number LIKE :search OR COALESCE(CONCAT(d.last_name, ' ', d.first_name), '') LIKE :search)";
        $countParams[':search'] = "%$search%";
    }
    if ($date_from) {
        $countQuery .= " AND c.apprehension_datetime >= :date_from";
        $countParams[':date_from'] = $date_from;
    }
    if ($date_to) {
        $countQuery .= " AND c.apprehension_datetime <= :date_to";
        $countParams[':date_to'] = $date_to . ' 23:59:59';
    }
    $countStmt = $conn->prepare($countQuery);
    foreach ($countParams as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'rows' => $rows,
        'totalRecords' => $totalRecords,
        'html' => $rows ? generateHtml($rows) : '<div class="empty-state"><i class="fas fa-info-circle"></i> No citations found for the selected filters.</div>'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("PDOException in fetch_payments.php: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred. Check server logs for details: ' . $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Exception in fetch_payments.php: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} finally {
    $conn = null;
}

function generateHtml($rows) {
    ob_start();
    ?>
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><span>Ticket #</span></th>
                        <th><span>Driver</span></th>
                        <th><span>License #</span></th>
                        <th><span>Plate #</span></th>
                        <th><span>Vehicle Type</span></th>
                        <th><span>Apprehension Date</span></th>
                        <th><span>Violations</span></th>
                        <th><span>Total Fine</span></th>
                        <th><span>Payment Status</span></th>
                        <th><span>Payment Amount</span></th>
                        <th><span>Payment Date</span></th>
                        <th><span>Reference #</span></th>
                        <th><span>Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ticket_number'] ?? ''); ?></td>
                            <td><a href="#" class="driver-link text-primary" data-driver-id="<?php echo htmlspecialchars($row['driver_id'] ?? ''); ?>" data-zone="<?php echo htmlspecialchars($row['zone'] ?? ''); ?>" data-barangay="<?php echo htmlspecialchars($row['barangay'] ?? ''); ?>" data-municipality="<?php echo htmlspecialchars($row['municipality'] ?? ''); ?>" data-province="<?php echo htmlspecialchars($row['province'] ?? ''); ?>" data-license-number="<?php echo htmlspecialchars($row['license_number'] ?? ''); ?>" title="View Driver Details" aria-label="View Driver Details for <?php echo htmlspecialchars($row['driver_name'] ?? ''); ?>"><?php echo htmlspecialchars($row['driver_name'] ?? 'Unknown'); ?></a></td>
                            <td><?php echo htmlspecialchars($row['license_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['plate_mv_engine_chassis_no'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['vehicle_type'] ?? 'N/A'); ?></td>
                            <td><?php echo $row['apprehension_datetime'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($row['apprehension_datetime']))) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($row['violations'] ?? 'None'); ?></td>
                            <td>₱<?php echo number_format($row['total_fine'] ?? 0, 2); ?></td>
                            <td><?php echo $row['payment_status'] == 'Paid' ? '<span class="badge bg-success">Paid</span>' : ($row['payment_status'] == 'Partially Paid' ? '<span class="badge bg-warning">Partially Paid</span>' : '<span class="badge bg-danger">Unpaid</span>'); ?></td>
                            <td><?php echo $row['payment_amount'] ? '₱' . number_format($row['payment_amount'], 2) : 'N/A'; ?></td>
                            <td><?php echo $row['payment_date'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($row['payment_date']))) : 'N/A'; ?></td>
                            <td><?php echo $row['reference_number'] ? htmlspecialchars($row['reference_number']) : 'N/A'; ?></td>
                            <td>
                                <?php if (in_array($row['payment_status'], ['Unpaid', 'Partially Paid'])) : ?>
                                    <a href="#" class="btn-custom btn-success pay-now" data-citation-id="<?php echo htmlspecialchars($row['citation_id'] ?? ''); ?>" data-driver-id="<?php echo htmlspecialchars($row['driver_id'] ?? ''); ?>" data-zone="<?php echo htmlspecialchars($row['zone'] ?? ''); ?>" data-barangay="<?php echo htmlspecialchars($row['barangay'] ?? ''); ?>" data-municipality="<?php echo htmlspecialchars($row['municipality'] ?? ''); ?>" data-province="<?php echo htmlspecialchars($row['province'] ?? ''); ?>" data-license-number="<?php echo htmlspecialchars($row['license_number'] ?? ''); ?>" title="Pay Citation" aria-label="Pay Citation for Ticket <?php echo htmlspecialchars($row['ticket_number'] ?? ''); ?>"><i class="fas fa-credit-card me-2"></i>Pay</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>