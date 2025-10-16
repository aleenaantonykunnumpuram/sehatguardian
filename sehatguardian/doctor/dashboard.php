<?php
// Session & DB and Authentication
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/auth.php');
checkAuth('doctor');
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/db_connect.php');

$doctor_id = $_SESSION['user_id'];

// Handle AJAX request to clear all alerts
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'clear_all_alerts'
) {
    // FIXED: Clear alerts without requiring appointment relationship
    $sql = "UPDATE emergency_alerts SET status = 'Cleared' WHERE status = 'Sent'";
    $stmt = $conn->prepare($sql);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}
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
        .alert {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            border-left: 5px solid #fca5a5;
            animation: alertPulse 2s ease-in-out infinite;
        }
        @keyframes alertPulse {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(220, 38, 38, 0.6);
            }
        }
        .alert strong {
            font-size: 1.1em;
            display: block;
            margin-bottom: 5px;
        }
        .alert-badge {
            background: #fef2f2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            display: inline-block;
            margin-top: 8px;
            border: 2px solid #fca5a5;
        }
        #clear-alerts-btn {
            background: #008080;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            font-weight: bold;
            font-size: 1em;
            margin-top: 15px;
            transition: background 0.3s;
        }
        #clear-alerts-btn:hover {
            background: #009faf;
        }
        .no-alerts {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 1.1em;
        }
        .dropdown-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .dropdown {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 60%;
            font-size: 16px;
        }
        .patient-details {
            margin: 0 auto;
            width: 85%;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .patient-details h3 {
            text-align: center;
            margin-bottom: 15px;
        }
        .patient-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .patient-details th, .patient-details td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .patient-details th {
            text-align: left;
            width: 35%;
        }
        .form-label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
        .form-select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 100%;
            max-width: 400px;
            font-size: 16px;
        }
        .mb-3 {
            margin-bottom: 20px;
        }
        .alert-count {
            background: #ff5722;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.8em;
            margin-left: 5px;
            font-weight: bold;
        }
    </style>
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
    <a href="#alerts">Emergency Alerts <?php 
        // Show alert count badge
        $count_sql = "SELECT COUNT(*) as count FROM emergency_alerts WHERE status='Sent'";
        $count_result = $conn->query($count_sql);
        $alert_count = $count_result->fetch_assoc()['count'];
        if($alert_count > 0) echo '<span class="alert-count">'.$alert_count.'</span>';
    ?></a>
    <a href="#healthlog">Health Logs</a>
</div>

<!-- Appointments Section -->
<div class="section" id="appointments">
    <h2>My Appointments</h2>
    <?php
    // Accept/Reject appointment
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

<!-- Patients Section -->
<div class="section" id="patients">
    <h2>My Patients</h2>

    <?php
    $sql_patients = "
        SELECT 
            u.user_id, 
            u.username, 
            u.email, 
            p.date_of_birth, 
            p.gender, 
            p.contact_number, 
            p.address, 
            p.weight, 
            p.height, 
            p.blood_group, 
            p.allergies, 
            p.emergency_contact_name, 
            p.emergency_contact_number, 
            p.emergency_contact_relation
        FROM users u
        LEFT JOIN patient_profile p ON u.user_id = p.patient_id
        WHERE u.role = 'patient'
        ORDER BY u.username ASC
    ";
    $stmt_pat = $conn->prepare($sql_patients);
    $stmt_pat->execute();
    $res_pat = $stmt_pat->get_result();
    ?>

    <?php if ($res_pat && $res_pat->num_rows > 0): ?>
        <div class="dropdown-container">
            <select id="patientDropdown" class="dropdown">
                <option value="">-- Select a Patient --</option>
                <?php while ($p = $res_pat->fetch_assoc()): ?>
                    <option 
                        value="<?= htmlspecialchars($p['user_id']) ?>"
                        data-email="<?= htmlspecialchars($p['email']) ?>"
                        data-dob="<?= htmlspecialchars($p['date_of_birth']) ?>"
                        data-gender="<?= htmlspecialchars($p['gender']) ?>"
                        data-contact="<?= htmlspecialchars($p['contact_number']) ?>"
                        data-address="<?= htmlspecialchars($p['address']) ?>"
                        data-weight="<?= htmlspecialchars($p['weight']) ?>"
                        data-height="<?= htmlspecialchars($p['height']) ?>"
                        data-blood="<?= htmlspecialchars($p['blood_group']) ?>"
                        data-allergies="<?= htmlspecialchars($p['allergies']) ?>"
                        data-ecname="<?= htmlspecialchars($p['emergency_contact_name']) ?>"
                        data-ecnum="<?= htmlspecialchars($p['emergency_contact_number']) ?>"
                        data-ecrel="<?= htmlspecialchars($p['emergency_contact_relation']) ?>">
                        <?= htmlspecialchars($p['username']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div id="patientDetails" class="patient-details" style="display:none;">
            <h3>Patient Profile</h3>
            <table>
                <tr><th>Name</th><td id="pName"></td></tr>
                <tr><th>Email</th><td id="pEmail"></td></tr>
                <tr><th>Date of Birth</th><td id="pDob"></td></tr>
                <tr><th>Gender</th><td id="pGender"></td></tr>
                <tr><th>Contact Number</th><td id="pContact"></td></tr>
                <tr><th>Address</th><td id="pAddress"></td></tr>
                <tr><th>Weight (kg)</th><td id="pWeight"></td></tr>
                <tr><th>Height (cm)</th><td id="pHeight"></td></tr>
                <tr><th>Blood Group</th><td id="pBlood"></td></tr>
                <tr><th>Allergies</th><td id="pAllergies"></td></tr>
                <tr><th>Emergency Contact Name</th><td id="pECName"></td></tr>
                <tr><th>Emergency Contact Number</th><td id="pECNum"></td></tr>
                <tr><th>Relation</th><td id="pECRel"></td></tr>
            </table>
        </div>

        <script>
            const dropdown = document.getElementById("patientDropdown");
            const detailsDiv = document.getElementById("patientDetails");

            dropdown.addEventListener("change", function() {
                const selected = this.options[this.selectedIndex];
                if (selected.value) {
                    document.getElementById("pName").textContent = selected.text;
                    document.getElementById("pEmail").textContent = selected.getAttribute("data-email") || "Not available";
                    document.getElementById("pDob").textContent = selected.getAttribute("data-dob") || "Not available";
                    document.getElementById("pGender").textContent = selected.getAttribute("data-gender") || "Not available";
                    document.getElementById("pContact").textContent = selected.getAttribute("data-contact") || "Not available";
                    document.getElementById("pAddress").textContent = selected.getAttribute("data-address") || "Not available";
                    document.getElementById("pWeight").textContent = selected.getAttribute("data-weight") || "Not available";
                    document.getElementById("pHeight").textContent = selected.getAttribute("data-height") || "Not available";
                    document.getElementById("pBlood").textContent = selected.getAttribute("data-blood") || "Not available";
                    document.getElementById("pAllergies").textContent = selected.getAttribute("data-allergies") || "None";
                    document.getElementById("pECName").textContent = selected.getAttribute("data-ecname") || "Not available";
                    document.getElementById("pECNum").textContent = selected.getAttribute("data-ecnum") || "Not available";
                    document.getElementById("pECRel").textContent = selected.getAttribute("data-ecrel") || "Not available";
                    detailsDiv.style.display = "block";
                } else {
                    detailsDiv.style.display = "none";
                }
            });
        </script>

    <?php else: ?>
        <p style="text-align:center;">No patients found yet.</p>
    <?php endif; ?>

    <?php $stmt_pat->close(); ?>
</div>

<!-- Emergency Alerts Section -->
<div class="section" id="alerts">
    <h2>Emergency Alerts</h2>
    <?php
    // FIXED: Simplified query to show ALL emergency alerts with 'Sent' status
    // No appointment relationship required - doctors should see all emergency alerts
    $sql_alerts = "
        SELECT ea.alert_id, ea.alert_time, ea.location, ea.message, ea.patient_id, u.username AS patient_name, u.email AS patient_email
        FROM emergency_alerts ea
        JOIN users u ON ea.patient_id = u.user_id
        WHERE ea.status='Sent'
        ORDER BY ea.alert_time DESC
        LIMIT 50
    ";
    $stmt_alert = $conn->prepare($sql_alerts);
    $stmt_alert->execute();
    $res_alert = $stmt_alert->get_result();
    ?>
    <div id="alerts-container">
    <?php if($res_alert && $res_alert->num_rows > 0): ?>
        <?php foreach($res_alert as $a): ?>
        <div class="alert" data-id="<?= htmlspecialchars($a['alert_id']) ?>">
            <strong>🚨 <?= htmlspecialchars($a['patient_name']) ?></strong>
            <span class="alert-badge">EMERGENCY</span>
            <div style="margin-top: 10px;">
                <div><strong>Location:</strong> <?= htmlspecialchars($a['location']) ?></div>
                <div><strong>Message:</strong> <em><?= htmlspecialchars($a['message']) ?></em></div>
                <div><strong>Contact:</strong> <?= htmlspecialchars($a['patient_email']) ?></div>
                <div style="margin-top: 5px;"><small><strong>Time:</strong> <?= htmlspecialchars($a['alert_time']) ?></small></div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="text-align: center;">
            <button id="clear-alerts-btn" class="btn">Clear All Alerts</button>
        </div>
    <?php else: ?>
        <div class="no-alerts">
            <strong>✓ No Emergency Alerts</strong><br>
            <small>All clear - no pending emergency alerts at this time.</small>
        </div>
    <?php endif; ?>
    </div>
    <?php $stmt_alert->close(); ?>
</div>

<!-- Health Logs Section -->
<div class="section" id="healthlog">
    <h2>Health Logs</h2>

    <?php
    $stmt_pat = $conn->prepare("
        SELECT u.user_id, u.username 
        FROM users u
        WHERE u.role = 'patient'
        ORDER BY u.username
    ");
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
            echo '<div style="text-align:center; padding:20px;">No health logs available for this patient.</div>';
        }
        $stmt_logs->close();
    } else {
        echo '<div style="text-align:center; padding:20px;">Please select a patient to view health logs.</div>';
    }
    ?>
</div>

<script>
    // Clock update
    function updateClock() {
        var now = new Date();
        var clock = document.getElementById('clock');
        if (clock) {
            clock.textContent = now.toLocaleTimeString();
        }
    }
    setInterval(updateClock, 1000);
    window.onload = updateClock;

    // Clear all alerts AJAX
    document.addEventListener('DOMContentLoaded', function(){
        var clearBtn = document.getElementById('clear-alerts-btn');
        if(clearBtn){
            clearBtn.addEventListener('click', function(){
                if(!confirm('Are you sure you want to clear all displayed alerts?')) return;

                var formData = new FormData();
                formData.append('action', 'clear_all_alerts');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success){
                        location.reload(); // Reload to show updated alert count
                    } else {
                        alert('Failed to clear alerts.');
                    }
                })
                .catch(() => alert('Error clearing alerts.'));
            });
        }
    });

    // Auto-refresh alerts every 30 seconds to check for new ones
    setInterval(function() {
        location.reload();
    }, 30000);
</script>

</body>
</html>