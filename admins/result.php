<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* ===== ADMIN AUTH ===== */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ===== FILTERS ===== */
$search = trim($_GET['search'] ?? "");
$examId = (int)($_GET['exam_id'] ?? 0);

/* ===== EXAMS FOR DROPDOWN ===== */
$examList = $conn->query("SELECT id, exam_name FROM exams ORDER BY exam_name");

/* ===== MAIN QUERY (ONLY REAL COLUMNS) ===== */
$sql = "
SELECT
    ea.attempt_no,
    ea.total_questions,
    ea.attempted,
    ea.correct,
    ea.percentage,
    ea.status,
    ea.created_at,

    s.name   AS student_name,
    s.roll_no,
    s.email,

    e.exam_name
FROM exam_attempts ea
JOIN students s ON s.id = ea.student_id
JOIN exams e    ON e.id = ea.exam_id
WHERE 1
";

$params = [];
$types  = "";

/* SEARCH */
if ($search !== "") {
    $sql .= " AND (s.name LIKE ? OR s.email LIKE ? OR s.roll_no LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

/* EXAM FILTER */
if ($examId > 0) {
    $sql .= " AND e.id = ?";
    $params[] = $examId;
    $types .= "i";
}

$sql .= " ORDER BY ea.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-box {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        }

        .badge-pass {
            background: #dcfce7;
            color: #166534 !important;
        }

        .badge-fail {
            background: #fee2e2;
            color: #991b1b !important;
        }

        @media(max-width:992px) {
            .main-content {
                margin-left: 0
            }
        }
    </style>
</head>

<body>

    <!------   adding sidebar file   ------>
    <?php include "sidebar.php"; ?>
    <!------   main start here   ------>
    <div class="main">

        <h4 class="fw-semibold mb-4">Results</h4>

        <!-- FILTER -->
        <form method="get" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    class="form-control" placeholder="Search student / email / roll">
            </div>

            <div class="col-md-3">
                <select name="exam_id" class="form-select">
                    <option value="">All Exams</option>
                    <?php while ($ex = $examList->fetch_assoc()): ?>
                        <option value="<?= $ex['id'] ?>" <?= $examId == $ex['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ex['exam_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="card-box">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Exam</th>
                            <th>Correct</th>
                            <th>Attempted</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Attempt</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if ($results->num_rows === 0): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No results found
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php $i = 1;
                        while ($r = $results->fetch_assoc()):
                            $pass = $r['percentage'] >= 40;
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>

                                <td>
                                    <strong><?= htmlspecialchars($r['student_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($r['roll_no']) ?></small>
                                </td>

                                <td><?= htmlspecialchars($r['exam_name']) ?></td>

                                <td><?= $r['correct'] ?> / <?= $r['total_questions'] ?></td>

                                <td><?= $r['attempted'] ?></td>

                                <td><?= $r['percentage'] ?>%</td>

                                <td>
                                    <span class="badge <?= $pass ? 'badge-pass' : 'badge-fail' ?>">
                                        <?= $pass ? 'Pass' : 'Fail' ?>
                                    </span>
                                </td>

                                <td><?= $r['attempt_no'] ?></td>

                                <td><?= date("d M Y, h:i A", strtotime($r['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>