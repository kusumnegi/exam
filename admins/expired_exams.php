<?php
session_start();
require "../config/db.php";

/*------   login check   ------*/
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

$success = "";

/*------   delete exam  ------*/
if (isset($_POST['delete_exam'])) {
    $exam_id = (int)$_POST['exam_id'];

    $conn->query("DELETE FROM exam_questions WHERE exam_id = $exam_id");
    $conn->query("DELETE FROM exams WHERE id = $exam_id");

    $success = "✅ Exam deleted successfully";
}

/* ================= UPDATE EXAM ================= */
if (isset($_POST['update_exam'])) {

    $exam_id = (int)$_POST['exam_id'];
    $old = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();

    $exam_name  = $_POST['exam_name'] ?? $old['exam_name'];
    $start_date = $_POST['start_date'] ?? $old['start_date'];
    $end_date   = $_POST['end_date'] ?? $old['end_date'];
    $attempts   = $_POST['attempts'] ?? $old['attempts'];
    $duration   = $_POST['duration_minutes'] ?? $old['duration_minutes'];
    $exam_image = $old['exam_image'];

    /* IMAGE UPDATE */
    if (!empty($_FILES['exam_image']['name'])) {
        $ext = pathinfo($_FILES['exam_image']['name'], PATHINFO_EXTENSION);
        $exam_image = time() . "_" . rand(100, 999) . "." . $ext;
        move_uploaded_file($_FILES['exam_image']['tmp_name'], "../uploads/" . $exam_image);
    }

    $stmt = $conn->prepare("
        UPDATE exams SET
            exam_name = ?,
            start_date = ?,
            end_date = ?,
            attempts = ?,
            duration_minutes = ?,
            exam_image = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sssiisi",
        $exam_name,
        $start_date,
        $end_date,
        $attempts,
        $duration,
        $exam_image,
        $exam_id
    );
    $stmt->execute();

    $success = "✅ Exam updated successfully";
}

/* ================= FETCH EXPIRED EXAMS ================= */
$today = date("Y-m-d");

$exams = $conn->query("
    SELECT * FROM exams
    WHERE end_date < '$today'
    ORDER BY end_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expired Exams</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 14px;
        }
    </style>
</head>

<body>

    <!------   adding sidebar file   ------>
    <?php include "sidebar.php"; ?>
    <!------   main start here   ------>
    <div class="main">

        <h4 class="mb-3">Expired Exams</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($exams->num_rows == 0): ?>
            <div class="alert alert-info">No expired exams found.</div>
        <?php endif; ?>

        <?php while ($row = $exams->fetch_assoc()): ?>

            <?php
            $qCount = $conn->query(
                "SELECT COUNT(*) AS total FROM exam_questions WHERE exam_id = {$row['id']}"
            )->fetch_assoc()['total'];
            ?>

            <div class="card mb-3 shadow-sm">
                <div class="card-body">

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="exam_id" value="<?= $row['id'] ?>">

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Exam Name</label>
                                <input type="text" name="exam_name"
                                    value="<?= htmlspecialchars($row['exam_name']) ?>"
                                    class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date"
                                    value="<?= $row['start_date'] ?>" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date"
                                    value="<?= $row['end_date'] ?>" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Attempts</label>
                                <input type="number" name="attempts"
                                    value="<?= $row['attempts'] ?>" class="form-control">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label">Duration</label>
                                <input type="number" name="duration_minutes"
                                    value="<?= $row['duration_minutes'] ?>"
                                    class="form-control">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Questions</label>
                                <input type="number"
                                    value="<?= $qCount ?>"
                                    class="form-control" readonly>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Exam Image</label>
                                <input type="file" name="exam_image" class="form-control">
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2 flex-wrap">
                            <button name="update_exam" class="btn btn-success btn-sm">
                                <i class="bi bi-save"></i> Update Details
                            </button>

                            <a href="edit_questions.php?exam_id=<?= $row['id'] ?>"
                                class="btn btn-primary btn-sm">
                                <i class="bi bi-list-check"></i> Edit Questions
                            </a>

                            <button type="button"
                                class="btn btn-danger btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal<?= $row['id'] ?>">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- DELETE MODAL -->
            <div class="modal fade" id="deleteModal<?= $row['id'] ?>">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            Delete <b><?= htmlspecialchars($row['exam_name']) ?></b>?
                            <br><small class="text-danger">All questions will be deleted.</small>
                        </div>

                        <div class="modal-footer">
                            <form method="post">
                                <input type="hidden" name="exam_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button name="delete_exam" class="btn btn-danger">Delete</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        <?php endwhile; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>