<?php
session_start();
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sanitize inputs
    $citation_id = filter_input(INPUT_GET, 'citation_id', FILTER_VALIDATE_INT) ?: null;
    $amount_paid = filter_input(INPUT_GET, 'amount_paid', FILTER_VALIDATE_FLOAT) ?: 0;
    $change = filter_input(INPUT_GET, 'change', FILTER_VALIDATE_FLOAT) ?: 0;
    $payment_date = filter_input(INPUT_GET, 'payment_date', FILTER_SANITIZE_STRING) ?: date('Y-m-d H:i:s');

    if (!$citation_id) {
        throw new Exception("Citation ID is required.");
    }

    // Fetch citation details
    $query = "
        SELECT c.citation_id, c.ticket_number, c.payment_amount, c.payment_date,
               CONCAT(d.last_name, ', ', d.first_name,
                      IF(d.middle_initial != '', CONCAT(' ', d.middle_initial), ''),
                      IF(d.suffix != '', CONCAT(' ', d.suffix), '')) AS driver_name,
               d.license_number,
               GROUP_CONCAT(
                   CONCAT(vl.violation_type, ' (Offense ', vl.offense_count, ' - ₱',
                          COALESCE(
                              CASE vl.offense_count
                                  WHEN 1 THEN vt.fine_amount_1
                                  WHEN 2 THEN vt.fine_amount_2
                                  WHEN 3 THEN vt.fine_amount_3
                              END, 200
                          ), ')'
                   ) SEPARATOR ': '
               ) AS violations
        FROM citations c
        JOIN drivers d ON c.driver_id = d.driver_id
        LEFT JOIN violations vl ON c.citation_id = vl.citation_id
        LEFT JOIN violation_types vt ON vl.violation_type = vt.violation_type
        WHERE c.citation_id = :citation_id
        GROUP BY c.citation_id
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute(['citation_id' => $citation_id]);
    $citation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$citation) {
        throw new Exception("Citation not found.");
    }

    // Fetch violations for fine calculation
    $stmt = $conn->prepare("
        SELECT vl.violation_type, vl.offense_count,
               COALESCE(
                   CASE vl.offense_count
                       WHEN 1 THEN vt.fine_amount_1
                       WHEN 2 THEN vt.fine_amount_2
                       WHEN 3 THEN vt.fine_amount_3
                   END, 200
               ) AS fine
        FROM violations vl
        LEFT JOIN violation_types vt ON vl.violation_type = vt.violation_type
        WHERE vl.citation_id = :cid
    ");
    $stmt->execute(['cid' => $citation_id]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($violations)) {
        throw new Exception("No violations found for this citation.");
    }

    $total_fine = 0;
    foreach ($violations as $violation) {
        $total_fine += (float)$violation['fine'];
    }

} catch (Exception $e) {
    header('HTTP/1.1 400 Bad Request');
    echo "<html><body><h1>Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p></body></html>";
    exit;
} finally {
    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --accent: #10b981;
            --danger: #dc2626;
            --background: #f9fafb;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .receipt-container {
            width: 350px;
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
            border: 1px solid var(--border);
        }

        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background-color: #e6e6e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .header {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: #ffffff;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 20px;
        }

        .dashed-line {
            border-top: 2px dashed var(--border);
            margin: 20px 0;
        }

        .receipt-details, .violations-list, .payment-summary {
            margin: 15px 0;
            text-align: left;
        }

        .receipt-details p, .violations-list p, .payment-summary p {
            margin: 8px 0;
            color: var(--text-primary);
            font-size: 13px;
        }

        .receipt-details p strong, .violations-list p strong, .payment-summary p strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .violations-list {
            background-color: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .payment-summary {
            padding: 15px;
            background-color: #f1f5f9;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .payment-summary p.total {
            font-size: 16px;
            font-weight: 700;
            color: var(--danger);
            margin-top: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: var(--text-secondary);
            font-style: italic;
            text-align: center;
        }

        .barcode {
            font-family: 'Courier New', Courier, monospace;
            font-size: 24px;
            margin-top: 20px;
            background-color: #f3f4f6;
            padding: 8px;
            border-radius: 6px;
            color: var(--text-primary);
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
                border: none;
                width: 100%;
                max-width: 350px;
            }

            .header {
                background: linear-gradient(90deg, var(--primary), var(--primary-light));
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .payment-summary p.total {
                color: var(--danger);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="logo">TMG Logo</div>
        <div class="header">Official Payment Receipt</div>

        <div class="receipt-details">
            <p><strong>Receipt #:</strong> <?php echo htmlspecialchars($citation['ticket_number']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($payment_date); ?></p>
            <p><strong>Driver:</strong> <?php echo htmlspecialchars($citation['driver_name']); ?></p>
            <p><strong>License:</strong> <?php echo htmlspecialchars($citation['license_number'] ?? 'N/A'); ?></p>
        </div>

        <div class="dashed-line"></div>

        <div class="violations-list">
            <?php
            foreach ($violations as $index => $violation) {
                echo "<p>" . ($index + 1) . ". " . htmlspecialchars($violation['violation_type']) . 
                     " (Offense " . $violation['offense_count'] . "): ₱" . 
                     number_format($violation['fine'], 2) . "</p>";
            }
            ?>
        </div>

        <div class="dashed-line"></div>

        <div class="payment-summary">
            <p><strong>TOTAL:</strong> <span class="total">₱<?php echo number_format($total_fine, 2); ?></span></p>
            <p><strong>CASH:</strong> ₱<?php echo number_format($amount_paid, 2); ?></p>
            <p><strong>CHANGE:</strong> ₱<?php echo number_format($change, 2); ?></p>
            <p><strong>Payment Method:</strong> Cash</p>
        </div>

        <div class="footer">
            <p>Thank you for your payment!</p>
            <p>Issued by: Traffic Management Group, Baggao</p>
        </div>

        <div class="barcode"><?php echo htmlspecialchars($citation['ticket_number']); ?></div>
    </div>

    <script>
        window.onload = () => {
            window.print();
        };
    </script>
</body>
</html>