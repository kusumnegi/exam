<?php
session_start();
require "../config/db.php";

/* ================= ALREADY LOGGED IN ================= */
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    $q = mysqli_query($conn, "
        SELECT id, email, password, name 
        FROM admins 
        WHERE email='$email' AND is_verified=1
        LIMIT 1
    ");

    if (mysqli_num_rows($q) === 1) {

        $admin = mysqli_fetch_assoc($q);

        if (password_verify($pass, $admin['password'])) {

            /* ===== AUTH SESSION ===== */
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_email']     = $admin['email'];
            $_SESSION['admin_name']      = $admin['name'] ?? 'Admin';

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "❌ Invalid password";
        }
    } else {
        $error = "❌ Account not found or not verified";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Login | Exam Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/login.css">
</head>

<body>
    <div class="card">

        <div class="card-header">
            Admin Login
        </div>

        <div class="card-body p-4">

            <?php if ($error): ?>
                <div class="alert alert-danger text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <input type="email" name="email"
                    class="form-control mb-3"
                    placeholder="Admin Email" required>

                <input type="password" name="password"
                    class="form-control mb-3"
                    placeholder="Password" required>

                <button name="login" class="btn btn-primary w-100">
                    Login
                </button>

            </form>

            <div class="text-center mt-3 ">
                <a href="forgot.php">Forgot password?</a>
            </div>
            <div class="text-center mt-2">
                <small>
                    Don’t have an account?
                    <a href="index.php" class="fw-semibold text-decoration-none">Sign up</a>
                </small>
            </div>


        </div>
    </div>

</body>

</html>