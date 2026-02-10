<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['student_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$studentId = $_SESSION['student_id'];
$examId    = intval($_POST['exam_id'] ?? 0);
$answers   = $_POST['answers'] ?? [];

if ($examId <= 0) {
    header("Location: index.php");
    exit;
}

/* 🚫 NO CACHE */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* ================= FETCH EXAM ================= */
$examStmt = $conn->prepare("SELECT exam_name FROM exams WHERE id=?");
$examStmt->bind_param("i", $examId);
$examStmt->execute();
$exam = $examStmt->get_result()->fetch_assoc();

/* ================= FETCH QUESTIONS ================= */
$qStmt = $conn->prepare("
    SELECT id, correct_option
    FROM exam_questions
    WHERE exam_id=?
");
$qStmt->bind_param("i", $examId);
$qStmt->execute();
$qResult = $qStmt->get_result();

$totalQuestions = $qResult->num_rows;
$attempted = 0;
$correct   = 0;

while ($q = $qResult->fetch_assoc()) {
    if (!empty($answers[$q['id']])) {
        $attempted++;
        if (strtoupper($answers[$q['id']]) === strtoupper($q['correct_option'])) {
            $correct++;
        }
    }
}

$wrong = $attempted - $correct;
$percentage = $totalQuestions > 0
    ? round(($correct / $totalQuestions) * 100, 2)
    : 0;

$status = ($percentage >= 33) ? "PASS" : "FAIL";

/* ================= ATTEMPT NUMBER ================= */
$attemptStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM exam_attempts
    WHERE student_id=? AND exam_id=?
");
$attemptStmt->bind_param("ii", $studentId, $examId);
$attemptStmt->execute();
$attemptNo = $attemptStmt->get_result()->fetch_assoc()['total'] + 1;

/* ================= SAVE RESULT ================= */
$save = $conn->prepare("
    INSERT INTO exam_attempts
    (student_id, exam_id, attempt_no, total_questions, attempted, correct, percentage, status)
    VALUES (?,?,?,?,?,?,?,?)
");
$save->bind_param(
    "iiiiidis",
    $studentId,
    $examId,
    $attemptNo,
    $totalQuestions,
    $attempted,
    $correct,
    $percentage,
    $status
);
$save->execute();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assessment Result</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assest/css/submit.css">
</head>

<body>

    <div class="page">
        <div class="card">

            <div class="header">
                <div>
                    <h1><?= htmlspecialchars($exam['exam_name']) ?></h1>
                    <p>Attempt <?= $attemptNo ?> · Assessment Summary</p>
                </div>
                <div class="score <?= $status === 'PASS' ? 'pass' : 'fail' ?>">
                    <div class="value"><?= $percentage ?>%</div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="stats">
                <div class="stat">
                    <span>Total Questions</span>
                    <b><?= $totalQuestions ?></b>
                </div>
                <div class="stat">
                    <span>Attempted</span>
                    <b><?= $attempted ?></b>
                </div>
                <div class="stat">
                    <span>Correct</span>
                    <b><?= $correct ?></b>
                </div>
                <div class="stat">
                    <span>Incorrect</span>
                    <b><?= $wrong ?></b>
                </div>
            </div>

            <div class="footer">
                <span class="badge <?= $status === 'PASS' ? 'pass' : 'fail' ?>">
                    <?= $status ?>
                </span>
                <a href="index.php" class="link">Return to dashboard</a>
            </div>

        </div>
    </div>
    <script>
        // push state once (prevents back)
        history.pushState({
            page: "result"
        }, "", "");

        window.addEventListener("popstate", function(e) {
            // ONLY when user presses back
            if (e.state && e.state.page === "result") {
                window.location.href = "index.php";
            }
        });

        // prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>


</body>

</html>