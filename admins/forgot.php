<?php
session_start();
require "../config/db.php";
require "../config/mail.php";

$error = "";

/* ===== HANDLE OTP REQUEST ===== */
if (isset($_POST['send_otp'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);

    /* CHECK ADMIN EXISTS */
    $check = mysqli_query($conn,"SELECT id FROM admins WHERE email='$email'");
    if (mysqli_num_rows($check) == 0) {
        $error = "❌ No admin account found with this email";
    } else {

        /* PREVENT OTP SPAM (2 MIN RULE) */
        if (isset($_SESSION['otp_time']) && time() - $_SESSION['otp_time'] < 120) {
            $error = "⏳ Please wait before requesting a new OTP";
        } else {

            /* GENERATE NEW OTP */
            $otp = rand(100000,999999);

            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp']   = $otp;
            $_SESSION['otp_time']    = time();

            /* PROFESSIONAL EMAIL */
            $emailBody = "
            <div style='max-width:520px;margin:auto;font-family:Segoe UI,Arial;background:#ffffff;border-radius:12px;overflow:hidden'>
                <div style='background:#141e30;color:#ffffff;padding:20px;text-align:center'>
                    <h2 style='margin:0'>Exam Portal</h2>
                    <p style='margin:5px 0 0;font-size:14px'>Password Reset Request</p>
                </div>

                <div style='padding:25px;color:#333'>
                    <p>Hello Admin,</p>
                    <p>We received a request to reset your password.</p>
                    <p>Please use the OTP below to continue:</p>

                    <div style='text-align:center;margin:30px 0'>
                        <span style='padding:12px 20px;
                                     font-size:34px;
                                     letter-spacing:8px;
                                     font-weight:bold;
                                     background:#f1f4ff;
                                     color:#141e30;
                                     border-radius:8px'>
                            $otp
                        </span>
                    </div>

                    <p>This OTP is valid for <b>2 minutes</b>.</p>
                    <p>If you did not request this, please ignore this email.</p>

                    <p style='margin-top:30px'>
                        Regards,<br>
                        <b>Exam Portal Team</b>
                    </p>
                </div>

                <div style='background:#f4f6f8;text-align:center;padding:12px;font-size:12px;color:#666'>
                    © ".date('Y')." Exam Portal. All rights reserved.
                </div>
            </div>";

            sendMail($email, "Password Reset OTP – Exam Portal", $emailBody);

            header("Location: reset_otp.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Forgot Password | Exam Portal</title>
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
    box-shadow:0 15px 35px rgba(0,0,0,.25);
}
.form-control,.btn{
    border-radius:12px;
}
</style>
</head>

<body>

<div class="card p-4">
<h4 class="text-center mb-3">Forgot Password</h4>

<p class="text-center text-muted mb-3">
Enter your admin email to receive an OTP
</p>

<?php if ($error): ?>
<div class="alert alert-danger text-center"><?= $error ?></div>
<?php endif; ?>

<form method="post">
<input type="email" name="email"
class="form-control mb-3"
placeholder="Admin Email" required>

<button name="send_otp" class="btn btn-primary w-100">
Send Reset OTP
</button>
</form>

<div class="text-center mt-3">
<small>
Remember password?
<a href="login.php" class="fw-semibold text-decoration-none">Login</a>
</small>
</div>
</div>

</body>
</html>
