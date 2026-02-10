<?php
session_start();
require "config/db.php";
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit;
}

$examId = intval($_GET['exam_id'] ?? 0);
if ($examId <= 0) {
    header("Location: index.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$exam = $conn->query("SELECT * FROM exams WHERE id=$examId")->fetch_assoc();
if (!$exam) {
    header("Location: index.php");
    exit;
}

$qRes = $conn->query("
    SELECT id, question, option_a, option_b, option_c, option_d
    FROM exam_questions
    WHERE exam_id=$examId
    ORDER BY id ASC
");
$questions = $qRes->fetch_all(MYSQLI_ASSOC);
$timeLimit = intval($exam['duration_minutes']) * 60;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($exam['exam_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assest/css/exam.css">
</head>

<body>
    <div class="exam-header">
        <h6><?= htmlspecialchars($exam['exam_name']) ?></h6>
        <div class="timer" id="timer">00:00</div>
    </div>

    <div class="q-strip">
        <?php foreach ($questions as $i => $q): ?>
            <div class="q-box" onclick="goTo(<?= $i ?>)"><?= $i + 1 ?></div>
        <?php endforeach; ?>
    </div>

    <form id="examForm" method="post" action="submit.php">
        <input type="hidden" name="exam_id" value="<?= $examId ?>">
        <?php foreach ($questions as $q): ?>
            <input type="hidden" name="answers[<?= $q['id'] ?>]" id="ans_<?= $q['id'] ?>">
        <?php endforeach; ?>

        <div class="exam-wrap">
            <div id="qTitle" class="text-muted"></div>
            <div id="qText" class="fw-bold fs-5 my-3"></div>
            <div id="options"></div>

            <div class="actions">
                <button type="button" class="btn btn-outline-secondary" onclick="prevQ()">Previous</button>
                <button type="button" class="btn btn-outline-primary" onclick="nextQ()">Next</button>
                <button type="button" class="btn btn-danger" onclick="openSubmitModal()">Submit</button>
            </div>
        </div>
    </form>

    <!-- 🔥 CONFIRM MODAL -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                </div>
                <div class="modal-body text-center" id="modalText"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmYes">Yes, Continue</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const examKey = "exam_<?= $examId ?>";
        const questions = <?= json_encode($questions) ?>;
        const duration = <?= $timeLimit ?>;

        let state = JSON.parse(localStorage.getItem(examKey)) || {
            startTime: Date.now(),
            current: 0,
            visited: [],
            answers: {}
        };

        let {
            startTime,
            current,
            visited,
            answers
        } = state;
        const qBoxes = document.querySelectorAll(".q-box");
        let allowNavigation = false;
        let submitType = "submit";

        const modal = new bootstrap.Modal(document.getElementById("confirmModal"));

        /* 🔒 BLOCK BACK */
        history.pushState(null, "", location.href);
        window.addEventListener("popstate", () => {
            if (!allowNavigation) {
                openExitModal();
                history.pushState(null, "", location.href);
            }
        });

        /* TIMER */
        const timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const remaining = Math.max(duration - elapsed, 0);
            timer.innerText =
                String(Math.floor(remaining / 60)).padStart(2, '0') + ":" +
                String(remaining % 60).padStart(2, '0');
            if (remaining <= 0) safeSubmit();
        }, 1000);

        function saveState() {
            localStorage.setItem(examKey, JSON.stringify({
                startTime,
                current,
                visited,
                answers
            }));
        }

        function render() {
            const q = questions[current];
            qTitle.innerText = `Question ${current+1} of ${questions.length}`;
            qText.innerText = q.question;
            options.innerHTML = "";

            ["A", "B", "C", "D"].forEach(opt => {
                const label = document.createElement("label");
                label.className = "option";
                const letter = document.createElement("span");
                letter.className = "opt-letter";
                letter.innerText = opt + ".";
                const radio = document.createElement("input");
                radio.type = "radio";
                radio.name = "temp";
                radio.value = opt;
                if (answers[q.id] === opt) radio.checked = true;
                radio.onchange = () => {
                    answers[q.id] = opt;
                    document.getElementById("ans_" + q.id).value = opt;
                    visited[current] = true;
                    saveState();
                    updateColors();
                };
                const text = document.createElement("span");
                text.innerText = q["option_" + opt.toLowerCase()];
                label.append(letter, radio, text);
                options.appendChild(label);
            });
            visited[current] = true;
            saveState();
            updateColors();
        }

        function updateColors() {
            qBoxes.forEach((b, i) => {
                b.className = "q-box";
                if (i === current) b.classList.add("q-current");
                else if (answers[questions[i].id]) b.classList.add("q-answered");
                else if (visited[i]) b.classList.add("q-visited");
            });
        }

        function goTo(i) {
            current = i;
            saveState();
            render();
        }

        function nextQ() {
            if (current < questions.length - 1) {
                current++;
                saveState();
                render();
            }
        }

        function prevQ() {
            if (current > 0) {
                current--;
                saveState();
                render();
            }
        }

        /* ===== MODALS ===== */
        function openExitModal() {
            submitType = "exit";
            modalTitle.innerText = "Exit Exam?";
            modalText.innerText =
                "Are you sure you want to exit this exam?\n" +
                "Your exam will be submitted and counted as an attempt.";
            modal.show();
        }

        function openSubmitModal() {
            submitType = "submit";
            modalTitle.innerText = "Submit Exam?";
            modalText.innerText =
                "Are you sure you want to submit the exam? ";
            modal.show();
        }

        document.getElementById("confirmYes").onclick = () => safeSubmit();

        function safeSubmit() {
            allowNavigation = true;
            localStorage.removeItem(examKey);
            clearInterval(timerInterval);
            examForm.submit();
        }

        render();
    </script>

</body>

</html>