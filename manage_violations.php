<?php
session_start();
require_once 'config.php';

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug CSRF tokens
    error_log("Session CSRF Token: " . $_SESSION['csrf_token']);
    error_log("Posted CSRF Token: " . ($_POST['csrf_token'] ?? 'Not set'));

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = '<div class="alert alert-danger">Invalid CSRF token.</div>';
    } else {
        $violation_type = trim($_POST['violation_type']);
        $fine_amount_1 = filter_var($_POST['fine_amount_1'], FILTER_VALIDATE_FLOAT);
        $fine_amount_2 = filter_var($_POST['fine_amount_2'], FILTER_VALIDATE_FLOAT);
        $fine_amount_3 = filter_var($_POST['fine_amount_3'], FILTER_VALIDATE_FLOAT);

        if (empty($violation_type) || $fine_amount_1 === false || $fine_amount_2 === false || $fine_amount_3 === false) {
            $message = '<div class="alert alert-danger">All fields are required, and fine amounts must be valid numbers.</div>';
        } else {
            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $conn->prepare("INSERT INTO violation_types (violation_type, fine_amount_1, fine_amount_2, fine_amount_3) VALUES (:violation_type, :fine_amount_1, :fine_amount_2, :fine_amount_3)");
                $stmt->execute([
                    ':violation_type' => $violation_type,
                    ':fine_amount_1' => $fine_amount_1,
                    ':fine_amount_2' => $fine_amount_2,
                    ':fine_amount_3' => $fine_amount_3
                ]);
                $message = '<div class="alert alert-success">Violation type added successfully.</div>';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $message = '<div class="alert alert-danger">Violation type already exists.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error adding violation type: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                error_log("PDOException in manage_violations.php: " . $e->getMessage());
            }
            $conn = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Violation Types</title>
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
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 600px;
            padding: 2rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                margin-top: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .btn-primary {
                width: 100%;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Violation Types</h1>
        </div>
        <?php echo $message; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label for="violation_type">Violation Type</label>
                <input type="text" class="form-control" id="violation_type" name="violation_type" required>
            </div>
            <div class="form-group">
                <label for="fine_amount_1">Fine Amount (1st Offense)</label>
                <input type="number" step="0.01" class="form-control" id="fine_amount_1" name="fine_amount_1" required>
            </div>
            <div class="form-group">
                <label for="fine_amount_2">Fine Amount (2nd Offense)</label>
                <input type="number" step="0.01" class="form-control" id="fine_amount_2" name="fine_amount_2" required>
            </div>
            <div class="form-group">
                <label for="fine_amount_3">Fine Amount (3rd Offense)</label>
                <input type="number" step="0.01" class="form-control" id="fine_amount_3" name="fine_amount_3" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Violation Type</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>