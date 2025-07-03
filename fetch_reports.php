<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Validate CSRF token
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }

    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Determine date range and filters
    $period = filter_input(INPUT_GET, 'period', FILTER_SANITIZE_STRING) ?? 'yearly';
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2025]]) ?? date('Y');
    $start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING);
    $violation_type = filter_input(INPUT_GET, 'violation_type', FILTER_SANITIZE_STRING);
    $vehicle_type = filter_input(INPUT_GET, 'vehicle_type', FILTER_SANITIZE_STRING);

    $date_condition = '';
    $params = [];
    if ($period === 'custom' && $start_date && $end_date) {
        $start = DateTime::createFromFormat('Y-m-d', $start_date);
        $end = DateTime::createFromFormat('Y-m-d', $end_date);
        if (!$start || !$end || $start > $end) {
            throw new Exception('Invalid date range');
        }
        $date_condition = "WHERE c.apprehension_datetime BETWEEN :start_date AND :end_date AND c.is_archived = 0";
        $params[':start_date'] = $start_date . ' 00:00:00';
        $params[':end_date'] = $end_date . ' 23:59:59';
    } else {
        $date_condition = "WHERE YEAR(c.apprehension_datetime) = :year AND c.is_archived = 0";
        $params[':year'] = $year;
    }

    $violation_condition = $violation_type ? "AND TRIM(UPPER(v.violation_type)) = TRIM(UPPER(:violation_type))" : "";
    $vehicle_condition = $vehicle_type ? "AND TRIM(UPPER(v.vehicle_type)) = TRIM(UPPER(:vehicle_type))" : "";
    if ($violation_type) $params[':violation_type'] = $violation_type;
    if ($vehicle_type) $params[':vehicle_type'] = $vehicle_type;

    // Most common violations
    $violations_query = "
        SELECT v.violation_type, COUNT(*) AS count,
               COALESCE(SUM(
                   CASE v.offense_count
                       WHEN 1 THEN vt.fine_amount_1
                       WHEN 2 THEN vt.fine_amount_2
                       WHEN 3 THEN vt.fine_amount_3
                       ELSE 150.00
                   END
               ), 0) AS total_fines
        FROM violations v
        JOIN citations c ON v.citation_id = c.citation_id
        LEFT JOIN violation_types vt ON TRIM(UPPER(v.violation_type)) = TRIM(UPPER(vt.violation_type))
        $date_condition $violation_condition
        GROUP BY v.violation_type
        ORDER BY count DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($violations_query);
    try {
        $stmt->execute($params);
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Violations query returned " . count($violations) . " rows");
    } catch (PDOException $e) {
        error_log("Violations query error: " . $e->getMessage() . "\nQuery: $violations_query\nParams: " . print_r($params, true));
        throw new Exception('Database error in violations query');
    }

    // Barangays with most violations
    $barangays_query = "
        SELECT COALESCE(d.barangay, 'Unknown') AS barangay, COUNT(*) AS count
        FROM citations c
        JOIN drivers d ON c.driver_id = d.driver_id
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        $date_condition $vehicle_condition
        GROUP BY d.barangay
        ORDER BY count DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($barangays_query);
    try {
        $stmt->execute($params);
        $barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Barangays query returned " . count($barangays) . " rows");
    } catch (PDOException $e) {
        error_log("Barangays query error: " . $e->getMessage() . "\nQuery: $barangays_query\nParams: " . print_r($params, true));
        throw new Exception('Database error in barangays query');
    }

    // Payment status
    $payment_status_query = "
        SELECT c.payment_status AS status, COUNT(*) AS count
        FROM citations c
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        $date_condition $vehicle_condition
        GROUP BY c.payment_status
    ";
    $stmt = $conn->prepare($payment_status_query);
    try {
        $stmt->execute($params);
        $payment_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Payment status query returned " . count($payment_status) . " rows");
    } catch (PDOException $e) {
        error_log("Payment status query error: " . $e->getMessage() . "\nQuery: $payment_status_query\nParams: " . print_r($params, true));
        throw new Exception('Database error in payment status query');
    }

    // Violation trends
    $trends_query = '';
    if ($period === 'monthly') {
        $trends_query = "
            SELECT DATE_FORMAT(c.apprehension_datetime, '%Y-%m') AS period, COUNT(*) AS count
            FROM citations c
            JOIN vehicles v ON c.vehicle_id = v.vehicle_id
            WHERE YEAR(c.apprehension_datetime) = :year AND c.is_archived = 0 $vehicle_condition
            GROUP BY DATE_FORMAT(c.apprehension_datetime, '%Y-%m')
            ORDER BY period
        ";
    } elseif ($period === 'quarterly') {
        $trends_query = "
            SELECT CONCAT(YEAR(c.apprehension_datetime), ' Q', QUARTER(c.apprehension_datetime)) AS period, COUNT(*) AS count
            FROM citations c
            JOIN vehicles v ON c.vehicle_id = v.vehicle_id
            WHERE YEAR(c.apprehension_datetime) = :year AND c.is_archived = 0 $vehicle_condition
            GROUP BY YEAR(c.apprehension_datetime), QUARTER(c.apprehension_datetime)
            ORDER BY period
        ";
    } elseif ($period === 'custom') {
        $trends_query = "
            SELECT DATE_FORMAT(c.apprehension_datetime, '%Y-%m') AS period, COUNT(*) AS count
            FROM citations c
            JOIN vehicles v ON c.vehicle_id = v.vehicle_id
            $date_condition $vehicle_condition
            GROUP BY DATE_FORMAT(c.apprehension_datetime, '%Y-%m')
            ORDER BY period
        ";
    } else {
        $trends_query = "
            SELECT YEAR(c.apprehension_datetime) AS period, COUNT(*) AS count
            FROM citations c
            JOIN vehicles v ON c.vehicle_id = v.vehicle_id
            WHERE c.apprehension_datetime IS NOT NULL AND c.is_archived = 0 $vehicle_condition
            GROUP BY YEAR(c.apprehension_datetime)
            ORDER BY period
        ";
    }
    $stmt = $conn->prepare($trends_query);
    try {
        $stmt->execute($period === 'custom' ? $params : ($period === 'yearly' ? [] : $params));
        $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Trends query returned " . count($trends) . " rows");
    } catch (PDOException $e) {
        error_log("Trends query error: " . $e->getMessage() . "\nQuery: $trends_query\nParams: " . print_r($period === 'custom' ? $params : ($period === 'yearly' ? [] : $params), true));
        throw new Exception('Database error in trends query');
    }

    // Fine revenue over time
    $revenue_query = '';
    if ($period === 'monthly') {
        $revenue_query = "
            SELECT DATE_FORMAT(c.apprehension_datetime, '%Y-%m') AS period,
                   COALESCE(SUM(
                       CASE v.offense_count
                           WHEN 1 THEN vt.fine_amount_1
                           WHEN 2 THEN vt.fine_amount_2
                           WHEN 3 THEN vt.fine_amount_3
                           ELSE 150.00
                       END
                   ), 0) AS total_fines
            FROM citations c
            JOIN violations v ON c.citation_id = v.citation_id
            LEFT JOIN violation_types vt ON TRIM(UPPER(v.violation_type)) = TRIM(UPPER(vt.violation_type))
            WHERE YEAR(c.apprehension_datetime) = :year AND c.is_archived = 0 $violation_condition
            GROUP BY DATE_FORMAT(c.apprehension_datetime, '%Y-%m')
            ORDER BY period
        ";
    } elseif ($period === 'quarterly') {
        $revenue_query = "
            SELECT CONCAT(YEAR(c.apprehension_datetime), ' Q', QUARTER(c.apprehension_datetime)) AS period,
                   COALESCE(SUM(
                       CASE v.offense_count
                           WHEN 1 THEN vt.fine_amount_1
                           WHEN 2 THEN vt.fine_amount_2
                           WHEN 3 THEN vt.fine_amount_3
                           ELSE 150.00
                       END
                   ), 0) AS total_fines
            FROM citations c
            JOIN violations v ON c.citation_id = v.citation_id
            LEFT JOIN violation_types vt ON TRIM(UPPER(v.violation_type)) = TRIM(UPPER(vt.violation_type))
            WHERE YEAR(c.apprehension_datetime) = :year AND c.is_archived = 0 $violation_condition
            GROUP BY YEAR(c.apprehension_datetime), QUARTER(c.apprehension_datetime)
            ORDER BY period
        ";
    } elseif ($period === 'custom') {
        $revenue_query = "
            SELECT DATE_FORMAT(c.apprehension_datetime, '%Y-%m') AS period,
                   COALESCE(SUM(
                       CASE v.offense_count
                           WHEN 1 THEN vt.fine_amount_1
                           WHEN 2 THEN vt.fine_amount_2
                           WHEN 3 THEN vt.fine_amount_3
                           ELSE 150.00
                       END
                   ), 0) AS total_fines
            FROM citations c
            JOIN violations v ON c.citation_id = v.citation_id
            LEFT JOIN violation_types vt ON TRIM(UPPER(v.violation_type)) = TRIM(UPPER(vt.violation_type))
            $date_condition $violation_condition
            GROUP BY DATE_FORMAT(c.apprehension_datetime, '%Y-%m')
            ORDER BY period
        ";
    } else {
        $revenue_query = "
            SELECT YEAR(c.apprehension_datetime) AS period,
                   COALESCE(SUM(
                       CASE v.offense_count
                           WHEN 1 THEN vt.fine_amount_1
                           WHEN 2 THEN vt.fine_amount_2
                           WHEN 3 THEN vt.fine_amount_3
                           ELSE 150.00
                       END
                   ), 0) AS total_fines
            FROM citations c
            JOIN violations v ON c.citation_id = v.citation_id
            LEFT JOIN violation_types vt ON TRIM(UPPER(v.violation_type)) = TRIM(UPPER(vt.violation_type))
            WHERE c.apprehension_datetime IS NOT NULL AND c.is_archived = 0 $violation_condition
            GROUP BY YEAR(c.apprehension_datetime)
            ORDER BY period
        ";
    }
    $stmt = $conn->prepare($revenue_query);
    try {
        $stmt->execute($period === 'custom' ? $params : ($period === 'yearly' ? [] : $params));
        $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Revenue query returned " . count($revenue) . " rows");
    } catch (PDOException $e) {
        error_log("Revenue query error: " . $e->getMessage() . "\nQuery: $revenue_query\nParams: " . print_r($period === 'custom' ? $params : ($period === 'yearly' ? [] : $params), true));
        throw new Exception('Database error in revenue query');
    }

    // Vehicle types
    $vehicle_query = "
        SELECT COALESCE(v.vehicle_type, 'Unknown') AS vehicle_type, COUNT(*) AS count
        FROM citations c
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        $date_condition $vehicle_condition
        GROUP BY v.vehicle_type
        ORDER BY count DESC
    ";
    $stmt = $conn->prepare($vehicle_query);
    try {
        $stmt->execute($params);
        $vehicle_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Vehicle types query returned " . count($vehicle_types) . " rows");
    } catch (PDOException $e) {
        error_log("Vehicle types query error: " . $e->getMessage() . "\nQuery: $vehicle_query\nParams: " . print_r($params, true));
        throw new Exception('Database error in vehicle types query');
    }

    // Top apprehension locations
    $locations_query = "
        SELECT COALESCE(c.place_of_apprehension, 'Unknown') AS place_of_apprehension, COUNT(*) AS count
        FROM citations c
        JOIN vehicles v ON c.vehicle_id = v.vehicle_id
        $date_condition $vehicle_condition
        GROUP BY c.place_of_apprehension
        ORDER BY count DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($locations_query);
    try {
        $stmt->execute($params);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Locations query returned " . count($locations) . " rows");
    } catch (PDOException $e) {
        error_log("Locations query error: " . $e->getMessage() . "\nQuery: $locations_query\nParams: " . print_r($params, true));
        throw new Exception('Database error in locations query');
    }

    // Repeat offenders
    $repeat_offenders_query = "
        SELECT 
            CONCAT(d.first_name, ' ', d.last_name, 
                   IF(d.middle_initial IS NOT NULL AND d.middle_initial != '', CONCAT(' ', d.middle_initial), ''),
                   IF(d.suffix IS NOT NULL AND d.suffix != '', CONCAT(' ', d.suffix), '')) AS driver_name,
            COALESCE(d.license_number, 'N/A') AS license_number,
            COUNT(c.citation_id) AS citation_count,
            COALESCE(SUM(
                CASE v.offense_count
                    WHEN 1 THEN vt.fine_amount_1
                    WHEN 2 THEN vt.fine_amount_2
                    WHEN 3 THEN vt.fine_amount_3
                    ELSE 150.00
                END
            ), 0) AS total_fines
        FROM citations c
        JOIN drivers d ON c.driver_id = d.driver_id
        JOIN violations v ON c.citation_id = v.citation_id
        LEFT JOIN violation_types vt ON TRIM(UPPER(v.violation_type)) = TRIM(UPPER(vt.violation_type))
        $date_condition $violation_condition
        GROUP BY d.driver_id, d.first_name, d.last_name, d.middle_initial, d.suffix, d.license_number
        HAVING COUNT(c.citation_id) > 1
        ORDER BY citation_count DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($repeat_offenders_query);
    try {
        $stmt->execute($params);
        $repeat_offenders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Repeat offenders query returned " . count($repeat_offenders) . " rows");
    } catch (PDOException $e) {
        error_log("Repeat offenders query error: " . $e->getMessage() . "\nQuery: $repeat_offenders_query\nParams: " . print_r($params, true));
        throw new Exception('Database error in repeat offenders query');
    }

    echo json_encode([
        'violations' => $violations,
        'barangays' => $barangays,
        'payment_status' => $payment_status,
        'trends' => $trends,
        'revenue' => $revenue,
        'vehicle_types' => $vehicle_types,
        'locations' => $locations,
        'repeat_offenders' => $repeat_offenders
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("PDOException in fetch_reports.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: Unable to fetch reports']);
} catch (Exception $e) {
    error_log("Exception in fetch_reports.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn = null;
}
?>