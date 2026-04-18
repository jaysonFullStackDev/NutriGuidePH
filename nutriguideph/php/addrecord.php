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

$save_msg = "";
$save_type = "";

// ── Save Record ────────────────────────────────────────────────────────────
if (isset($_POST['save_record'])) {
    $ln             = $_POST["lname"];
    $fn             = $_POST["fname"];
    $mid            = $_POST["m_initial"];
    $gender         = $_POST["gender"];
    $height         = $_POST["height"];
    $h_unit         = $_POST["measurement_unit"];
    $weight         = $_POST["weight"];
    $w_unit         = $_POST["weight_unit"];
    $classification = $_POST["classification"];
    $bmi            = $_POST["bmi"];
    $pname          = $_POST["p_name"];
    $contact        = $_POST["number"];
    $email          = $_POST["email"];

    $stmt = $conn->prepare("INSERT INTO stdRecord (std_last_name, std_first_name, std_mid_initial, gender, height, height_unit, weight, weight_unit, classification, bmi, guardian_name, guardian_number, guardian_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssss", $ln, $fn, $mid, $gender, $height, $h_unit, $weight, $w_unit, $classification, $bmi, $pname, $contact, $email);

    if ($stmt->execute()) {
        $save_msg  = "Record saved successfully!";
        $save_type = "success";

        if (in_array($classification, ['Underweight', 'Overweight', 'Obese']) && !empty($email)) {
            require_once 'mailer.php';
            $studentName = "$fn $mid $ln";
            sendMalnourishmentEmail($email, $pname, $studentName, $bmi, $height, $h_unit, $weight, $w_unit, $classification);
        }
    } else {
        $save_msg  = "Error saving record. Please try again.";
        $save_type = "error";
    }
    $stmt->close();
}

// ── Calculate BMI on Submit ────────────────────────────────────────────────
$preview = null;
if (isset($_POST['submit_form'])) {
    $fn     = $_POST["fname"];
    $mid    = $_POST["m_initial"];
    $ln     = $_POST["lname"];
    $bday   = $_POST["birthday"];
    $gender = $_POST["gender"];
    $weight = $_POST["weight"];
    $w_unit = $_POST["weight_unit"];
    $height = $_POST["height"];
    $h_unit = $_POST["measurement_unit"];
    $pname  = $_POST["p_name"];
    $contact= $_POST["number"];
    $email  = $_POST["email"];

    if ($h_unit == "cm")   { $h_m = $height / 100; }
    elseif ($h_unit == "m"){ $h_m = $height; }
    elseif ($h_unit == "inch") { $h_m = $height * 0.0254; }
    else                   { $h_m = $height * 0.3048; }

    $w_kg = ($w_unit == "lbs") ? round($weight * 0.453592, 2) : $weight;
    $bmi = round($w_kg / ($h_m * $h_m), 1);

    if      ($bmi < 18.5)  { $classification = "Underweight"; }
    elseif  ($bmi <= 24.9) { $classification = "Normal Weight"; }
    elseif  ($bmi <= 29.9) { $classification = "Overweight"; }
    else                   { $classification = "Obese"; }

    $preview = compact('fn','mid','ln','bday','gender','weight','w_unit','height','h_unit','bmi','classification','pname','contact','email');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide – Add Record</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item">
                        <span class="nav-link text-success-light fw-semibold"><i class="fa-solid fa-user me-1"></i>Hi, <?= htmlspecialchars($_SESSION['firstName']) ?></span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
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
                <li class="nav-item"><a class="nav-link text-white" href="#footer"><i class="fa-solid fa-circle-info me-2"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">
        <div class="row g-3 g-lg-4">

            <!-- Form Card -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="card border-0 shadow-sm rounded-4" style="border-top: 5px solid #78bc27 !important;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-success mb-4">
                            <i class="fa-solid fa-clipboard-list me-2"></i>Student Record Entry
                        </h5>

                        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                            <p class="section-label mb-3">Student Information</p>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-muted">First Name *</label>
                                    <input type="text" class="form-control" name="fname" value="<?= htmlspecialchars($preview['fn'] ?? '') ?>" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-muted">Last Name *</label>
                                    <input type="text" class="form-control" name="lname" value="<?= htmlspecialchars($preview['ln'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-4 col-sm-3">
                                    <label class="form-label small fw-semibold text-muted">M.I.</label>
                                    <input type="text" class="form-control" name="m_initial" maxlength="2" value="<?= htmlspecialchars($preview['mid'] ?? '') ?>">
                                </div>
                                <div class="col-8 col-sm-5">
                                    <label class="form-label small fw-semibold text-muted">Birthday</label>
                                    <input type="date" class="form-control" name="birthday" value="<?= htmlspecialchars($preview['bday'] ?? '') ?>">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label small fw-semibold text-muted">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="Male" <?= (($preview['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= (($preview['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>
                            </div>

                            <p class="section-label mb-3 mt-4">Physical Measurements</p>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Height *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="height" step="any" placeholder="e.g. 120" value="<?= $preview['height'] ?? '' ?>" required>
                                    <select class="form-select" name="measurement_unit" style="max-width: 100px;">
                                        <option value="cm" <?= (($preview['h_unit'] ?? 'cm') == 'cm') ? 'selected' : '' ?>>cm</option>
                                        <option value="m" <?= (($preview['h_unit'] ?? '') == 'm') ? 'selected' : '' ?>>m</option>
                                        <option value="inch" <?= (($preview['h_unit'] ?? '') == 'inch') ? 'selected' : '' ?>>inch</option>
                                        <option value="feet" <?= (($preview['h_unit'] ?? '') == 'feet') ? 'selected' : '' ?>>feet</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Weight *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="weight" step="any" placeholder="e.g. 35" value="<?= $preview['weight'] ?? '' ?>" required>
                                    <select class="form-select" name="weight_unit" style="max-width: 100px;">
                                        <option value="kg" <?= (($preview['w_unit'] ?? 'kg') == 'kg') ? 'selected' : '' ?>>kg</option>
                                        <option value="lbs" <?= (($preview['w_unit'] ?? '') == 'lbs') ? 'selected' : '' ?>>lbs</option>
                                    </select>
                                </div>
                            </div>

                            <p class="section-label mb-3 mt-4">Guardian Information</p>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Guardian Name</label>
                                <input type="text" class="form-control" name="p_name" placeholder="Full name" value="<?= htmlspecialchars($preview['pname'] ?? '') ?>">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-muted">Contact Number</label>
                                    <input type="text" class="form-control" name="number" placeholder="+63 900 000 0000" value="<?= htmlspecialchars($preview['contact'] ?? '') ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-muted">Guardian Email</label>
                                    <input type="email" class="form-control" name="email" placeholder="email@example.com" value="<?= htmlspecialchars($preview['email'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" name="submit_form" class="btn btn-green w-100 py-2 mt-2">
                                <i class="fa-solid fa-calculator me-2"></i>Calculate BMI & Preview
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="card border-0 shadow-sm rounded-4" style="border-top: 5px solid #78bc27 !important;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-success mb-4">
                            <i class="fa-solid fa-chart-column me-2"></i>Record Preview
                        </h5>

                        <?php if ($save_msg): ?>
                            <div class="alert alert-<?= $save_type === 'success' ? 'success' : 'danger' ?> small py-2">
                                <i class="fa-solid fa-<?= $save_type === 'success' ? 'circle-check' : 'circle-exclamation' ?> me-1"></i>
                                <?= htmlspecialchars($save_msg) ?>
                                <?php if ($save_type === 'success' && in_array($preview['classification'] ?? '', ['Underweight', 'Overweight', 'Obese'])): ?>
                                    <br><small><i class="fa-solid fa-envelope me-1"></i>A health alert has been sent to the guardian's email.</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($preview): ?>
                            <?php
                                $cls = $preview['classification'];
                                $badgeClass = match($cls) {
                                    'Underweight'  => 'badge-underweight',
                                    'Normal Weight'=> 'badge-normal',
                                    'Overweight'   => 'badge-overweight',
                                    default        => 'badge-obese'
                                };
                            ?>

                            <?php if ($cls !== 'Normal Weight'): ?>
                                <?php
                                    $alertMsg = match($cls) {
                                        'Underweight' => 'This student is classified as <b>Underweight (Malnourished)</b>.',
                                        'Overweight'  => 'This student is classified as <b>Overweight (At Risk)</b>.',
                                        'Obese'       => 'This student is classified as <b>Obese (High Risk)</b>.',
                                        default       => ''
                                    };
                                    $alertType = ($cls === 'Underweight' || $cls === 'Obese') ? 'danger' : 'warning';
                                ?>
                                <div class="alert alert-<?= $alertType ?> small py-2">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    <strong>Health Alert Detected</strong><br>
                                    <?= $alertMsg ?> Saving will send a health alert to the guardian's email.
                                </div>
                            <?php endif; ?>

                            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                                <input type="hidden" name="fname" value="<?= htmlspecialchars($preview['fn']) ?>">
                                <input type="hidden" name="m_initial" value="<?= htmlspecialchars($preview['mid']) ?>">
                                <input type="hidden" name="lname" value="<?= htmlspecialchars($preview['ln']) ?>">
                                <input type="hidden" name="gender" value="<?= htmlspecialchars($preview['gender']) ?>">
                                <input type="hidden" name="height" value="<?= htmlspecialchars($preview['height']) ?>">
                                <input type="hidden" name="measurement_unit" value="<?= htmlspecialchars($preview['h_unit']) ?>">
                                <input type="hidden" name="weight" value="<?= htmlspecialchars($preview['weight']) ?>">
                                <input type="hidden" name="weight_unit" value="<?= htmlspecialchars($preview['w_unit']) ?>">
                                <input type="hidden" name="bmi" value="<?= htmlspecialchars($preview['bmi']) ?>">
                                <input type="hidden" name="classification" value="<?= htmlspecialchars($cls) ?>">
                                <input type="hidden" name="p_name" value="<?= htmlspecialchars($preview['pname']) ?>">
                                <input type="hidden" name="number" value="<?= htmlspecialchars($preview['contact']) ?>">
                                <input type="hidden" name="email" value="<?= htmlspecialchars($preview['email']) ?>">

                                <table class="table table-sm table-borderless mb-3">
                                    <tr><th class="text-muted small" style="width:140px">Student Name</th><td><?= htmlspecialchars("{$preview['fn']} {$preview['mid']} {$preview['ln']}") ?></td></tr>
                                    <tr><th class="text-muted small">Birthday</th><td><?= htmlspecialchars($preview['bday']) ?></td></tr>
                                    <tr><th class="text-muted small">Gender</th><td><?= htmlspecialchars($preview['gender']) ?></td></tr>
                                    <tr><th class="text-muted small">Height</th><td><?= htmlspecialchars("{$preview['height']} {$preview['h_unit']}") ?></td></tr>
                                    <tr><th class="text-muted small">Weight</th><td><?= htmlspecialchars("{$preview['weight']} {$preview['w_unit']}") ?></td></tr>
                                    <tr><th class="text-muted small">BMI</th><td><strong><?= $preview['bmi'] ?></strong></td></tr>
                                    <tr><th class="text-muted small">Classification</th><td><span class="badge rounded-pill <?= $badgeClass ?>"><?= $cls ?></span></td></tr>
                                    <tr><th class="text-muted small">Guardian</th><td><?= htmlspecialchars($preview['pname']) ?></td></tr>
                                    <tr><th class="text-muted small">Contact</th><td><?= htmlspecialchars($preview['contact']) ?></td></tr>
                                    <tr><th class="text-muted small">Email</th><td><?= htmlspecialchars($preview['email']) ?></td></tr>
                                </table>

                                <div class="d-flex gap-2">
                                    <button type="submit" name="save_record" class="btn btn-green flex-fill">
                                        <i class="fa-solid fa-floppy-disk me-1"></i>Save
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.scrollTo({top:0,behavior:'smooth'})">
                                        <i class="fa-solid fa-pen me-1"></i>Edit
                                    </button>
                                    <button type="button" class="btn btn-outline-danger flex-fill" onclick="if(confirm('Clear this record?')) location.href='addrecord.php'">
                                        <i class="fa-solid fa-trash me-1"></i>Discard
                                    </button>
                                </div>
                            </form>

                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-file-pen fa-3x mb-3 opacity-25"></i>
                                <p>Fill out the form and click<br><strong>Calculate BMI & Preview</strong> to see the result here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
    <script>AOS.init({ duration: 700, once: true });</script>
</body>
</html>
