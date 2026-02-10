<?php
session_start();
require "../config/db.php";
require "../config/mail.php";

/* ================= SECURITY CHECK ================= */
if (
    empty($_SESSION['reset_email']) ||
    empty($_SESSION['reset_otp']) ||
    empty($_SESSION['otp_time'])
) {
    header("Location: forgot.php");
    exit;
}

$error = "";
$success = "";

/* ================= OTP EXPIRY ================= */
$OTP_VALIDITY = 120;
$elapsed = time() - $_SESSION['otp_time'];
$remaining = max(0, $OTP_VALIDITY - $elapsed);

/* ================= VERIFY OTP ================= */
if (isset($_POST['verify'])) {

    if ($remaining <= 0) {
        $error = "⏰ OTP expired. Please resend OTP.";
    } else {

        $otp = trim($_POST['otp']);

        if ($otp === (string)$_SESSION['reset_otp']) {

            /* OTP VERIFIED */
            $_SESSION['otp_verified'] = true;

            /* DESTROY OTP (ONE TIME USE) */
            unset($_SESSION['reset_otp']);
            unset($_SESSION['otp_time']);

            session_regenerate_id(true);

            header("Location: reset_password.php");
            exit;
        } else {
            $error = "❌ Invalid OTP";
        }
    }
}

/* ================= RESEND OTP ================= */
if (isset($_POST['resend'])) {

    if ($remaining > 0) {
        $error = "⏳ Please wait before resending OTP";
    } else {

        $newOtp = rand(100000, 999999);

        $_SESSION['reset_otp'] = $newOtp;
        $_SESSION['otp_time'] = time();

        $emailBody = "
        <div style='max-width:520px;margin:auto;font-family:Segoe UI;background:#fff;border-radius:12px'>
            <div style='background:#141e30;color:#fff;padding:20px;text-align:center'>
                <h2>Exam Portal</h2>
                <p>Password Reset OTP</p>
            </div>
            <div style='padding:25px'>
                <p>Your OTP:</p>
                <div style='font-size:34px;font-weight:700;letter-spacing:8px;
                            background:#f1f4ff;padding:12px 20px;
                            text-align:center;border-radius:8px'>
                    $newOtp
                </div>
                <p style='margin-top:20px'>Valid for 2 minutes.</p>
            </div>
        </div>";

        sendMail(
            $_SESSION['reset_email'],
            "Password Reset OTP – Exam Portal",
            $emailBody
        );

        $success = "📩 New OTP sent successfully";
        $remaining = $OTP_VALIDITY;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Verify OTP | Exam Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/reset_otp.css">
</head>
<body>

    <div class="card p-4 shadow">

        <h4 class="text-center mb-3">OTP Verification</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text"
                name="otp"
                maxlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                autofocus
                class="form-control mb-3"
                placeholder="••••••"
                required>

            <button name="verify" class="btn btn-primary w-100 mb-2">
                Verify OTP
            </button>
        </form>

        <form method="post" class="text-center">
            <button name="resend"
                id="resendBtn"
                class="resend"
                <?= $remaining > 0 ? "disabled" : "" ?>>
                Resend OTP
            </button>
        </form>

        <p class="text-center text-muted mt-2" id="timerText"></p>

    </div>

    <script>
        let timeLeft = <?= $remaining ?>;
        let btn = document.getElementById("resendBtn");
        let text = document.getElementById("timerText");

        function startTimer() {
            if (timeLeft > 0) {
                btn.disabled = true;
                text.innerHTML = "Resend available in " + timeLeft + "s";

                const timer = setInterval(() => {
                    timeLeft--;
                    text.innerHTML = "Resend available in " + timeLeft + "s";

                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        btn.disabled = false;
                        text.innerHTML = "You can resend OTP now";
                    }
                }, 1000);
            } else {
                btn.disabled = false;
                text.innerHTML = "You can resend OTP now";
            }
        }
        startTimer();
    </script>

</body>

</html>