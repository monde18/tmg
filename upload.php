<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

try {
    // Database configuration
    $host = 'localhost';
    $dbname = 'traffic_citation_db';
    $username = 'root';
    $password = '';

    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Increase PHP limits
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);

    // Check file
    if (!isset($_SESSION['file_path']) || !file_exists($_SESSION['file_path'])) {
        throw new Exception('No file uploaded or upload error.');
    }

    $file = $_SESSION['file_path'];
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'csv') {
        throw new Exception('Invalid file type. Please upload a CSV file.');
    }

    // Count total rows for progress
    $fileHandle = fopen($file, 'r');
    if ($fileHandle === false) {
        throw new Exception('Failed to open CSV file for row count.');
    }
    $total_rows = 0;
    while (fgetcsv($fileHandle) !== false) {
        $total_rows++;
    }
    $total_rows--; // Exclude header
    fclose($fileHandle);
    $_SESSION['total_rows'] = $total_rows;
    $_SESSION['progress'] = 0;

    // Create temporary table (17 columns)
    $pdo->exec("
        CREATE TEMPORARY TABLE temp_citations (
            timestamp VARCHAR(50),
            ticket_number VARCHAR(20),
            last_name VARCHAR(50),
            first_name VARCHAR(50),
            middle_initial VARCHAR(5),
            barangay VARCHAR(100),
            zone VARCHAR(50),
            license_number VARCHAR(20),
            plate_number VARCHAR(50),
            vehicle_type VARCHAR(50),
            vehicle_description VARCHAR(255),
            date_apprehended VARCHAR(50),
            time_apprehension VARCHAR(50),
            place_apprehension VARCHAR(255),
            violations TEXT,
            apprehending_officer VARCHAR(100),
            remarks TEXT
        )
    ");

    // Load CSV data
    $fileHandle = fopen($file, 'r');
    if ($fileHandle === false) {
        throw new Exception('Failed to open CSV file.');
    }
    fgetcsv($fileHandle); // Skip header
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO temp_citations (
            timestamp, ticket_number, last_name, first_name, middle_initial, barangay, zone,
            license_number, plate_number, vehicle_type, vehicle_description,
            date_apprehended, time_apprehension, place_apprehension, violations,
            apprehending_officer, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $row_count = 0;
    $batch = [];
    ob_start();
    while (($row = fgetcsv($fileHandle)) !== false) {
        $row_count++;
        // Ensure 17 columns
        $row = array_pad($row, 17, null);

        // Normalize plate_number and barangay
        $row[8] = preg_replace('/\s+/', '', $row[8] ?? ''); // plate_number
        $row[5] = strtoupper(trim($row[5] ?? '')); // barangay

        // Parse date and time (use DATE APREHENDED and TIME OF APPREHENSION)
        $date_apprehended = null;
        $time_apprehension = null;
        if (!empty($row[11]) && !empty($row[12])) {
            $dateTime = DateTime::createFromFormat('m/d/Y h:i:s a', $row[11] . ' ' . $row[12]) ?:
                        DateTime::createFromFormat('m/d/Y h:i a', $row[11] . ' ' . $row[12]) ?:
                        DateTime::createFromFormat('Y-m-d h:i:s a', $row[11] . ' ' . $row[12]) ?:
                        DateTime::createFromFormat('Y-m-d h:i a', $row[11] . ' ' . $row[12]);
            if ($dateTime) {
                $date_apprehended = $dateTime->format('Y-m-d');
                $time_apprehension = $dateTime->format('H:i:s');
                // Skip future dates (beyond June 25, 2025, 23:59:59 PST)
                if ($dateTime > new DateTime('2025-06-25 23:59:59')) {
                    error_log("Row $row_count has future date: $date_apprehended");
                    continue;
                }
            } else {
                error_log("Row $row_count invalid date/time: {$row[11]} {$row[12]}");
            }
        }
        $row[11] = $date_apprehended;
        $row[12] = $time_apprehension;

        // Convert empty strings, 'N/A', 'NONE' to NULL
        $row = array_map(function($value) {
            $value = trim($value ?? '');
            return in_array(strtoupper($value), ['', 'N/A', 'NONE']) ? null : $value;
        }, $row);

        // Validate row
        if (count($row) !== 17) {
            error_log("Row $row_count has incorrect column count: " . count($row) . " columns, data: " . implode(',', $row));
            continue;
        }
        if (empty($row[1]) || empty($row[2]) || empty($row[3])) {
            error_log("Row $row_count missing required fields: ticket_number={$row[1]}, last_name={$row[2]}, first_name={$row[3]}");
            continue;
        }

        $batch[] = $row;
        if (count($batch) >= 1000) {
            $values = implode(',', array_fill(0, count($batch), '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'));
            $batch_stmt = $pdo->prepare("
                INSERT INTO temp_citations (
                    timestamp, ticket_number, last_name, first_name, middle_initial, barangay, zone,
                    license_number, plate_number, vehicle_type, vehicle_description,
                    date_apprehended, time_apprehension, place_apprehension, violations,
                    apprehending_officer, remarks
                ) VALUES $values
            ");
            $batch_stmt->execute(array_merge(...$batch));
            $batch = [];
        }

        // Update progress
        $progress = round(($row_count / $total_rows) * 100, 2);
        $_SESSION['progress'] = $progress;
        if ($row_count % 100 === 0) {
            echo "<script>parent.updateProgress($progress);</script>\n";
            ob_flush();
            flush();
        }
    }

    // Insert remaining batch
    if (!empty($batch)) {
        $values = implode(',', array_fill(0, count($batch), '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'));
        $batch_stmt = $pdo->prepare("
            INSERT INTO temp_citations (
                timestamp, ticket_number, last_name, first_name, middle_initial, barangay, zone,
                license_number, plate_number, vehicle_type, vehicle_description,
                date_apprehended, time_apprehension, place_apprehension, violations,
                apprehending_officer, remarks
            ) VALUES $values
        ");
        $batch_stmt->execute(array_merge(...$batch));
    }

    // Final progress
    $_SESSION['progress'] = 100;
    echo "<script>parent.updateProgress(100);</script>\n";
    ob_flush();
    flush();
    ob_end_clean();

    fclose($fileHandle);
    unlink($file);
    unset($_SESSION['file_path']);

    // Fetch temp_citations count
    $stmt = $pdo->query("SELECT COUNT(*) FROM temp_citations");
    if ($stmt === false) {
        throw new Exception('Failed to query temp_citations count.');
    }
    $temp_rows = $stmt->fetch(PDO::FETCH_NUM)[0];
    error_log("Processed $row_count CSV rows, $temp_rows rows in temp_citations");

    // Clean data
    $pdo->exec("
        UPDATE temp_citations
        SET 
            middle_initial = NULLIF(TRIM(UPPER(middle_initial)), 'N/A'),
            license_number = NULLIF(TRIM(UPPER(license_number)), 'NONE'),
            plate_number = NULLIF(TRIM(UPPER(plate_number)), 'N/A'),
            vehicle_type = NULLIF(TRIM(vehicle_type), 'N/A'),
            vehicle_description = NULLIF(TRIM(vehicle_description), 'N/A'),
            zone = NULLIF(TRIM(zone), 'N/A'),
            barangay = NULLIF(TRIM(UPPER(barangay)), 'N/A'),
            ticket_number = NULLIF(TRIM(ticket_number), ''),
            remarks = NULLIF(TRIM(remarks), ''),
            violations = NULLIF(TRIM(violations), ''),
            place_apprehension = NULLIF(TRIM(place_apprehension), ''),
            date_apprehended = NULLIF(date_apprehended, ''),
            time_apprehension = NULLIF(time_apprehension, ''),
            apprehending_officer = NULLIF(TRIM(apprehending_officer), '')
    ");

    // Insert into drivers
    $pdo->exec("
        INSERT INTO drivers (last_name, first_name, middle_initial, barangay, zone, license_number, municipality, province)
        SELECT DISTINCT 
            TRIM(last_name), 
            TRIM(first_name), 
            middle_initial, 
            barangay, 
            zone, 
            license_number,
            'Baggao',
            'Cagayan'
        FROM temp_citations
        WHERE last_name IS NOT NULL 
            AND first_name IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 
                FROM drivers d 
                WHERE UPPER(d.last_name) = UPPER(TRIM(temp_citations.last_name))
                    AND UPPER(d.first_name) = UPPER(TRIM(temp_citations.first_name))
            )
    ");
    $stmt = $pdo->query("SELECT COUNT(*) FROM drivers");
    $driver_rows = $stmt->fetch(PDO::FETCH_NUM)[0];
    error_log("Inserted $driver_rows drivers");

    // Insert into vehicles
    $pdo->exec("
        INSERT INTO vehicles (plate_mv_engine_chassis_no, vehicle_type, vehicle_description)
        SELECT DISTINCT 
            plate_number, 
            COALESCE(vehicle_type, 'Motorcycle'),
            vehicle_description
        FROM temp_citations
        WHERE plate_number IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 
                FROM vehicles v 
                WHERE UPPER(v.plate_mv_engine_chassis_no) = UPPER(temp_citations.plate_number)
            )
    ");
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
    $vehicle_rows = $stmt->fetch(PDO::FETCH_NUM)[0];
    error_log("Inserted $vehicle_rows vehicles");

    // Insert into citations
    $pdo->exec("
        INSERT INTO citations (ticket_number, driver_id, vehicle_id, apprehension_datetime, place_of_apprehension, payment_status)
        SELECT 
            tc.ticket_number,
            d.driver_id,
            v.vehicle_id,
            IF(tc.date_apprehended IS NOT NULL AND tc.time_apprehension IS NOT NULL,
               STR_TO_DATE(CONCAT(tc.date_apprehended, ' ', tc.time_apprehension), '%Y-%m-%d %H:%i:%s'),
               NULL),
            tc.place_apprehension,
            CASE WHEN UPPER(tc.remarks) = 'PAID' THEN 'Paid' ELSE 'Unpaid' END
        FROM temp_citations tc
        JOIN drivers d 
            ON UPPER(d.last_name) = UPPER(TRIM(tc.last_name))
            AND UPPER(d.first_name) = UPPER(TRIM(tc.first_name))
        JOIN vehicles v 
            ON UPPER(v.plate_mv_engine_chassis_no) = UPPER(tc.plate_number)
        WHERE tc.ticket_number IS NOT NULL
    ");
    $stmt = $pdo->query("SELECT COUNT(*) FROM citations");
    $citation_rows = $stmt->fetch(PDO::FETCH_NUM)[0];
    error_log("Inserted $citation_rows citations");

    // Log non-matching rows
    $stmt = $pdo->query("
        SELECT ticket_number, last_name, first_name, barangay, plate_number
        FROM temp_citations tc
        WHERE tc.ticket_number IS NOT NULL
            AND NOT EXISTS (
                SELECT 1
                FROM citations c
                WHERE c.ticket_number = tc.ticket_number
            )
        LIMIT 10
    ");
    $non_matching = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($non_matching)) {
        error_log("Sample non-matching rows: " . json_encode($non_matching));
    }

    // Insert into violations with multiple violation support and corrected fine matching
    $pdo->exec("
        INSERT INTO violations (citation_id, violation_type, driver_id, fine_amount)
        SELECT 
            c.citation_id,
            TRIM(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(tc.violations, ',', n.n), ',', -1), 
                'NO DRIVERS LICENSE', 'NO DRIVER’S LICENSE / MINOR'), 
                'NO DEFECTIVE PARTS & ACCESSORIES', 'NO /DEFECTIVE PARTS & ACCESSORIES'), 'NO /', 'NO')) AS violation,
            d.driver_id,
            COALESCE(
                CASE
                    WHEN TRIM(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(tc.violations, ',', n.n), ',', -1), 
                        'NO DRIVERS LICENSE', 'NO DRIVER’S LICENSE / MINOR'), 
                        'NO DEFECTIVE PARTS & ACCESSORIES', 'NO /DEFECTIVE PARTS & ACCESSORIES'), 'NO /', 'NO')) LIKE '%3rd Offense%' THEN
                        (SELECT vt.fine_amount_3 FROM violation_types vt 
                         WHERE vt.violation_type = TRIM(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(tc.violations, ',', n.n), ',', -1), 
                             ' - 3rd Offense', ''), 'NO DRIVERS LICENSE', 'NO DRIVER’S LICENSE / MINOR'), 
                             'NO DEFECTIVE PARTS & ACCESSORIES', 'NO /DEFECTIVE PARTS & ACCESSORIES')))
                    WHEN TRIM(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(tc.violations, ',', n.n), ',', -1), 
                        'NO DRIVERS LICENSE', 'NO DRIVER’S LICENSE / MINOR'), 
                        'NO DEFECTIVE PARTS & ACCESSORIES', 'NO /DEFECTIVE PARTS & ACCESSORIES'), 'NO /', 'NO')) LIKE '%2nd Offense%' THEN
                        (SELECT vt.fine_amount_2 FROM violation_types vt 
                         WHERE vt.violation_type = TRIM(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(tc.violations, ',', n.n), ',', -1), 
                             ' - 2nd Offense', ''), 'NO DRIVERS LICENSE', 'NO DRIVER’S LICENSE / MINOR'), 
                             'NO DEFECTIVE PARTS & ACCESSORIES', 'NO /DEFECTIVE PARTS & ACCESSORIES')))
                    ELSE
                        (SELECT vt.fine_amount_1 FROM violation_types vt 
                         WHERE vt.violation_type = TRIM(REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(tc.violations, ',', n.n), ',', -1), 
                             'NO DRIVERS LICENSE', 'NO DRIVER’S LICENSE / MINOR'), 
                             'NO DEFECTIVE PARTS & ACCESSORIES', 'NO /DEFECTIVE PARTS & ACCESSORIES'), 'NO /', 'NO')))
                END,
                150.00 -- Default fine if no match
            ) AS fine_amount
        FROM temp_citations tc
        JOIN citations c ON c.ticket_number = tc.ticket_number
        JOIN drivers d 
            ON UPPER(d.last_name) = UPPER(TRIM(tc.last_name))
            AND UPPER(d.first_name) = UPPER(TRIM(tc.first_name))
        CROSS JOIN (
            SELECT a.N + b.N * 10 + 1 AS n
            FROM 
                (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) a,
                (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b
        ) n
        WHERE tc.violations IS NOT NULL
            AND n.n <= (LENGTH(tc.violations) - LENGTH(REPLACE(tc.violations, ',', '')) + 1)
    ");
    $stmt = $pdo->query("SELECT citation_id, violation_type, fine_amount FROM violations ORDER BY citation_id DESC LIMIT 10");
    $last_violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($last_violations as $v) {
        error_log("Last violation: citation_id={$v['citation_id']}, violation_type={$v['violation_type']}, fine_amount={$v['fine_amount']}");
    }
    $stmt = $pdo->query("SELECT COUNT(*) FROM violations");
    $violation_rows = $stmt->fetch(PDO::FETCH_NUM)[0];
    error_log("Inserted $violation_rows violations");

    // Insert into remarks
    $pdo->exec("
        INSERT INTO remarks (citation_id, remark_text)
        SELECT 
            c.citation_id,
            tc.remarks
        FROM temp_citations tc
        JOIN citations c ON c.ticket_number = tc.ticket_number
        WHERE tc.remarks IS NOT NULL AND UPPER(tc.remarks) != 'PAID'
    ");
    $stmt = $pdo->query("SELECT COUNT(*) FROM remarks");
    $remark_rows = $stmt->fetch(PDO::FETCH_NUM)[0];
    error_log("Inserted $remark_rows remarks");

    // Drop temporary table
    $pdo->exec("DROP TEMPORARY TABLE temp_citations");

    // Commit transaction
    $pdo->commit();

    unset($_SESSION['progress'], $_SESSION['total_rows']);
    $_SESSION['message'] = "CSV imported successfully. Processed $row_count rows, inserted $driver_rows drivers, $vehicle_rows vehicles, $citation_rows citations, $violation_rows violations, $remark_rows remarks.";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    unset($_SESSION['progress'], $_SESSION['total_rows']);
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    error_log('CSV Import Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

header('Location: index.php');
exit;
?>