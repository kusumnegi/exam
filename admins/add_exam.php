<?php
session_start();
require "../config/db.php";
/*------   login check   ------*/
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}
$success = "";

/*------   add exams  ------*/
if (isset($_POST['add_exam'])) {
    $exam_name  = trim($_POST['exam_name']);
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $attempts   = (int)($_POST['attempts'] ?? 1);
    $duration   = (int)$_POST['duration_minutes'];
    $total_q    = (int)$_POST['total_questions']; /*-- import quetions  --*/

    /*------   image uploading  ------*/
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = time() . "_" . rand(100, 999) . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $imageName);
    }

    /*------   insert exam  ------*/
    $stmt = $conn->prepare("
        INSERT INTO exams 
        (exam_name, start_date, end_date, attempts, duration_minutes, total_questions, exam_image)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssdiis",
        $exam_name,
        $start_date,
        $end_date,
        $attempts,
        $duration,
        $total_q,
        $imageName
    );
    $stmt->execute();

    $exam_id = $stmt->insert_id;

    /*------   insert quetions  ------*/
    for ($i = 1; $i <= $total_q; $i++) {
        $qs = $conn->prepare("
            INSERT INTO exam_questions
            (exam_id, question, option_a, option_b, option_c, option_d, correct_option)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $qs->bind_param(
            "issssss",
            $exam_id,
            $_POST['question'][$i],
            $_POST['a'][$i],
            $_POST['b'][$i],
            $_POST['c'][$i],
            $_POST['d'][$i],
            $_POST['correct'][$i]
        );
        $qs->execute();
    }
    $success = "✅ Exam added successfully with $total_q questions";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Exam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/add_exam.css">
</head>

<body>
    <!------   adding sidebar file   ------>
    <?php include "sidebar.php"; ?>
    <!------   main start here   ------>
    <div class="main">
        <div class="page-card shadow-sm">
            <h5>Add New Exam</h5>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Exam Name</label>
                        <input type="text" name="exam_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-2">
                        <label>Attempts</label>
                        <input type="number" name="attempts" value="1" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" value="30" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Exam Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>Questions</label>
                        <input type="number" id="qCount" name="total_questions" value="5" min="1" class="form-control">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" onclick="generate()" class="btn px-1 btn-outline-primary w-100">
                            Generate
                        </button>
                    </div>
                </div>

                <div id="questions"></div>

                <button name="add_exam" class="btn btn-primary w-100 mt-3">
                    Add Exam
                </button>
            </form>
        </div>
    </div>
    <script src="assest/js/add_exam.js"></script>
</body>
</html>