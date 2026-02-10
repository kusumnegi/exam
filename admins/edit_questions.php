<?php
session_start();
require "../config/db.php";

/*------   login check   ------*/
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

/*  GET / POST SAFE */
$exam_id = (int)($_REQUEST['exam_id'] ?? 0);
if ($exam_id <= 0) {
    header("Location: edit_exam.php");
    exit;
}

$exam = $conn->query("SELECT exam_name FROM exams WHERE id=$exam_id")->fetch_assoc();
if (!$exam) {
    header("Location: edit_exam.php");
    exit;
}

$success = "";

/* UPDATE TOTAL QUESTIONS */
function updateTotalQuestions($conn, $exam_id)
{
    $conn->query("
        UPDATE exams 
        SET total_questions = (
            SELECT COUNT(*) FROM exam_questions WHERE exam_id=$exam_id
        )
        WHERE id=$exam_id
    ");
}

/* ================= ADD ================= */
if (isset($_POST['add_question'])) {
    $stmt = $conn->prepare("
        INSERT INTO exam_questions
        (exam_id, question, option_a, option_b, option_c, option_d, correct_option)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "issssss",
        $exam_id,
        $_POST['question'],
        $_POST['a'],
        $_POST['b'],
        $_POST['c'],
        $_POST['d'],
        $_POST['correct']
    );
    $stmt->execute();
    updateTotalQuestions($conn, $exam_id);
    $success = "Question added successfully";
}

/* ================= UPDATE ================= */
if (isset($_POST['update_question'])) {
    $qid = (int)$_POST['question_id'];
    $stmt = $conn->prepare("
        UPDATE exam_questions SET
        question=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?
        WHERE id=? AND exam_id=?
    ");
    $stmt->bind_param(
        "ssssssii",
        $_POST['question'],
        $_POST['a'],
        $_POST['b'],
        $_POST['c'],
        $_POST['d'],
        $_POST['correct'],
        $qid,
        $exam_id
    );
    $stmt->execute();
    $success = "Question updated successfully";
}

/* ================= DELETE ================= */
if (isset($_POST['delete_question'])) {
    $qid = (int)$_POST['question_id'];
    $conn->query("DELETE FROM exam_questions WHERE id=$qid AND exam_id=$exam_id");
    updateTotalQuestions($conn, $exam_id);
    $success = "Question deleted successfully";
}

/* ALWAYS RE-FETCH QUESTIONS */
$questions = $conn->query("
    SELECT * FROM exam_questions 
    WHERE exam_id=$exam_id 
    ORDER BY id ASC
");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Questions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .q-card {
            background: #fff;
            border: 1px solid #d7dee8;
            border-radius: 10px;
            padding: 20px
        }

        .opt-label {
            width: 40px;
            font-weight: 600;
            background: #f4f6fa
        }
    </style>
</head>

<body>
    <!------   adding sidebar file   ------>
    <?php include "sidebar.php"; ?>
    <!------   main start here   ------>
    <div class="main">

        <h4>Edit Questions</h4>
        <p class="text-muted"><?= htmlspecialchars($exam['exam_name']) ?></p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- ADD QUESTION -->
        <div class="q-card mb-4">
            <form method="post">
                <input type="hidden" name="exam_id" value="<?= $exam_id ?>">

                <input name="question" class="form-control mb-2" placeholder="Question" required>

                <?php foreach (['a', 'b', 'c', 'd'] as $o): ?>
                    <div class="input-group mb-2">
                        <span class="input-group-text opt-label"><?= strtoupper($o) ?>.</span>
                        <input name="<?= $o ?>" class="form-control" required>
                    </div>
                <?php endforeach; ?>

                <select name="correct" class="form-select mb-3" required>
                    <option value="">Correct Option</option>
                    <option value="a">A</option>
                    <option value="b">B</option>
                    <option value="c">C</option>
                    <option value="d">D</option>
                </select>

                <button name="add_question" class="btn btn-primary">Add Question</button>
            </form>
        </div>

        <!-- ALL QUESTIONS -->
        <?php $i = 1;
        while ($q = $questions->fetch_assoc()): ?>
            <div class="q-card mb-3">
                <form method="post">
                    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">

                    <strong>Q<?= $i++ ?>.</strong>

                    <input name="question" value="<?= htmlspecialchars($q['question']) ?>" class="form-control mb-2" required>

                    <?php foreach (['a', 'b', 'c', 'd'] as $o): ?>
                        <div class="input-group mb-2">
                            <span class="input-group-text opt-label"><?= strtoupper($o) ?>.</span>
                            <input name="<?= $o ?>" value="<?= htmlspecialchars($q['option_' . $o]) ?>" class="form-control" required>
                        </div>
                    <?php endforeach; ?>

                    <select name="correct" class="form-select mb-2">
                        <?php foreach (['a', 'b', 'c', 'd'] as $o): ?>
                            <option value="<?= $o ?>" <?= $q['correct_option'] == $o ? 'selected' : '' ?>>
                                <?= strtoupper($o) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="d-flex gap-2">
                        <button name="update_question" class="btn btn-outline-primary btn-sm">Update</button>
                        <button name="delete_question" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Delete this question?')">
                            Delete
                        </button>
                    </div>

                </form>
            </div>
        <?php endwhile; ?>

    </div>
</body>

</html>