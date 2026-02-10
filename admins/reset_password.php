<?php
session_start();
require "../config/db.php";

/* ================= SECURITY CHECK ================= */
if (
    empty($_SESSION['otp_verified']) ||
    empty($_SESSION['reset_email'])
) {
    header("Location: forgot.php");
    exit;
}

$error = "";
$success = "";

/* ================= RESET PASSWORD ================= */
if (isset($_POST['reset'])) {

    $p1 = trim($_POST['password']);
    $p2 = trim($_POST['cpassword']);

    if ($p1 !== $p2) {
        $error = "❌ Passwords do not match";
    }
    elseif (strlen($p1) < 8) {
        $error = "❌ Password must be at least 8 characters";
    }
    elseif (!preg_match("/[A-Z]/", $p1) ||
            !preg_match("/[0-9]/", $p1)) {
        $error = "❌ Password must contain at least 1 uppercase letter and 1 number";
    }
    else {

        $hash  = password_hash($p1, PASSWORD_BCRYPT);
        $email = $_SESSION['reset_email'];

        /* UPDATE PASSWORD (SECURE QUERY) */
        $stmt = $conn->prepare(
            "UPDATE admins SET password=? WHERE email=?"
        );
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();

        /* CLEAN RESET SESSIONS ONLY */
        unset($_SESSION['otp_verified']);
        unset($_SESSION['reset_email']);

        session_regenerate_id(true);

        $success = "✅ Password updated successfully";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Reset Password | Exam Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:linear-gradient(135deg,#141e30,#243b55);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:Segoe UI;
}
.card{
    max-width:420px;
    width:100%;
    border-radius:16px;
}
.form-control{border-radius:12px}
.btn{border-radius:12px}
</style>
</head>

<body>

<div class="card p-4 shadow">

<h4 class="text-center mb-3">Reset Password</h4>

<?php if($error): ?>
<div class="alert alert-danger text-center"><?= $error ?></div>
<?php endif; ?>

<?php if($success): ?>
<div class="alert alert-success text-center"><?= $success ?></div>
<a href="login.php" class="btn btn-success w-100 mt-2">
Go to Login
</a>
<?php else: ?>

<form method="post">

<input type="password"
       name="password"
       class="form-control mb-3"
       placeholder="New Password"
       required>

<input type="password"
       name="cpassword"
       class="form-control mb-3"
       placeholder="Confirm Password"
       required>

<button name="reset" class="btn btn-primary w-100">
Reset Password
</button>

</form>

<p class="text-center text-muted mt-3" style="font-size:13px">
Password must be at least 8 characters, include 1 uppercase & 1 number
</p>

<?php endif; ?>

</div>

</body>
</html>
