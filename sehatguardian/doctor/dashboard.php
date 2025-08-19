<?php
// Session & DB
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/auth.php'); // Use unified auth.php
checkAuth('doctor');
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/db_connect.php');

$doctor_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #e0f7f7;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #0097a7;
            color: #fff;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header .left-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .clock {
            font-size: 1.4em;
            font-weight: bold;
            background: #00bcd4;
            padding: 6px 18px;
            border-radius: 8px;
            color: #fff;
        }
        .logout-btn {
            background: #b71c1c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: #900000;
        }
        .nav {
            background: #006064;
            color: #fff;
            padding: 18px 0;
            text-align: center;
        }
        .nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 30px;
            font-weight: bold;
            padding: 8px 22px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav a:hover, .nav .active {
            background: #00838f;
            color: #fff;
        }
        .section {
            margin: 32px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
            max-width: 950px;
            padding: 30px 40px;
        }
        h2 {
            color: #006064;
            text-align: center;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #0097a7;
            color: #fff;
        }
        .btn {
            background: #00838f;
            color: #fff;
            padding: 6px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #006064;
        }
        .btn-reject {
            background: #b71c1c;
        }
        .btn-reject:hover {
            background: #900000;
        }
        .section table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.section table th, .section table td {
    border: 1px solid #ddd;
    padding: 8px;
}
.section table th {
    background-color: #0097a7;
    color: white;
    text-align: left;
}

        .alert {
            background: #ff5722;
            color: #fff;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
    <script>
    // Real-time clock in teal box
    function updateClock() {
        var now = new Date();
        var clock = document.getElementById('clock');
        if (clock) {
            clock.textContent = now.toLocaleTimeString();
        }
    }
    setInterval(updateClock, 1000);
    window.onload = updateClock;
    </script>
    
</head>
<body>

<div class="header">
    <div class="left-section">
        <strong>Doctor Dashboard</strong><br>
        <small>Welcome, <span style="text-decoration: underline;"><?= htmlspecialchars($_SESSION['username']) ?></span></small>
    </div>
    <div>
        <button class="logout-btn" onclick="window.location.href='/sehatguardian/logout.php'">Logout</button>
        <div class="clock" id="clock"></div>
    </div>
</div>

<div class="nav">
    <a href="#appointments" class="active">Appointments</a>
    <a href="#patients">My Patients</a>
    <a href="#alerts">Emergency Alerts</a>
    <a href="#healthlog">Health Logs</a>

</div>

<div class="section" id="appointments">
    <h2>My Appointments</h2>
    <?php
    // Accept/Reject appointment logic
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['accept']) && isset($_POST['appointment_id'])) {
            $aid = intval($_POST['appointment_id']);
            $stmt = $conn->prepare("UPDATE appointments SET status='Approved' WHERE appointment_id=? AND doctor_id=?");
            $stmt->bind_param("ii", $aid, $doctor_id);
            $stmt->execute();
            $stmt->close();
        }
        if (isset($_POST['reject']) && isset($_POST['appointment_id'])) {
            $aid = intval($_POST['appointment_id']);
            $stmt = $conn->prepare("UPDATE appointments SET status='Rejected' WHERE appointment_id=? AND doctor_id=?");
            $stmt->bind_param("ii", $aid, $doctor_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Fetch appointments
    $sql = "
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, u.username AS patient_name, u.email AS patient_email
        FROM appointments a
        JOIN users u ON a.patient_id = u.user_id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>
    <table>
        <tr>
            <th>Date</th><th>Time</th><th>Patient Name</th><th>Email</th><th>Status</th><th>Action</th>
        </tr>
        <?php if($result && $result->num_rows > 0): foreach($result as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['appointment_time']) ?></td>
            <td><?= htmlspecialchars($row['patient_name']) ?></td>
            <td><?= htmlspecialchars($row['patient_email']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
                <?php if($row['status'] === 'Pending'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                    <button class="btn" type="submit" name="accept">Accept</button>
                    <button class="btn btn-reject" type="submit" name="reject">Reject</button>
                </form>
                <?php else: ?>
                <span>-</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; 
        else: ?>
        <tr><td colspan="6" style="text-align:center;">No appointments found.</td></tr>
        <?php endif; ?>
    </table>
    <?php $stmt->close(); ?>
</div>

<div class="section" id="patients">
    <h2>My Patients</h2>
    <?php
    $sql_patients = "
        SELECT DISTINCT u.username, u.email
        FROM appointments a
        JOIN users u ON a.patient_id = u.user_id
        WHERE a.doctor_id = ? AND a.status IN ('Approved','Completed')
    ";
    $stmt_pat = $conn->prepare($sql_patients);
    $stmt_pat->bind_param('i', $doctor_id);
    $stmt_pat->execute();
    $res_pat = $stmt_pat->get_result();
    ?>
    <table>
        <tr><th>Patient Name</th><th>Email</th></tr>
        <?php if($res_pat && $res_pat->num_rows > 0): foreach($res_pat as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['username']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
        </tr>
        <?php endforeach;
        else: ?>
        <tr><td colspan="2" style="text-align:center;">No patients found yet.</td></tr>
        <?php endif; ?>
    </table>
    <?php $stmt_pat->close(); ?>
</div>

<div class="section" id="alerts">
    <h2>Emergency Alerts</h2>
    <?php
    $sql_alerts = "
        SELECT ea.alert_time, ea.location, ea.message, u.username AS patient_name
        FROM emergency_alerts ea
        JOIN appointments a ON ea.patient_id = a.patient_id
        JOIN users u ON ea.patient_id = u.user_id
        WHERE a.doctor_id = ? AND a.status IN ('Approved','Completed')
        ORDER BY ea.alert_time DESC
        LIMIT 20
    ";
    $stmt_alert = $conn->prepare($sql_alerts);
    $stmt_alert->bind_param('i', $doctor_id);
    $stmt_alert->execute();
    $res_alert = $stmt_alert->get_result();
    ?>
    <?php if($res_alert && $res_alert->num_rows > 0): foreach($res_alert as $a): ?>
    <div class="alert">
        <strong><?= htmlspecialchars($a['patient_name']) ?></strong> at <?= htmlspecialchars($a['location']) ?><br>
        <em><?= htmlspecialchars($a['message']) ?></em><br>
        <small><?= htmlspecialchars($a['alert_time']) ?></small>
    </div>
    <?php endforeach;
    else: ?>
    <div style="text-align:center;">No alerts found.</div>
    <?php endif; ?>
    <?php $stmt_alert->close(); ?>
</div>
<div class="section" id="healthlog">
    <h2>Health Logs</h2>

    <?php
    // Fetch approved/completed patients for dropdown
    $stmt_pat = $conn->prepare("
        SELECT DISTINCT u.user_id, u.username 
        FROM appointments a
        JOIN users u ON a.patient_id = u.user_id
        WHERE a.doctor_id = ? AND a.status IN ('Approved','Completed')
        ORDER BY u.username
    ");
    $stmt_pat->bind_param('i', $doctor_id);
    $stmt_pat->execute();
    $res_pat = $stmt_pat->get_result();
    $patients = [];
    while ($p = $res_pat->fetch_assoc()) {
        $patients[] = $p;
    }
    $stmt_pat->close();

    $selected_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    ?>

    <form method="GET" class="mb-3">
        <label for="patient_id" class="form-label">Select Patient:</label>
        <select name="patient_id" id="patient_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select Patient --</option>
            <?php foreach($patients as $patient): ?>
                <option value="<?= $patient['user_id'] ?>" <?= ($selected_patient == $patient['user_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($patient['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php
    if ($selected_patient) {
        // Fetch health logs for selected patient
        $stmt_logs = $conn->prepare("
            SELECT log_date, bp, sugar, water_intake, sleep_hours 
            FROM health_log 
            WHERE patient_id = ? 
            ORDER BY log_date DESC
        ");
        $stmt_logs->bind_param('i', $selected_patient);
        $stmt_logs->execute();
        $res_logs = $stmt_logs->get_result();

        if ($res_logs->num_rows > 0) {
            echo '<table>';
            echo '<thead><tr><th>Date</th><th>BP</th><th>Sugar</th><th>Water Intake (L)</th><th>Sleep Hours</th></tr></thead><tbody>';
            while ($log = $res_logs->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($log['log_date']) . '</td>';
                echo '<td>' . htmlspecialchars($log['bp']) . '</td>';
                echo '<td>' . htmlspecialchars($log['sugar']) . '</td>';
                echo '<td>' . htmlspecialchars($log['water_intake']) . '</td>';
                echo '<td>' . htmlspecialchars($log['sleep_hours']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div>No health logs available for this patient.</div>';
        }
        $stmt_logs->close();
    } else {
        echo '<div>Please select a patient to view health logs.</div>';
    }
    ?>
</div>


</body>
</html>
