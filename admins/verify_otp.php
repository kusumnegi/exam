<?php
session_start();
require "../config/db.php";
require "../config/mail.php";

/* ================= BLOCK DIRECT ACCESS ================= */
if (
    empty($_SESSION['admin_email']) ||
    empty($_SESSION['admin_pass']) ||
    empty($_SESSION['otp'])
) {
    header("Location: index.php");
    exit;
}

/* ================= ENSURE OTP TIMER EXISTS ================= */
if (!isset($_SESSION['otp_time'])) {
    $_SESSION['otp_time'] = time();
}

$error   = "";
$success = "";

/* ================= OTP EXPIRY CHECK ================= */
if ((time() - $_SESSION['otp_time']) > 120) {
    $error = "⏳ OTP expired. Please resend OTP.";
}

/* ================= VERIFY OTP ================= */
if (isset($_POST['verify']) && empty($error)) {

    $userOtp = trim($_POST['otp']);

    if ($userOtp === (string)$_SESSION['otp']) {

        $email = $_SESSION['admin_email'];
        $pass  = $_SESSION['admin_pass'];

        /* CHECK DUPLICATE ADMIN */
        $check = $conn->prepare(
            "SELECT id FROM admins WHERE email=? LIMIT 1"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "❌ Admin already exists. Please login.";
        } else {

            /* INSERT ADMIN */
            $stmt = $conn->prepare(
                "INSERT INTO admins (email, password, is_verified)
                 VALUES (?, ?, 1)"
            );
            $stmt->bind_param("ss", $email, $pass);
            $stmt->execute();

            /* CLEAR OTP SESSIONS */
            unset($_SESSION['admin_email']);
            unset($_SESSION['admin_pass']);
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);

            session_regenerate_id(true);

            $success = "✅ Account verified successfully. You can login now.";
        }
    } else {
        $error = "❌ Invalid OTP";
    }
}

/* ================= RESEND OTP ================= */
if (isset($_POST['resend'])) {

    if ((time() - $_SESSION['otp_time']) < 120) {

        $error = "⏳ Please wait before resending OTP";
    } else {

        $newOtp = rand(100000, 999999);

        $_SESSION['otp']      = $newOtp;
        $_SESSION['otp_time'] = time();

        $emailBody = "
        <div style='max-width:520px;margin:auto;font-family:Segoe UI;background:#fff;border-radius:12px'>
            <div style='background:#141e30;color:#fff;padding:20px;text-align:center'>
                <h2>Exam Portal</h2>
                <p>Admin OTP Verification</p>
            </div>
            <div style='padding:25px'>
                <p>Your OTP:</p>
                <div style='font-size:32px;font-weight:700;letter-spacing:8px;
                            background:#f1f4ff;padding:12px 20px;
                            text-align:center;border-radius:8px'>
                    $newOtp
                </div>
                <p style='margin-top:20px'>Valid for 2 minutes.</p>
            </div>
        </div>";

        sendMail(
            $_SESSION['admin_email'],
            "Admin OTP Verification – Exam Portal",
            $emailBody
        );
    }
}

/* ================= TIMER (SAFE) ================= */
$remaining = 0;
if (isset($_SESSION['otp_time'])) {
    $remaining = 120 - (time() - $_SESSION['otp_time']);
    if ($remaining < 0) $remaining = 0;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Verify OTP | Exam Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assest/css/verify_otp.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="card p-4">

        <h4 class="text-center mb-3">OTP Verification</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
            <a href="login.php" class="btn btn-success w-100">Go to Login</a>

        <?php else: ?>

            <form method="post">
                <input type="text" name="otp" maxlength="6"
                    class="form-control mb-3"
                    placeholder="••••••" required>

                <button name="verify" class="btn btn-primary w-100 mb-2">
                    Verify OTP
                </button>
            </form>

            <form method="post" class="text-center">
                <button name="resend"
                    id="resendBtn"
                    class="resend"
                    <?= $remaining > 0 ? "disabled" : "" ?>>
                    <?= $remaining > 0 ? "Resend OTP ($remaining s)" : "Resend OTP" ?>
                </button>
            </form>

        <?php endif; ?>

    </div>

    <script>
        let t = <?= $remaining ?>;
        let btn = document.getElementById("resendBtn");

        if (btn && t > 0) {
            let timer = setInterval(() => {
                t--;
                btn.innerText = "Resend OTP (" + t + "s)";
                if (t <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.innerText = "Resend OTP";
                }
            }, 1000);
        }
    </script>

</body>

</html>