<?php
session_start();
require_once 'auth.php';
checkAccess(['Parent/Guardian']);

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { die("Connection failed"); }

$email = $_SESSION['user_id'];

// Find student records where guardian_email matches the parent's login email
$stmt = $conn->prepare("SELECT * FROM stdRecord WHERE guardian_email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $email);
$stmt->execute();
$records = $stmt->get_result();

// Get feeding records for those students
$stmt2 = $conn->prepare("
    SELECT f.*, s.std_first_name, s.std_mid_initial, s.std_last_name
    FROM feedingRecord f
    JOIN stdRecord s ON f.student_id = s.id
    WHERE s.guardian_email = ?
    ORDER BY f.feed_date DESC
    LIMIT 20
");
$stmt2->bind_param("s", $email);
$stmt2->execute();
$feedings = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide – Parent Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
</head>
<body style="background: linear-gradient(135deg, rgba(45,90,14,0.7), rgba(61,107,15,0.6)), url('../images/happy.jpg') center/cover no-repeat fixed; min-height:100vh;">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-3 px-lg-5">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../index.php">
                <img src="../images/logo.png" alt="Logo" height="40">
                <span class="fw-bold brand-text">NutriPh Guide</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto gap-1 align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="parent_dashboard.php">My Child's Records</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-success-light fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user me-1"></i><?= htmlspecialchars($_SESSION['firstName']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3" style="min-width:200px;">
                            <li class="px-3 py-2">
                                <div class="small fw-bold"><?= htmlspecialchars($_SESSION['firstName']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;">Parent/Guardian</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small" href="parent_dashboard.php"><i class="fa-solid fa-child me-2 text-success"></i>My Child's Records</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Offcanvas -->
    <div class="offcanvas offcanvas-end text-bg-dark" id="mobileSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title brand-text">NutriPh Guide</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav gap-2">
                <li class="nav-item"><a class="nav-link text-white" href="parent_dashboard.php"><i class="fa-solid fa-child me-2"></i>My Child's Records</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Content -->
    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">

        <div class="mb-4" data-aos="fade-down">
            <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-child me-2"></i>My Child's Health Records</h4>
            <p class="text-white-50 small mb-0">View your child's BMI records and feeding program history. Records are matched by your registered email.</p>
        </div>

        <?php if ($records->num_rows > 0): ?>
            <!-- Child Records -->
            <div class="card table-card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-clipboard-list me-2"></i>Health Records</h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small fw-semibold">Name</th>
                                    <th class="small fw-semibold">Gender</th>
                                    <th class="small fw-semibold">Height</th>
                                    <th class="small fw-semibold">Weight</th>
                                    <th class="small fw-semibold">BMI</th>
                                    <th class="small fw-semibold">Classification</th>
                                    <th class="small fw-semibold">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $records->fetch_assoc()):
                                    $cls = $row['classification'];
                                    $badge = match($cls) {
                                        'Underweight'  => 'badge-underweight',
                                        'Normal Weight' => 'badge-normal',
                                        'Overweight'   => 'badge-overweight',
                                        default        => 'badge-obese'
                                    };
                                ?>
                                <tr>
                                    <td class="small fw-semibold">
                                        <?= htmlspecialchars($row['std_first_name'] . ' ' . $row['std_mid_initial'] . ' ' . $row['std_last_name']) ?>
                                        <?php if ($cls !== 'Normal Weight'): ?>
                                            <i class="fa-solid fa-triangle-exclamation ms-1 text-danger" style="font-size:0.7rem;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($row['gender']) ?></td>
                                    <td class="small"><?= htmlspecialchars($row['height'] . ' ' . $row['height_unit']) ?></td>
                                    <td class="small"><?= htmlspecialchars($row['weight'] . ' ' . $row['weight_unit']) ?></td>
                                    <td class="small fw-bold"><?= $row['bmi'] ?></td>
                                    <td><span class="badge rounded-pill <?= $badge ?>"><?= $cls ?></span></td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Feeding History -->
            <?php if ($feedings->num_rows > 0): ?>
            <div class="card table-card shadow-sm mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-utensils me-2"></i>Feeding Program History</h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small fw-semibold">Child</th>
                                    <th class="small fw-semibold">Date</th>
                                    <th class="small fw-semibold">Meal</th>
                                    <th class="small fw-semibold">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($f = $feedings->fetch_assoc()): ?>
                                <tr>
                                    <td class="small fw-semibold"><?= htmlspecialchars($f['std_first_name'] . ' ' . $f['std_mid_initial'] . ' ' . $f['std_last_name']) ?></td>
                                    <td class="small"><?= date('M d, Y', strtotime($f['feed_date'])) ?></td>
                                    <td class="small"><?= htmlspecialchars($f['meal_type']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($f['notes'] ?: '—') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="card shadow-sm rounded-4 border-0" data-aos="fade-up">
                <div class="card-body text-center py-5">
                    <i class="fa-solid fa-child fa-3x mb-3 text-muted opacity-25"></i>
                    <h6 class="fw-bold text-muted">No Records Found</h6>
                    <p class="text-muted small mb-0">No student records are linked to your email (<b><?= htmlspecialchars($email) ?></b>).<br>Please contact the school if you believe this is an error.</p>
                </div>
            </div>
        <?php endif; ?>
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
                    <p class="text-muted small">A community-based monitoring and support system for San Antonio Central School.</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success-light text-uppercase small fw-bold mb-3">Contact</h6>
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
    <script>AOS.init({ duration: 700, once: true });</script>
</body>
</html>
<?php $stmt->close(); $stmt2->close(); $conn->close(); ?>
