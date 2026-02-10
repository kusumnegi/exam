<?php
session_start();
require "../config/db.php";
require "../config/mail.php";

$error = "";

/* ---------- SIGNUP ---------- */
if (isset($_POST['send_otp'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];
    $cpass = $_POST['cpassword'];

    if ($pass !== $cpass) {
        $error = "Passwords do not match";
    } else {

        /* CHECK EXISTING ADMIN */
        $check = mysqli_query($conn,"SELECT id FROM admins WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Admin already exists";
        } else {

            /* CREATE OTP */
            $otp = rand(100000,999999);

            /* STORE TEMP DATA IN SESSION (NOT DB) */
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_pass']  = password_hash($pass, PASSWORD_BCRYPT);
            $_SESSION['otp']         = $otp;
            $_SESSION['otp_time']    = time();

            /* SEND OTP */
            sendMail(
                $email,
                "Admin OTP Verification",
                "
                <div style='font-family:Segoe UI'>
                    <h2>Exam Portal</h2>
                    <p>Your OTP is:</p>
                    <h1 style='letter-spacing:6px'>$otp</h1>
                    <p>Valid for 2 minutes</p>
                </div>
                "
            );

            /* REDIRECT */
            header("Location: verify_otp.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Signup | Exam Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
body{
    background:linear-gradient(135deg,#141e30,#243b55);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}
.card{
    max-width:420px;
    width:100%;
    border-radius:16px;
}
.eye{cursor:pointer}
</style>
</head>
<body>

<div class="card shadow p-4">
<h4 class="text-center mb-3">Admin Signup</h4>

<?php if ($error): ?>
<div class="alert alert-danger text-center"><?= $error ?></div>
<?php endif; ?>

<form method="post">

<input type="email"
       name="email"
       class="form-control mb-3"
       placeholder="Admin Email"
       required>

<div class="input-group mb-3">
    <input type="password" id="p1" name="password" class="form-control" placeholder="Password" required>
    <span class="input-group-text eye" onclick="toggle('p1',this)">
        <i class="fa fa-eye"></i>
    </span>
</div>

<div class="input-group mb-3">
    <input type="password" id="p2" name="cpassword" class="form-control" placeholder="Confirm Password" required>
    <span class="input-group-text eye" onclick="toggle('p2',this)">
        <i class="fa fa-eye"></i>
    </span>
</div>

<button name="send_otp" class="btn btn-primary w-100">
Send OTP
</button>

</form>

<div class="text-center mt-3">
<small>
Already admin?
<a href="login.php" class="text-decoration-none fw-semibold">Login</a>
</small>
</div>
</div>

<script>
function toggle(id, el){
    const i = document.getElementById(id);
    const icon = el.querySelector("i");

    if(i.type === "password"){
        i.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    }else{
        i.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}
</script>

</body>
</html>
