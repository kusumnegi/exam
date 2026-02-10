<?php
session_start();

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/mail.php";

/* ---------- RESET ---------- */
if (isset($_GET['reset'])) {
    session_unset();
    session_destroy();
    header("Location: signup.php");
    exit;
}

$error = "";
$step = $_SESSION['signup_step'] ?? 1;

/* ---------- ROLL NUMBER ---------- */
$res = $conn->query("SELECT COUNT(*) total FROM students");
$row = $res->fetch_assoc();
$roll_no = str_pad($row['total'] + 1, 2, "0", STR_PAD_LEFT) . date("dmY");

/* ---------- SEND OTP ---------- */
if (isset($_POST['send_otp'])) {

    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = "Passwords do not match";
    } else {

        $otp = (string) random_int(100000, 999999);

        $_SESSION['signup_step'] = 2;
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_time'] = time();
        $_SESSION['signup_data'] = [
            "name" => trim($_POST['name']),
            "email" => trim($_POST['email']),
            "password" => password_hash($_POST['password'], PASSWORD_BCRYPT),
            "roll" => $roll_no
        ];

        sendMail(
            $_SESSION['signup_data']['email'],
            "Email Verification",
            "<h2>Your OTP</h2>
             <h1 style='letter-spacing:6px;color:#0d6efd'>$otp</h1>
             <p>Valid for 120 seconds</p>"
        );

        header("Location: signup.php");
        exit;
    }
}

/* ---------- RESEND OTP ---------- */
if (isset($_POST['resend_otp']) && isset($_SESSION['signup_data'])) {

    $otp = (string) random_int(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();

    sendMail(
        $_SESSION['signup_data']['email'],
        "Resend OTP",
        "<h2>New OTP</h2>
         <h1 style='letter-spacing:6px;color:#198754'>$otp</h1>
         <p>Valid for 120 seconds</p>"
    );

    header("Location: signup.php");
    exit;
}

/* ---------- VERIFY OTP ---------- */
if (isset($_POST['verify_otp'])) {

    if (!isset($_SESSION['otp'])) {
        $error = "Session expired. Signup again.";
        $step = 1;
    } elseif (time() - $_SESSION['otp_time'] > 120) {
        $error = "OTP expired. Please resend OTP.";
        $step = 2;
    } elseif ($_POST['otp'] !== $_SESSION['otp']) {
        $error = "Invalid OTP";
        $step = 2;
    } else {

        $d = $_SESSION['signup_data'];
        $stmt = $conn->prepare("
            INSERT INTO students (name,email,password,roll_no,created_at)
            VALUES (?,?,?,?,NOW())
        ");
        $stmt->bind_param("ssss", $d['name'], $d['email'], $d['password'], $d['roll']);
        $stmt->execute();

        session_unset();
        session_destroy();

        header("Location: login.php?signup=success");
        exit;
    }
}

/* ---------- TIMER ---------- */
$remaining = 0;
if ($step == 2 && isset($_SESSION['otp_time'])) {
    $remaining = max(0, 120 - (time() - $_SESSION['otp_time']));
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Student Signup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            padding: 26px;
            border-radius: 18px;
        }

        .otp-input {
            text-align: center;
            font-size: 22px;
            letter-spacing: 6px;
        }

        .timer {
            color: #dc3545;
            font-weight: 600
        }

        @media(max-width:576px) {
            .card {
                padding: 20px
            }
        }
    </style>
</head>

<body>

    <div class="card bg-white shadow">
        <h4 class="text-center mb-3">Student Signup</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="post">
                <input name="name" class="form-control mb-2" placeholder="Full Name" required>
                <input name="email" class="form-control mb-2" type="email" placeholder="Email" required>
                <input name="password" class="form-control mb-2" type="password" placeholder="Password" required>
                <input name="confirm_password" class="form-control mb-3" type="password" placeholder="Confirm Password" required>

                <label class="small fw-bold">Roll Number</label>
                <input class="form-control mb-3" value="<?= $roll_no ?>" readonly>

                <button name="send_otp" class="btn btn-primary w-100">Send OTP</button>
            </form>
            <div class="text-center mt-3">
                <a href="login.php" class="small text-muted">Already have an account?</a>
            </div>
        <?php endif; ?>

        <?php if ($step == 2): ?>

            <p class="text-center small">
                OTP sent to <b><?= $_SESSION['signup_data']['email'] ?></b>
            </p>

            <!-- VERIFY OTP FORM -->
            <form method="post">
                <input name="otp" class="form-control otp-input mb-2" placeholder="Enter OTP" required>



                <button name="verify_otp" class="btn btn-success w-100 mb-2">
                    Verify & Create Account
                </button>
            </form>

            <!-- RESEND OTP FORM (SEPARATE) -->
            <form method="post">

                <button name="resend_otp" id="resendBtn" class="btn btn-outline-secondary w-100" disabled>

                    Resend OTP
                </button>
                <p class="text-center timer" id="timer"></p>
            </form>

            <div class="text-center mt-2">
                <a href="signup.php?reset=1" class="small text-muted">Change email address</a>
            </div>

        <?php endif; ?>

    </div>

    <script>
        let time = <?= $remaining ?>;
        const timer = document.getElementById("timer");
        const resendBtn = document.getElementById("resendBtn");

        if (timer) {
            const t = setInterval(() => {
                timer.textContent =
                    String(Math.floor(time / 60)).padStart(2, '0') + ":" +
                    String(time % 60).padStart(2, '0');
                time--;
                if (time < 0) {
                    clearInterval(t);
                    timer.textContent = "OTP expired";
                    resendBtn.disabled = false;
                }
            }, 1000);
        }
    </script>

</body>

</html>