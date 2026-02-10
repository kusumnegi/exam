<?php
session_start();
require_once __DIR__ . "/config/db.php";

/* ===== LOGIN STATUS ===== */
$loggedIn  = isset($_SESSION['student_id']);
$studentId = $_SESSION['student_id'] ?? 0;
$showBlockedModal = false;

/* ===== BLOCK CHECK ===== */
if ($loggedIn) {
  $st = $conn->prepare("SELECT status FROM students WHERE id=? LIMIT 1");
  $st->bind_param("i", $studentId);
  $st->execute();
  $rs = $st->get_result();

  if ($rs->num_rows === 1 && $rs->fetch_assoc()['status'] == 0) {
    session_destroy();          // logout
    $loggedIn = false;
    $studentId = 0;
    $showBlockedModal = true;   // show modal
  }
}

/* ===== FETCH EXAMS ===== */
$today = date('Y-m-d');
$exams = $conn->query("
    SELECT id, exam_name, exam_image, total_questions,
           attempts, duration_minutes, end_date
    FROM exams
    WHERE start_date <= '$today'
      AND end_date >= '$today'
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Exam Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assest/css/index.css">
</head>

<body>
  <nav class="navbar navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">Exam Portal</a>

      <div class="nav-right ">
        <div class="search-box ms-auto">
          <input id="searchInput" class="form-control" placeholder="Search exams...">
          <i class="bi bi-search"></i>
          <div id="suggestions" class="list-group position-absolute w-100 d-none"></div>
        </div>

        <?php if ($loggedIn): ?>
          <a href="setting.php" class="setting-icon"><i class="bi bi-gear-fill"></i></a>
        <?php else: ?>
          <a href="signup.php" class="user-icon"><i class="bi bi-person-circle"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row g-4" id="examGrid">

      <?php while ($e = $exams->fetch_assoc()):
        $usedAttempts = 0;
        if ($loggedIn) {
          $st = $conn->prepare("SELECT COUNT(*) total FROM exam_attempts WHERE student_id=? AND exam_id=?");
          $st->bind_param("ii", $studentId, $e['id']);
          $st->execute();
          $usedAttempts = $st->get_result()->fetch_assoc()['total'];
        }
        $done = $usedAttempts >= $e['attempts'];
      ?>
        <div class="col-lg-4 col-md-6 exam-col">
          <div class="exam-card h-100" data-name="<?= strtolower($e['exam_name']) ?>">
            <img src="uploads/<?= htmlspecialchars($e['exam_image']) ?>" class="exam-img w-100">
            <div class="p-3">
              <h6 class="fw-semibold"><?= htmlspecialchars($e['exam_name']) ?></h6>
              <p class="text-muted small">
                Questions: <?= $e['total_questions'] ?> |
                Attempts: <?= $usedAttempts ?>/<?= $e['attempts'] ?>
              </p>

              <?php if (!$loggedIn): ?>
                <a href="login.php" class="btn start-btn text-white w-100">Start Exam</a>
              <?php else: ?>
                <button class="btn start-btn text-white w-100 <?= $done ? 'disabled-btn' : '' ?>" <?= $done ? 'disabled' : '' ?>
                  data-bs-toggle="modal" data-bs-target="#instructionModal"
                  data-exam="<?= $e['id'] ?>"
                  data-name="<?= htmlspecialchars($e['exam_name']) ?>"
                  data-duration="<?= $e['duration_minutes'] ?>"
                  data-used="<?= $usedAttempts ?>"
                  data-max="<?= $e['attempts'] ?>"
                  data-end="<?= $e['end_date'] ?>">
                  <?= $done ? 'Attempts Completed' : 'Start Exam' ?>
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>

    </div>

    <div id="noResult" class="text-center text-muted fw-semibold mt-5 d-none">
      No exams found
    </div>
  </div>

  <!-- MODAL -->
  <div class="modal fade" id="instructionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content exam-modal shadow-lg border-0 rounded-4">

        <div class="modal-header exam-modal-header px-4 py-3 border-bottom">
          <div>
            <h5 class="modal-title fw-bold mb-0" id="modalExamName">Exam Instructions</h5>
            <small class="text-muted">Please review the instructions before starting</small>
          </div>
          <span class="attempt-badge ms-auto me-3" id="attemptText"></span>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body exam-modal-body px-4 py-4">

          <div class=" d-flex align-items-center gap-3 mb-2">
            <i class="bi bi-calendar-event text-danger fs-4"></i>
            <div class="fw-semibold">
              Exam Valid Till:
              <span class="fw-bold text-danger" id="examEndDate"></span>
            </div>
          </div>

          <div class=" d-flex align-items-center gap-3 mb-4">
            <i class="bi bi-clock-history text-primary fs-4"></i>
            <div class="fw-semibold">
              Duration: <span id="examDuration" class="fw-bold text-primary"></span> minutes
            </div>
          </div>

          <ul class="instruction-list ps-3">
            <li>Timer starts immediately after clicking Start Exam.</li>
            <li>Auto submit after time completion.</li>
            <li>Closing browser may submit exam.</li>
            <li>Any malpractice leads to disqualification.</li>
          </ul>

          <div class="form-check mt-4 p-3 border rounded-3 bg-light">
            <input class="form-check-input" type="checkbox" id="agree">
            <label class="form-check-label fw-semibold">
              I have read and understood the instructions.
            </label>
          </div>

        </div>

        <div class="modal-footer px-4 py-3">
          <form action="exam.php" method="get" class="ms-auto">
            <input type="hidden" name="exam_id" id="examId">
            <button type="submit" id="startExamBtn" class="btn btn-primary" disabled>Start Exam</button>
          </form>
        </div>

      </div>
    </div>
  </div>


  <!-- BLOCKED ACCOUNT MODAL -->
  <div class="modal fade" id="blockedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow rounded-4">

        <div class="modal-header bg-danger text-white rounded-top-4">
          <h5 class="modal-title">
            <i class="bi bi-shield-lock-fill me-2"></i>
            Account Blocked
          </h5>
        </div>

        <div class="modal-body text-center py-4">
          <p class="mb-2 fw-semibold">
            Your account has been blocked by the administrator.
          </p>
          <p class="text-muted small mb-0">
            For security reasons, you have been logged out.<br>
            Please contact support for further assistance.
          </p>
        </div>

        <div class="modal-footer justify-content-center border-0 pb-4">
          <a href="index.php" class="btn btn-danger px-4">
            OK
          </a>
        </div>

      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assest/js/index.js"> </script>

  <?php if ($showBlockedModal): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const modal = new bootstrap.Modal(
          document.getElementById("blockedModal"), {
            backdrop: 'static',
            keyboard: false
          }
        );
        modal.show();
      });
    </script>
  <?php endif; ?>
</body>
</html>