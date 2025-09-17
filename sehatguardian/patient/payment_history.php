<?php
session_start();
include '../includes/db_connect.php'; // Adjust path as needed

// Check patient login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch payments for this patient
$stmt = $conn->prepare("
    SELECT id, amount, payment_date, status, rejection_reason 
    FROM payments 
    WHERE patient_id = ? 
    ORDER BY payment_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e3f2fd;
            margin: 20px;
        }
        table {
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
            border-collapse: collapse;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(33, 150, 243, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #90caf9;
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background: #2196f3;
            color: #ffffff;
        }
        tr:nth-child(even) {
            background: #bbdefb;
        }
        .status-approved {
            color: #0d47a1;
            font-weight: bold;
        }
        .status-rejected {
            color: #b71c1c;
            font-weight: bold;
        }
        .status-processing {
            color: #1565c0;
            font-weight: normal;
            font-style: italic;
        }
        .rejection-reason {
            font-size: 0.9rem;
            color: #b71c1c;
            margin-top: 4px;
        }
        h2 {
            text-align: center;
            color: #0d47a1;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h2>Payment History</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
            <th>Rejection Reason</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding: 20px; color: #0d47a1; font-style: italic;">
                    No payments found.
                </td>
            </tr>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td>$<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['payment_date']) ?></td>
                    <td class="status-<?= htmlspecialchars($row['status']) ?>">
                        <?= ucfirst(htmlspecialchars($row['status'])) ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'rejected' && !empty($row['rejection_reason'])): ?>
                            <div class="rejection-reason"><?= htmlspecialchars($row['rejection_reason']) ?></div>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

<?php
$stmt->close();
?>
