<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/signin.html");
    exit();
}

require_once 'auth.php';
checkAccess(['Employee', 'Admin', 'Super Admin']);

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stats
$total = $conn->query("SELECT COUNT(*) as c FROM stdRecord")->fetch_assoc()['c'];
$underweight = $conn->query("SELECT COUNT(*) as c FROM stdRecord WHERE classification='Underweight'")->fetch_assoc()['c'];
$normal = $conn->query("SELECT COUNT(*) as c FROM stdRecord WHERE classification='Normal Weight'")->fetch_assoc()['c'];
$overweight = $conn->query("SELECT COUNT(*) as c FROM stdRecord WHERE classification='Overweight'")->fetch_assoc()['c'];
$obese = $conn->query("SELECT COUNT(*) as c FROM stdRecord WHERE classification='Obese'")->fetch_assoc()['c'];
$males = $conn->query("SELECT COUNT(*) as c FROM stdRecord WHERE gender='Male'")->fetch_assoc()['c'];
$females = $conn->query("SELECT COUNT(*) as c FROM stdRecord WHERE gender='Female'")->fetch_assoc()['c'];

// Recent records
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM stdRecord WHERE std_first_name LIKE ? OR std_last_name LIKE ? OR classification LIKE ? ORDER BY created_at DESC LIMIT 50");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $records = $stmt->get_result();
} else {
    $records = $conn->query("SELECT * FROM stdRecord ORDER BY created_at DESC LIMIT 50");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide – Dashboard</title>
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="feeding_program.php">Feeding Program</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-success-light fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user me-1"></i><?= htmlspecialchars($_SESSION['firstName']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3" style="min-width:200px;">
                            <li class="px-3 py-2">
                                <div class="small fw-bold"><?= htmlspecialchars($_SESSION['firstName']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($_SESSION['role'] ?? 'User') ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small" href="#" id="openProfileBtn"><i class="fa-solid fa-user-pen me-2 text-success"></i>My Profile</a></li>
                            <li><a class="dropdown-item small" href="#" id="openPasswordBtn"><i class="fa-solid fa-key me-2 text-warning"></i>Change Password</a></li>
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
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="feeding_program.php"><i class="fa-solid fa-utensils me-2"></i>Feeding Program</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#" id="openProfileBtnMobile"><i class="fa-solid fa-user-pen me-2"></i>My Profile</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#" id="openPasswordBtnMobile"><i class="fa-solid fa-key me-2"></i>Change Password</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#footer"><i class="fa-solid fa-circle-info me-2"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">

        <!-- Header -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-2" data-aos="fade-down">
            <div>
                <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-gauge me-2"></i>Dashboard</h4>
                <p class="text-white-50 small mb-0">Overview of student nutrition records</p>
            </div>
            <a href="addrecord.php" class="btn btn-green px-4">
                <i class="fa-solid fa-plus me-2"></i>Add Record
            </a>
        </div>

        <?php $canEdit = isAdmin(); ?>

        <!-- Stat Cards -->
        <div class="row g-2 g-lg-3 mb-4">
            <div class="col-6 col-lg-3" data-aos="fade-up">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <div class="stat-number text-primary"><?= $total ?></div>
                            <div class="text-muted small fw-semibold">Total Students</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="50">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#fdecea;color:#c0392b;">
                            <i class="fa-solid fa-arrow-down"></i>
                        </div>
                        <div>
                            <div class="stat-number" style="color:#c0392b;"><?= $underweight ?></div>
                            <div class="text-muted small fw-semibold">Underweight</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <div class="stat-number text-success"><?= $normal ?></div>
                            <div class="text-muted small fw-semibold">Normal</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="150">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="fa-solid fa-arrow-up"></i>
                        </div>
                        <div>
                            <div class="stat-number text-warning"><?= $overweight ?></div>
                            <div class="text-muted small fw-semibold">Overweight</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second row: Obese + Gender -->
        <div class="row g-2 g-lg-3 mb-4">
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#f8d7da;color:#721c24;">
                            <i class="fa-solid fa-exclamation"></i>
                        </div>
                        <div>
                            <div class="stat-number" style="color:#721c24;"><?= $obese ?></div>
                            <div class="text-muted small fw-semibold">Obese</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="250">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#d6eaf8;color:#2471a3;">
                            <i class="fa-solid fa-mars"></i>
                        </div>
                        <div>
                            <div class="stat-number" style="color:#2471a3;"><?= $males ?></div>
                            <div class="text-muted small fw-semibold">Male</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#f5d0e0;color:#c2185b;">
                            <i class="fa-solid fa-venus"></i>
                        </div>
                        <div>
                            <div class="stat-number" style="color:#c2185b;"><?= $females ?></div>
                            <div class="text-muted small fw-semibold">Female</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="350">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#fdecea;color:#e74c3c;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <div class="stat-number" style="color:#e74c3c;"><?= $underweight + $obese ?></div>
                            <div class="text-muted small fw-semibold">At Risk</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-3 g-lg-4 mb-4">
            <div class="col-md-6" data-aos="fade-up">
                <div class="card chart-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-chart-pie me-2"></i>BMI Distribution</h6>
                        <canvas id="bmiChart" height="260"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="card chart-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-venus-mars me-2"></i>Gender Distribution</h6>
                        <canvas id="genderChart" height="260"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="card table-card shadow-sm mb-4" data-aos="fade-up">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <h6 class="fw-bold text-success mb-0"><i class="fa-solid fa-table-list me-2"></i>Recent Records</h6>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?php if ($canEdit): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-success btn-sm dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-download me-1"></i>Download
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item small" href="export_records.php?type=all"><i class="fa-solid fa-table me-2 text-primary"></i>All Records</a></li>
                                <li><a class="dropdown-item small" href="export_records.php?type=underweight"><i class="fa-solid fa-arrow-down me-2 text-danger"></i>Underweight Only</a></li>
                                <li><a class="dropdown-item small" href="export_records.php?type=normal"><i class="fa-solid fa-check me-2 text-success"></i>Normal Only</a></li>
                                <li><a class="dropdown-item small" href="export_records.php?type=overweight"><i class="fa-solid fa-arrow-up me-2 text-warning"></i>Overweight Only</a></li>
                                <li><a class="dropdown-item small" href="export_records.php?type=obese"><i class="fa-solid fa-exclamation me-2 text-danger"></i>Obese Only</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item small" href="export_records.php?type=malnourished"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>All Malnourished</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <form method="GET" class="d-flex gap-2" style="max-width:280px;width:100%;">
                            <input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-green btn-sm px-3"><i class="fa-solid fa-search"></i></button>
                        </form>
                    </div>
                </div>
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
                                <th class="small fw-semibold">Guardian</th>
                                <th class="small fw-semibold">Date</th>
                                <?php if ($canEdit): ?><th class="small fw-semibold text-center">Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($records->num_rows > 0): ?>
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
                                    <td class="small">
                                        <a href="#" class="text-decoration-none fw-semibold student-name-link" style="color:#2d5a0e;"
                                           data-fn="<?= htmlspecialchars($row['std_first_name']) ?>"
                                           data-ln="<?= htmlspecialchars($row['std_last_name']) ?>"
                                           data-mid="<?= htmlspecialchars($row['std_mid_initial']) ?>">
                                            <?= htmlspecialchars($row['std_first_name'] . ' ' . $row['std_mid_initial'] . ' ' . $row['std_last_name']) ?>
                                        </a>
                                        <?php if ($cls !== 'Normal Weight'): ?>
                                            <i class="fa-solid fa-triangle-exclamation ms-1 text-danger" style="font-size:0.7rem;" title="Malnourished – <?= $cls ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($row['gender']) ?></td>
                                    <td class="small"><?= htmlspecialchars($row['height'] . ' ' . $row['height_unit']) ?></td>
                                    <td class="small"><?= htmlspecialchars($row['weight'] . ' ' . $row['weight_unit']) ?></td>
                                    <td class="small fw-bold"><?= $row['bmi'] ?></td>
                                    <td><span class="badge rounded-pill <?= $badge ?>"><?= $cls ?></span></td>
                                    <td class="small"><?= htmlspecialchars($row['guardian_name']) ?></td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <?php if ($canEdit): ?>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-success py-0 px-2 edit-record-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-fn="<?= htmlspecialchars($row['std_first_name']) ?>"
                                            data-ln="<?= htmlspecialchars($row['std_last_name']) ?>"
                                            data-mid="<?= htmlspecialchars($row['std_mid_initial']) ?>"
                                            data-gender="<?= htmlspecialchars($row['gender']) ?>"
                                            data-height="<?= $row['height'] ?>"
                                            data-hunit="<?= htmlspecialchars($row['height_unit']) ?>"
                                            data-weight="<?= $row['weight'] ?>"
                                            data-wunit="<?= htmlspecialchars($row['weight_unit']) ?>"
                                            data-guardian="<?= htmlspecialchars($row['guardian_name']) ?>"
                                            data-gnum="<?= htmlspecialchars($row['guardian_number']) ?>"
                                            data-gemail="<?= htmlspecialchars($row['guardian_email']) ?>">
                                            <i class="fa-solid fa-pen-to-square" style="font-size:0.75rem;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2 delete-record-btn" data-id="<?= $row['id'] ?>">
                                            <i class="fa-solid fa-trash" style="font-size:0.75rem;"></i>
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $canEdit ? 9 : 8 ?>" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-inbox fa-2x mb-2 opacity-25 d-block"></i>
                                        <?= $search ? 'No records match your search.' : 'No records yet. Start by adding a student record.' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold"><i class="fa-solid fa-user-pen me-2"></i>My Profile</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="profileModalBody">
                    <div class="text-center py-4"><div class="spinner-border text-success" role="status"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold"><i class="fa-solid fa-key me-2"></i>Change Password</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="changePasswordForm">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Current Password</label>
                            <input type="password" class="form-control form-control-sm" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">New Password</label>
                            <input type="password" class="form-control form-control-sm" name="new_password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Confirm New Password</label>
                            <input type="password" class="form-control form-control-sm" name="confirm_password" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-green w-100 py-2">
                            <i class="fa-solid fa-key me-2"></i>Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Record</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success small py-2 d-none" id="editSuccessMsg"><i class="fa-solid fa-circle-check me-1"></i><span id="editSuccessText"></span></div>
                    <div class="alert alert-danger small py-2 d-none" id="editErrorMsg"><i class="fa-solid fa-circle-exclamation me-1"></i><span id="editErrorText"></span></div>
                    <form id="editRecordForm">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3 mb-3">
                            <div class="col-5">
                                <label class="form-label small fw-semibold text-muted">First Name</label>
                                <input type="text" class="form-control form-control-sm" name="fname" id="edit_fn" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-semibold text-muted">Last Name</label>
                                <input type="text" class="form-control form-control-sm" name="lname" id="edit_ln" required>
                            </div>
                            <div class="col-2">
                                <label class="form-label small fw-semibold text-muted">M.I.</label>
                                <input type="text" class="form-control form-control-sm" name="m_initial" id="edit_mid" maxlength="2">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Gender</label>
                            <select class="form-select form-select-sm" name="gender" id="edit_gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-8">
                                <label class="form-label small fw-semibold text-muted">Height</label>
                                <input type="number" class="form-control form-control-sm" name="height" id="edit_height" step="any" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold text-muted">Unit</label>
                                <select class="form-select form-select-sm" name="height_unit" id="edit_hunit">
                                    <option value="cm">cm</option>
                                    <option value="m">m</option>
                                    <option value="inch">inch</option>
                                    <option value="feet">feet</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-8">
                                <label class="form-label small fw-semibold text-muted">Weight</label>
                                <input type="number" class="form-control form-control-sm" name="weight" id="edit_weight" step="any" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold text-muted">Unit</label>
                                <select class="form-select form-select-sm" name="weight_unit" id="edit_wunit">
                                    <option value="kg">kg</option>
                                    <option value="lbs">lbs</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Guardian Name</label>
                            <input type="text" class="form-control form-control-sm" name="guardian_name" id="edit_guardian">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Guardian Number</label>
                                <input type="text" class="form-control form-control-sm" name="guardian_number" id="edit_gnum">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Guardian Email</label>
                                <input type="email" class="form-control form-control-sm" name="guardian_email" id="edit_gemail">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-green w-100 py-2">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Update Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Student History Modal -->
    <div class="modal fade" id="studentHistoryModal" tabindex="-1" aria-labelledby="studentHistoryLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold" id="studentHistoryLabel">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>Student Record History
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="historyModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="text-muted small mt-2">Loading records...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-section" id="footer">
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true });

        // Navbar scroll effect
        const nav = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('navbar-scrolled', window.scrollY > 50);
        });

        // Toast notification helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const id = 'toast_' + Date.now();
            const icon = type === 'success' ? 'circle-check' : (type === 'error' ? 'circle-exclamation' : 'triangle-exclamation');
            const html = '<div id="' + id + '" class="toast-custom toast-' + type + ' mb-2"><div class="toast-body d-flex align-items-center gap-2"><i class="fa-solid fa-' + icon + '"></i> ' + message + '</div></div>';
            container.insertAdjacentHTML('beforeend', html);
            setTimeout(() => { const el = document.getElementById(id); if (el) el.remove(); }, 4000);
        }

        // BMI Pie Chart
        new Chart(document.getElementById('bmiChart'), {
            type: 'pie',
            data: {
                labels: ['Underweight', 'Normal', 'Overweight', 'Obese'],
                datasets: [{
                    data: [<?= $underweight ?>, <?= $normal ?>, <?= $overweight ?>, <?= $obese ?>],
                    backgroundColor: ['#e74c3c', '#27ae60', '#f39c12', '#8e44ad'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } }
                }
            }
        });

        // Gender Doughnut Chart
        new Chart(document.getElementById('genderChart'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [<?= $males ?>, <?= $females ?>],
                    backgroundColor: ['#2471a3', '#c2185b'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } }
                }
            }
        });

        // Student History Modal
        const historyModal = new bootstrap.Modal(document.getElementById('studentHistoryModal'));
        const modalBody = document.getElementById('historyModalBody');
        const modalLabel = document.getElementById('studentHistoryLabel');

        document.querySelectorAll('.student-name-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const fn = this.dataset.fn;
                const ln = this.dataset.ln;
                const mid = this.dataset.mid;
                const fullName = fn + ' ' + mid + ' ' + ln;

                modalLabel.innerHTML = '<i class="fa-solid fa-clock-rotate-left me-2"></i>' + fullName;
                modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success" role="status"></div><p class="text-muted small mt-2">Loading records...</p></div>';
                historyModal.show();

                fetch('get_student_history.php?fn=' + encodeURIComponent(fn) + '&ln=' + encodeURIComponent(ln))
                    .then(r => r.json())
                    .then(data => {
                        if (data.error || !data.records || data.records.length === 0) {
                            modalBody.innerHTML = '<div class="text-center text-muted py-4"><i class="fa-solid fa-inbox fa-2x mb-2 opacity-25"></i><p>No records found.</p></div>';
                            return;
                        }

                        const recs = data.records;
                        const latest = recs[0];
                        const isMalnourished = latest.classification !== 'Normal Weight';

                        function getBadge(cls) {
                            const map = { 'Underweight':'badge-underweight', 'Normal Weight':'badge-normal', 'Overweight':'badge-overweight', 'Obese':'badge-obese' };
                            return map[cls] || 'badge-obese';
                        }

                        function getAlertType(cls) {
                            if (cls === 'Normal Weight') return '';
                            if (cls === 'Underweight') return 'Underweight – Malnourished';
                            if (cls === 'Overweight') return 'Overweight – At Risk';
                            return 'Obese – High Risk';
                        }

                        let html = '';

                        // Malnourishment alert for latest record
                        if (isMalnourished) {
                            const alertColor = latest.classification === 'Underweight' ? '#c0392b' : (latest.classification === 'Overweight' ? '#e67e22' : '#8e44ad');
                            const alertBg = latest.classification === 'Underweight' ? '#fff5f5' : (latest.classification === 'Overweight' ? '#fff8f0' : '#faf0ff');
                            html += '<div style="background:' + alertBg + ';border-left:4px solid ' + alertColor + ';border-radius:8px;padding:12px 16px;margin-bottom:16px;">';
                            html += '<div style="color:' + alertColor + ';font-weight:700;font-size:0.9rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + getAlertType(latest.classification) + '</div>';
                            html += '<div class="text-muted small mt-1">Latest BMI: <b>' + latest.bmi + '</b> — Recorded on ' + formatDate(latest.created_at) + '</div>';
                            html += '</div>';
                        }

                        // Latest record card
                        html += '<div class="card border-0 shadow-sm rounded-3 mb-3" style="border-left:4px solid #78bc27 !important;">';
                        html += '<div class="card-body p-3">';
                        html += '<div class="d-flex justify-content-between align-items-center mb-2">';
                        html += '<span class="fw-bold small text-success"><i class="fa-solid fa-star me-1"></i>Latest Record</span>';
                        html += '<span class="badge rounded-pill ' + getBadge(latest.classification) + '">' + latest.classification + '</span>';
                        html += '</div>';
                        html += buildRecordDetails(latest);
                        html += '</div></div>';

                        // Previous records
                        if (recs.length > 1) {
                            html += '<h6 class="fw-bold small text-muted mt-4 mb-3"><i class="fa-solid fa-history me-1"></i>Previous Records (' + (recs.length - 1) + ')</h6>';
                            for (let i = 1; i < recs.length; i++) {
                                const r = recs[i];
                                const prevMalnourished = r.classification !== 'Normal Weight';
                                const borderColor = prevMalnourished ? '#e74c3c' : '#dee2e6';
                                html += '<div class="card border-0 shadow-sm rounded-3 mb-2" style="border-left:4px solid ' + borderColor + ' !important;">';
                                html += '<div class="card-body p-3">';
                                html += '<div class="d-flex justify-content-between align-items-center mb-2">';
                                html += '<span class="text-muted small">' + formatDate(r.created_at) + '</span>';
                                html += '<span class="badge rounded-pill ' + getBadge(r.classification) + '">' + r.classification + '</span>';
                                if (prevMalnourished) html += ' <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size:0.7rem;" title="' + getAlertType(r.classification) + '"></i>';
                                html += '</div>';
                                html += buildRecordDetails(r);
                                html += '</div></div>';
                            }
                        } else {
                            html += '<div class="text-center text-muted small py-3 mt-3" style="background:#f8f9fa;border-radius:8px;"><i class="fa-solid fa-info-circle me-1"></i>This is the only record for this student.</div>';
                        }

                        modalBody.innerHTML = html;
                    })
                    .catch(() => {
                        modalBody.innerHTML = '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation fa-2x mb-2"></i><p>Failed to load records.</p></div>';
                    });
            });
        });

        function buildRecordDetails(r) {
            let h = '<div class="row g-2 small">';
            h += '<div class="col-6 col-md-3"><span class="text-muted">Gender:</span> <b>' + esc(r.gender) + '</b></div>';
            h += '<div class="col-6 col-md-3"><span class="text-muted">Height:</span> <b>' + esc(r.height) + ' ' + esc(r.height_unit) + '</b></div>';
            h += '<div class="col-6 col-md-3"><span class="text-muted">Weight:</span> <b>' + esc(r.weight) + ' ' + esc(r.weight_unit) + '</b></div>';
            h += '<div class="col-6 col-md-3"><span class="text-muted">BMI:</span> <b>' + r.bmi + '</b></div>';
            if (r.guardian_name) h += '<div class="col-12 mt-1"><span class="text-muted">Guardian:</span> ' + esc(r.guardian_name) + '</div>';
            h += '</div>';
            return h;
        }

        function formatDate(d) {
            const dt = new Date(d);
            return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function esc(s) {
            if (!s) return '';
            const el = document.createElement('span');
            el.textContent = s;
            return el.innerHTML;
        }

        // Edit Record Modal
        const editModal = new bootstrap.Modal(document.getElementById('editRecordModal'));

        document.querySelectorAll('.edit-record-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_fn').value = this.dataset.fn;
                document.getElementById('edit_ln').value = this.dataset.ln;
                document.getElementById('edit_mid').value = this.dataset.mid;
                document.getElementById('edit_gender').value = this.dataset.gender;
                document.getElementById('edit_height').value = this.dataset.height;
                document.getElementById('edit_hunit').value = this.dataset.hunit;
                document.getElementById('edit_weight').value = this.dataset.weight;
                document.getElementById('edit_wunit').value = this.dataset.wunit;
                document.getElementById('edit_guardian').value = this.dataset.guardian;
                document.getElementById('edit_gnum').value = this.dataset.gnum;
                document.getElementById('edit_gemail').value = this.dataset.gemail;
                document.getElementById('editSuccessMsg').classList.add('d-none');
                document.getElementById('editErrorMsg').classList.add('d-none');
                editModal.show();
            });
        });

        // Profile Modal
        const profileModalEl = new bootstrap.Modal(document.getElementById('profileModal'));
        const profileBody = document.getElementById('profileModalBody');
        const passwordModalEl = new bootstrap.Modal(document.getElementById('passwordModal'));

        function openProfile() {
            profileBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success" role="status"></div></div>';
            profileModalEl.show();
            fetch('get_profile.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { profileBody.innerHTML = '<p class="text-danger text-center">Failed to load profile.</p>'; return; }
                    const u = data.user;
                    const joined = new Date(u.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                    let html = '';
                    html += '<div class="text-center mb-4">';
                    html += '<div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:72px;height:72px;background:#f4ffe8;color:#78bc27;font-size:1.8rem;font-weight:800;">' + esc(u.firstName.charAt(0)) + esc(u.lastName.charAt(0)) + '</div>';
                    html += '<h6 class="fw-bold mb-0">' + esc(u.firstName) + ' ' + esc(u.lastName) + '</h6>';
                    html += '<span class="badge bg-success-subtle text-success rounded-pill mt-1" style="font-size:0.75rem;">' + esc(u.role) + '</span>';
                    html += '</div>';
                    html += '<form id="profileEditForm">';
                    html += '<input type="hidden" name="action" value="update_info">';
                    html += '<div class="row g-3 mb-3">';
                    html += '<div class="col-6"><label class="form-label small fw-semibold text-muted">First Name</label><input type="text" class="form-control form-control-sm" name="firstName" value="' + esc(u.firstName) + '" required></div>';
                    html += '<div class="col-6"><label class="form-label small fw-semibold text-muted">Last Name</label><input type="text" class="form-control form-control-sm" name="lastName" value="' + esc(u.lastName) + '" required></div>';
                    html += '</div>';
                    html += '<div class="mb-3"><label class="form-label small fw-semibold text-muted">Email</label><input type="email" class="form-control form-control-sm" name="email" value="' + esc(u.email) + '" required></div>';
                    html += '<div class="mb-3"><label class="form-label small fw-semibold text-muted">Role</label><input type="text" class="form-control form-control-sm" value="' + esc(u.role) + '" disabled></div>';
                    html += '<div class="d-flex justify-content-between align-items-center small text-muted mb-3">';
                    html += '<span><i class="fa-solid fa-calendar me-1"></i>Joined: ' + joined + '</span>';
                    if (u.consent_agreed == 1) html += '<span class="text-success"><i class="fa-solid fa-shield-check me-1"></i>Consent Given</span>';
                    html += '</div>';
                    html += '<button type="submit" class="btn btn-green w-100 py-2"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>';
                    html += '</form>';
                    profileBody.innerHTML = html;

                    document.getElementById('profileEditForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        fetch('update_profile.php', { method: 'POST', body: new FormData(this) })
                            .then(r => r.json())
                            .then(res => {
                                if (res.success) {
                                    profileModalEl.hide();
                                    showToast('Profile updated successfully!');
                                    setTimeout(() => location.reload(), 1200);
                                } else {
                                    showToast(res.error || 'Update failed.', 'error');
                                }
                            })
                            .catch(() => showToast('Network error.', 'error'));
                    });
                })
                .catch(() => { profileBody.innerHTML = '<p class="text-danger text-center">Failed to load profile.</p>'; });
        }

        document.getElementById('openProfileBtn').addEventListener('click', e => { e.preventDefault(); openProfile(); });
        document.getElementById('openProfileBtnMobile').addEventListener('click', e => { e.preventDefault(); bootstrap.Offcanvas.getInstance(document.getElementById('mobileSidebar'))?.hide(); openProfile(); });

        function openPassword() { passwordModalEl.show(); }
        document.getElementById('openPasswordBtn').addEventListener('click', e => { e.preventDefault(); openPassword(); });
        document.getElementById('openPasswordBtnMobile').addEventListener('click', e => { e.preventDefault(); bootstrap.Offcanvas.getInstance(document.getElementById('mobileSidebar'))?.hide(); openPassword(); });

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('update_profile.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        passwordModalEl.hide();
                        this.reset();
                        showToast('Password changed successfully!');
                    } else {
                        showToast(res.error || 'Password change failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));
        });

        // Delete Record
        document.querySelectorAll('.delete-record-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to delete this record? This cannot be undone.')) return;
                const id = this.dataset.id;
                const row = this.closest('tr');
                fetch('delete_record.php', {
                    method: 'POST',
                    body: new URLSearchParams({ id: id })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => { row.remove(); showToast('Record deleted successfully.'); }, 300);
                    } else {
                        showToast(data.error || 'Delete failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));
            });
        });

        // Edit Record - use toast instead of inline alerts
        document.getElementById('editRecordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('update_record.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        editModal.hide();
                        showToast('Record updated! BMI: ' + data.bmi + ' (' + data.classification + ')');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        document.getElementById('editErrorText').textContent = data.error || 'Update failed.';
                        document.getElementById('editErrorMsg').classList.remove('d-none');
                        document.getElementById('editSuccessMsg').classList.add('d-none');
                    }
                })
                .catch(() => {
                    document.getElementById('editErrorText').textContent = 'Network error. Try again.';
                    document.getElementById('editErrorMsg').classList.remove('d-none');
                });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
