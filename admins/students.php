<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/*------   login check   ------*/
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/*------   block/unblock  ------*/
if (isset($_POST['toggle_status'])) {
    $studentId = (int)$_POST['student_id'];
    $newStatus = (int)$_POST['new_status'];

    $stmt = $conn->prepare("UPDATE students SET status=? WHERE id=?");
    $stmt->bind_param("ii", $newStatus, $studentId);
    $stmt->execute();

    header("Location: students.php");
    exit;
}

/* ========= SEARCH ========= */
$search = trim($_GET['search'] ?? "");

if ($search !== "") {
    $stmt = $conn->prepare("
        SELECT id, name, email, roll_no, status, created_at
        FROM students
        WHERE name LIKE ? OR email LIKE ? OR roll_no LIKE ?
        ORDER BY id DESC
    ");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query("
        SELECT id, name, email, roll_no, status, created_at
        FROM students
        ORDER BY id DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Students Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .table-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06)
        }

        .badge-active {
            background: #dcfce7 !important;
            color: #166534 !important
        }

        .badge-blocked {
            background: #fee2e2 !important;
            color: #991b1b !important
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-semibold">Students</h4>
            <form method="get" style="max-width:300px">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    class="form-control" placeholder="Search name / email / roll">
            </form>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Roll No</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if ($students->num_rows == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No students found</td>
                            </tr>
                        <?php endif; ?>

                        <?php $i = 1;
                        while ($s = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['roll_no']) ?></td>
                                <td>
                                    <?php if ($s['status'] == 1): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-blocked">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date("d M Y", strtotime($s['created_at'])) ?></td>
                                <td class="text-end">

                                    <button
                                        class="btn btn-sm <?= $s['status'] ? 'btn-danger' : 'btn-success' ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmModal"
                                        data-id="<?= $s['id'] ?>"
                                        data-name="<?= htmlspecialchars($s['name']) ?>"
                                        data-status="<?= $s['status'] ?>">
                                        <?= $s['status'] ? 'Block' : 'Unblock' ?>
                                    </button>

                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ===== CONFIRM MODAL ===== -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Confirm Action</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="modalStudentId">
                        <input type="hidden" name="new_status" id="modalNewStatus">

                        <p class="mb-0">
                            Are you sure you want to
                            <strong id="modalAction"></strong>
                            student
                            <strong id="modalStudentName"></strong>?
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="toggle_status" class="btn btn-primary">
                            Yes, Confirm
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <script src="assest/js/students.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>