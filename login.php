<?php
session_start();
require_once __DIR__ . "/config/db.php";

$error = "";

if (isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {

    $loginInput = trim($_POST['login_input']);
    $password   = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, name, password, status
        FROM students
        WHERE email = ? OR roll_no = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $loginInput, $loginInput);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {

        $user = $res->fetch_assoc();

        if ($user['status'] == 0) {
            $error = "Your account has been blocked by the administrator.";
        } elseif (password_verify($password, $user['password'])) {

            $_SESSION['student_id']   = $user['id'];
            $_SESSION['student_name'] = $user['name'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Student Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/login.css">
</head>

<body>

    <div class="card bg-white">
        <h4 class="text-center mb-3">Student Login</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">

            <input
                name="login_input"
                class="form-control mb-3"
                placeholder="Email / Roll Number"
                required>

            <div class="position-relative mb-3">
                <input
                    name="password"
                    id="password"
                    type="password"
                    class="form-control"
                    placeholder="Password"
                    required>
                <i class="bi bi-eye password-toggle" onclick="togglePassword(this)"></i>
            </div>

            <button name="login" class="btn btn-primary w-100">
                Login
            </button>

        </form>

        <div class="text-center mt-3">
            <a href="signup.php" class="small text-muted">Create new account</a>
        </div>

    </div>

    <script>
        function togglePassword(el) {
            const input = document.getElementById("password");
            if (input.type === "password") {
                input.type = "text";
                el.classList.replace("bi-eye", "bi-eye-slash");
            } else {
                input.type = "password";
                el.classList.replace("bi-eye-slash", "bi-eye");
            }
        }
    </script>

</body>

</html>