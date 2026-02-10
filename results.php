<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit;
}

$studentId = $_SESSION['student_id'];

/* FETCH ALL RESULTS */
$stmt = $conn->prepare("
    SELECT 
        ea.*,
        e.exam_name
    FROM exam_attempts ea
    JOIN exams e ON e.id = ea.exam_id
    WHERE ea.student_id=?
    ORDER BY ea.created_at DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$results = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/results.css">
</head>

<body>

    <div class="wrapper">

        <!-- HEADER WITH HOME BUTTON -->
        <div class="page-header">
            <div class="page-title">My Exam Results</div>
            <a href="index.php" class="btn btn-outline-dark">
                Home
            </a>
        </div>

        <?php while ($r = $results->fetch_assoc()): ?>
            <div class="result-row">
                <div>
                    <div class="exam-name"><?= htmlspecialchars($r['exam_name']) ?></div>
                    <div class="exam-date">
                        <?= date("d M Y, h:i A", strtotime($r['created_at'])) ?>
                    </div>
                </div>
                <button class="btn btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#result<?= $r['id'] ?>">
                    View Result
                </button>
            </div>

            <!-- ===== MODAL ===== -->
            <div class="modal fade" id="result<?= $r['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">

                        <div class="modal-body">
                            <div class="card">

                                <div class="header">
                                    <div>
                                        <h1><?= htmlspecialchars($r['exam_name']) ?></h1>
                                        <p>Attempt <?= $r['attempt_no'] ?> · Assessment Summary</p>
                                    </div>
                                    <div class="score <?= $r['status'] == 'PASS' ? 'pass' : 'fail' ?>">
                                        <div class="value"><?= $r['percentage'] ?>%</div>
                                    </div>
                                </div>

                                <div class="divider"></div>

                                <div class="stats">
                                    <div class="stat">
                                        <span>Total Questions</span>
                                        <b><?= $r['total_questions'] ?></b>
                                    </div>
                                    <div class="stat">
                                        <span>Attempted</span>
                                        <b><?= $r['attempted'] ?></b>
                                    </div>
                                    <div class="stat">
                                        <span>Correct</span>
                                        <b><?= $r['correct'] ?></b>
                                    </div>
                                    <div class="stat">
                                        <span>Incorrect</span>
                                        <b><?= $r['attempted'] - $r['correct'] ?></b>
                                    </div>
                                </div>

                                <div class="footer">
                                    <span class="badge <?= $r['status'] == 'PASS' ? 'pass' : 'fail' ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                                        Back
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endwhile; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>