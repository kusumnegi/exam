<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assest/css/sidebar.css">
</head>

<body>

<div class="sidebar p-4">
    <h4 class="text-center mb-4">Exam Portal</h4>

    <a href="dashboard.php" class="<?= $currentPage=='dashboard.php'?'active':'' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>

    <a href="students.php" class="<?= $currentPage=='students.php'?'active':'' ?>">
        <i class="bi bi-people me-2"></i> Students
    </a>

    <a href="add_exam.php" class="<?= $currentPage=='add_exam.php'?'active':'' ?>">
        <i class="bi bi-journal-text me-2"></i> Add Exam
    </a>

    <a href="edit_exam.php" class="<?= $currentPage=='edit_exam.php'?'active':'' ?>">
        <i class="bi bi-pencil-square me-2"></i> Edit Exams
    </a>

    <a href="active_exams.php" class="<?= $currentPage=='active_exams.php'?'active':'' ?>">
        <i class="bi bi-lightning-charge me-2"></i> Active Exams
    </a>

    <a href="expired_exams.php" class="<?= $currentPage=='expired_exams.php'?'active':'' ?>">
        <i class="bi bi-clock-history me-2"></i> Expired Exams
    </a>

    <a href="result.php" class="<?= $currentPage=='result.php'?'active':'' ?>">
        <i class="bi bi-clipboard-check me-2"></i> Results
    </a>

    <hr class="border-light">

    <!-- LOGOUT BUTTON -->
    <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
    </a>
</div>

<!-- LOGOUT CONFIRMATION MODAL -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-shield-lock text-danger me-2"></i>
                    Confirm Logout
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to logout from the admin panel?
                </p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <a href="logout.php" class="btn btn-danger">
                    Yes, Logout
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
