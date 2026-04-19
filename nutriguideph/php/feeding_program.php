<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Employee', 'Admin', 'Super Admin']);

$conn = getDB();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-create table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS feedingRecord (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        student_id      INT NOT NULL,
        feed_date       DATE NOT NULL,
        meal_type       VARCHAR(30),
        notes           TEXT,
        recorded_by     VARCHAR(100),
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$msg = "";
$msg_type = "";

// Handle add feeding record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_feeding'])) {
    $student_id = intval($_POST['student_id']);
    $feed_date  = $_POST['feed_date'];
    $meal_type  = trim($_POST['meal_type']);
    $notes      = trim($_POST['notes']);
    $recorded_by = htmlspecialchars($_SESSION['firstName']);

    $stmt = $conn->prepare("INSERT INTO feedingRecord (student_id, feed_date, meal_type, notes, recorded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $student_id, $feed_date, $meal_type, $notes, $recorded_by);

    if ($stmt->execute()) {
        $msg = "Feeding record saved successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error saving feeding record.";
        $msg_type = "danger";
    }
    $stmt->close();
}

// Get all underweight students (latest record per student)
$underweight_students = $conn->query("
    SELECT s.* FROM stdRecord s
    INNER JOIN (
        SELECT std_first_name, std_last_name, MAX(created_at) as latest
        FROM stdRecord
        GROUP BY std_first_name, std_last_name
    ) latest_rec ON s.std_first_name = latest_rec.std_first_name
        AND s.std_last_name = latest_rec.std_last_name
        AND s.created_at = latest_rec.latest
    WHERE s.classification = 'Underweight'
    ORDER BY s.std_last_name, s.std_first_name
");

// Get recent feeding records
$feeding_records = $conn->query("
    SELECT f.*, s.std_first_name, s.std_mid_initial, s.std_last_name, s.bmi, s.classification
    FROM feedingRecord f
    JOIN stdRecord s ON f.student_id = s.id
    ORDER BY f.feed_date DESC, f.created_at DESC
    LIMIT 50
");

$total_enrolled = $underweight_students->num_rows;
$total_feedings = $conn->query("SELECT COUNT(*) as c FROM feedingRecord")->fetch_assoc()['c'];
$today_feedings = $conn->query("SELECT COUNT(*) as c FROM feedingRecord WHERE feed_date = CURDATE()")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(localStorage.getItem('nutriph_dark')==='1')document.documentElement.setAttribute('data-theme','dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide â€“ Feeding Program</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
</head>
<body style="background: linear-gradient(135deg, rgba(45,90,14,0.7), rgba(61,107,15,0.6)), url('../images/happy.jpg') center/cover no-repeat fixed; min-height:100vh;">

    <!-- Navbar -->
    <?php $activePage = 'feeding'; include 'navbar.php'; ?>


    <!-- Content -->
    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">

        <!-- Header -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-2" data-aos="fade-down">
            <div>
                <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-utensils me-2"></i>Feeding Program</h4>
                <p class="text-white-50 small mb-0">Only <b>Underweight</b> students are enrolled. Other classifications are not included.</p>
            </div>
            <a href="export_feeding.php" class="btn btn-outline-light btn-sm px-3">
                <i class="fa-solid fa-download me-2"></i>Download Excel
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show small py-2" data-aos="fade-down">
                <i class="fa-solid fa-<?= $msg_type === 'success' ? 'circle-check' : 'circle-exclamation' ?> me-1"></i>
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-2 g-lg-3 mb-4">
            <div class="col-6 col-lg-4" data-aos="fade-up">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#fdecea;color:#c0392b;">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                        <div>
                            <div class="stat-number" style="color:#c0392b;"><?= $total_enrolled ?></div>
                            <div class="text-muted small fw-semibold">Enrolled Students</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-4" data-aos="fade-up" data-aos-delay="50">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-bowl-food"></i>
                        </div>
                        <div>
                            <div class="stat-number text-success"><?= $total_feedings ?></div>
                            <div class="text-muted small fw-semibold">Total Feedings</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div>
                            <div class="stat-number text-warning"><?= $today_feedings ?></div>
                            <div class="text-muted small fw-semibold">Fed Today</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 g-lg-4">

            <!-- Enrolled Students -->
            <div class="col-lg-7" data-aos="fade-up">
                <div class="card table-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-clipboard-list me-2"></i>Enrolled Students (Underweight Only)</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small fw-semibold">Name</th>
                                        <th class="small fw-semibold">Gender</th>
                                        <th class="small fw-semibold">BMI</th>
                                        <th class="small fw-semibold">Weight</th>
                                        <th class="small fw-semibold">Guardian</th>
                                        <th class="small fw-semibold text-center">Feed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($underweight_students->num_rows > 0): ?>
                                        <?php while ($s = $underweight_students->fetch_assoc()): ?>
                                        <tr>
                                            <td class="small fw-semibold">
                                                <?= htmlspecialchars($s['std_first_name'] . ' ' . $s['std_mid_initial'] . ' ' . $s['std_last_name']) ?>
                                                <i class="fa-solid fa-triangle-exclamation ms-1 text-danger" style="font-size:0.65rem;" title="Underweight"></i>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($s['gender']) ?></td>
                                            <td class="small"><span class="badge rounded-pill badge-underweight"><?= $s['bmi'] ?></span></td>
                                            <td class="small"><?= htmlspecialchars($s['weight'] . ' ' . $s['weight_unit']) ?></td>
                                            <td class="small"><?= htmlspecialchars($s['guardian_name']) ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-green py-0 px-2 log-feed-btn"
                                                    data-id="<?= $s['id'] ?>"
                                                    data-name="<?= htmlspecialchars($s['std_first_name'] . ' ' . $s['std_mid_initial'] . ' ' . $s['std_last_name']) ?>">
                                                    <i class="fa-solid fa-plus" style="font-size:0.7rem;"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-face-smile fa-2x mb-2 opacity-25 d-block"></i>
                                                No underweight students found. All students are healthy!
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feeding History -->
            <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
                <div class="card table-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-clock-rotate-left me-2"></i>Feeding History</h6>
                        <?php if ($feeding_records->num_rows > 0): ?>
                            <div style="max-height:500px;overflow-y:auto;">
                            <?php while ($f = $feeding_records->fetch_assoc()): ?>
                                <div class="d-flex align-items-start gap-2 mb-2 p-2 rounded-3" style="background:#f8f9fa;border-left:3px solid #78bc27;">
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold"><?= htmlspecialchars($f['std_first_name'] . ' ' . $f['std_mid_initial'] . ' ' . $f['std_last_name']) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">
                                            <i class="fa-solid fa-calendar me-1"></i><?= date('M d, Y', strtotime($f['feed_date'])) ?>
                                            <span class="mx-1">Â·</span>
                                            <i class="fa-solid fa-utensils me-1"></i><?= htmlspecialchars($f['meal_type']) ?>
                                            <?php if ($f['notes']): ?>
                                                <span class="mx-1">Â·</span>
                                                <?= htmlspecialchars($f['notes']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.7rem;">By: <?= htmlspecialchars($f['recorded_by']) ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-bowl-food fa-2x mb-2 opacity-25 d-block"></i>
                                <p class="small">No feeding records yet. Use the <b>+</b> button to log a feeding.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Log Feeding Modal -->
    <div class="modal fade" id="feedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold"><i class="fa-solid fa-bowl-food me-2"></i>Log Feeding</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="add_feeding" value="1">
                        <input type="hidden" name="student_id" id="feed_student_id">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Student</label>
                            <input type="text" class="form-control form-control-sm" id="feed_student_name" readonly style="background:#f8f9fa;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Date</label>
                            <input type="date" class="form-control form-control-sm" name="feed_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Meal Type</label>
                            <select class="form-select form-select-sm" name="meal_type" required>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch" selected>Lunch</option>
                                <option value="Snack">Snack</option>
                                <option value="Dinner">Dinner</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Notes (optional)</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="e.g. Rice, chicken, vegetables"></textarea>
                        </div>

                        <button type="submit" class="btn btn-green w-100 py-2">
                            <i class="fa-solid fa-check me-2"></i>Save Feeding Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-section mt-4" id="footer">
        <div class="container py-5">
            <div class="row g-4 align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="../images/logo.png" alt="Logo" height="36" class="footer-logo">
                        <h5 class="mb-0 text-success-light">NutriPh Guide</h5>
                    </div>
                    <p class="text-muted small">A community-based monitoring and support system for tracking malnourished students at San Antonio Central School.</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success-light text-uppercase small fw-bold mb-3">Contact & Address</h6>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2"></i>abcdefg@gmail.com</li>
                        <li class="mb-2"><i class="fa-solid fa-location-dot me-2"></i>San Antonio Central School, Philippines</li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2"></i>+63 900 000 0000</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="text-center py-3 border-top border-secondary small text-muted footer-bottom">
            &copy; <?= date('Y') ?> NutriPh Guide &mdash; San Antonio Central School. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true });

        const feedModal = new bootstrap.Modal(document.getElementById('feedModal'));

        document.querySelectorAll('.log-feed-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('feed_student_id').value = this.dataset.id;
                document.getElementById('feed_student_name').value = this.dataset.name;
                feedModal.show();
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
