<?php
session_start();
require "../config/db.php";
/*------   login check   ------*/
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*------   Total students   ------*/
$totalStudents = $conn->query("
    SELECT COUNT(*) total FROM students
")->fetch_assoc()['total'];

/*------   total exams  ------*/
$totalExams = $conn->query("
    SELECT COUNT(*) total FROM exams
")->fetch_assoc()['total'];

/*------   active exams   ------*/
$activeExams = $conn->query("
    SELECT COUNT(*) total 
    FROM exams 
    WHERE NOW() BETWEEN start_date AND end_date
")->fetch_assoc()['total'];

/*------   expired exams   ------*/
$expiredExams = $conn->query("
    SELECT COUNT(*) total 
    FROM exams 
    WHERE NOW() > end_date
")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/dashboard.css">
</head>
<body>
    <!------   adding sidebar file   ------>
    <?php include "sidebar.php"; ?>
    <!------   main start here   ------>
    <div class="main">
        <div class="topbar">
            <button class="btn btn-outline-secondary d-md-none" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0">Dashboard</h5>
            <span class="text-muted">
                Welcome, <b><?= htmlspecialchars($adminName) ?></b>
            </span>
        </div>

        <div class="container-fluid p-4">
            <div class="row g-4">
                <!-- TOTAL STUDENTS -->
                <div class="col-6 col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Students</h6>
                                <h3><?= $totalStudents ?></h3>
                            </div>
                            <i class="bi bi-people stat-icon"></i>
                        </div>
                    </div>
                </div>
                <!-- TOTAL EXAMS -->
                <div class="col-6 col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Exams</h6>
                                <h3><?= $totalExams ?></h3>
                            </div>
                            <i class="bi bi-journal-text stat-icon"></i>
                        </div>
                    </div>
                </div>
                <!-- ACTIVE EXAMS -->
                <div class="col-6 col-md-3">
                    <div class="card p-3 shadow-sm" onclick="location.href='active_exams.php'">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Active Exams</h6>
                                <h3><?= $activeExams ?></h3>
                            </div>
                            <i class="bi bi-lightning-charge stat-icon"></i>
                        </div>
                    </div>
                </div>
                <!-- EXPIRED EXAMS -->
                <div class="col-6 col-md-3">
                    <div class="card p-3 shadow-sm" onclick="location.href='expired_exams.php'">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Expired Exams</h6>
                                <h3><?= $expiredExams ?></h3>
                            </div>
                            <i class="bi bi-clock-history stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 mx-4 p-4 shadow-sm">
            <h5>👋 Hello <?= htmlspecialchars($adminName) ?></h5>
            <p class="text-muted mb-0"> Manage students, exams, results and system settings from here. </p>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
    </script>
</body>
</html>