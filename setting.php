<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$studentId = $_SESSION['student_id'];
$success = $error = "";

/* FETCH STUDENT */
$stmt = $conn->prepare("SELECT name,email,password FROM students WHERE id=?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE students SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $studentId);
    $stmt->execute();

    $_SESSION['student_name'] = $name;
    $success = "Profile updated successfully";
}

/* CHANGE PASSWORD */
if (isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $con = $_POST['confirm_password'];

    if (!password_verify($old, $student['password'])) {
        $error = "Old password is incorrect";
    } elseif ($new !== $con) {
        $error = "New passwords do not match";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE students SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $studentId);
        $stmt->execute();
        $success = "Password changed successfully";
    }
}

/* LOGOUT */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/setting.css">
</head>

<body>

    <div class="container py-5">

        <h4 class="mb-4">Account Settings</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- PROFILE -->
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h5 class="mb-3"><i class="bi bi-person-circle"></i> Profile Details</h5>

                    <form method="post">
                        <input name="name" class="form-control mb-3" value="<?= htmlspecialchars($student['name']) ?>" required>

                        <input name="email" type="email" class="form-control mb-3" value="<?= htmlspecialchars($student['email']) ?>" required>

                        <button name="update_profile" class="btn btn-primary w-100">
                            Update Profile
                        </button>
                    </form>

                </div>
            </div>

            <!-- PASSWORD -->
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h5 class="mb-3"><i class="bi bi-lock-fill"></i> Change Password</h5>

                    <form method="post">
                        <input name="old_password" type="password" class="form-control mb-2" placeholder="Old Password" required>
                        <input name="new_password" type="password" class="form-control mb-2" placeholder="New Password" required>
                        <input name="confirm_password" type="password" class="form-control mb-3" placeholder="Confirm Password" required>

                        <button name="change_password" class="btn btn-primary w-100">
                            Change Password
                        </button>
                    </form>

                </div>
            </div>

        </div>

        <!-- ACTIONS -->
        <div class="card p-4 mt-4">
            <div class="d-flex flex-wrap gap-3 justify-content-between">

                <a href="results.php" class="btn btn-outline-primary">
                    <i class="bi bi-bar-chart-fill"></i> View Results
                </a>

                <a href="?logout=1" class="btn logout-btn text-white">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>

            </div>
        </div>

    </div>

</body>

</html>