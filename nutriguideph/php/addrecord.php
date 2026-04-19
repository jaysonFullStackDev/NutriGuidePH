<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Employee', 'Admin', 'Super Admin']);

$conn = getDB();

$save_msg = "";
$save_type = "";

// â”€â”€ Save Record â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (isset($_POST['save_record'])) {
    verifyCsrf();
    $ln             = $_POST["lname"];
    $fn             = $_POST["fname"];
    $mid            = $_POST["m_initial"];
    $gender         = $_POST["gender"];
    $grade          = $_POST["grade_level"] ?? '';
    $section        = $_POST["section"] ?? '';
    $school_year    = $_POST["school_year"] ?? '';
    $height         = $_POST["height"];
    $h_unit         = $_POST["measurement_unit"];
    $weight         = $_POST["weight"];
    $w_unit         = $_POST["weight_unit"];
    $classification = $_POST["classification"];
    $bmi            = $_POST["bmi"];
    $pname          = $_POST["p_name"];
    $contact        = $_POST["number"];
    $email          = $_POST["email"];

    $stmt = $conn->prepare("INSERT INTO stdRecord (std_last_name, std_first_name, std_mid_initial, gender, grade_level, section, school_year, height, height_unit, weight, weight_unit, classification, bmi, guardian_name, guardian_number, guardian_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssss", $ln, $fn, $mid, $gender, $grade, $section, $school_year, $height, $h_unit, $weight, $w_unit, $classification, $bmi, $pname, $contact, $email);

    if ($stmt->execute()) {
        $save_msg  = "Record saved successfully!";
        $save_type = "success";
        auditLog('add_record', 'stdRecord', $conn->insert_id, "$fn $mid $ln - BMI: $bmi ($classification)");

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

// â”€â”€ Calculate BMI on Submit â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$preview = null;
$validation_error = '';
if (isset($_POST['submit_form'])) {
    verifyCsrf();
    $fn     = sanitize($_POST["fname"]);
    $mid    = sanitize($_POST["m_initial"]);
    $ln     = sanitize($_POST["lname"]);
    $bday   = $_POST["birthday"];
    $gender = $_POST["gender"];
    $grade  = sanitize($_POST["grade_level"] ?? '');
    $section = sanitize($_POST["section"] ?? '');
    $school_year = sanitize($_POST["school_year"] ?? '');
    $weight = floatval($_POST["weight"]);
    $w_unit = $_POST["weight_unit"];
    $height = floatval($_POST["height"]);
    $h_unit = $_POST["measurement_unit"];
    $pname  = sanitize($_POST["p_name"]);
    $contact= sanitize($_POST["number"]);
    $email  = trim($_POST["email"]);

    if (!validateHeight($height, $h_unit)) {
        $validation_error = 'Invalid height value for the selected unit.';
    } elseif (!validateWeight($weight, $w_unit)) {
        $validation_error = 'Invalid weight value for the selected unit.';
    } else {
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

    $preview = compact('fn','mid','ln','bday','gender','grade','section','school_year','weight','w_unit','height','h_unit','bmi','classification','pname','contact','email');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(localStorage.getItem('nutriph_dark')==='1')document.documentElement.setAttribute('data-theme','dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide â€“ Add Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
</head>
<body style="background: linear-gradient(135deg, rgba(45,90,14,0.7), rgba(61,107,15,0.6)), url('../images/happy.jpg') center/cover no-repeat fixed; min-height:100vh;">

    <!-- Navbar -->
    <?php $activePage = 'addrecord'; include 'navbar.php'; ?>


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

                        <?php if ($validation_error): ?>
                            <div class="alert alert-danger small py-2"><i class="fa-solid fa-circle-exclamation me-1"></i><?= $validation_error ?></div>
                        <?php endif; ?>

                        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                            <?= csrfField() ?>
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

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-muted">Grade Level</label>
                                    <select class="form-select" name="grade_level">
                                        <option value="">Select...</option>
                                        <?php for ($g = 1; $g <= 6; $g++): ?>
                                            <option value="Grade <?= $g ?>" <?= (($preview['grade'] ?? '') == "Grade $g") ? 'selected' : '' ?>>Grade <?= $g ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-muted">Section</label>
                                    <input type="text" class="form-control" name="section" placeholder="e.g. Sampaguita" value="<?= htmlspecialchars($preview['section'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">School Year</label>
                                <select class="form-select" name="school_year">
                                    <?php
                                    $currentYear = date('Y');
                                    $currentMonth = date('n');
                                    $sy = ($currentMonth >= 6) ? $currentYear . '-' . ($currentYear+1) : ($currentYear-1) . '-' . $currentYear;
                                    for ($y = $currentYear+1; $y >= $currentYear-3; $y--) {
                                        $val = ($y-1) . '-' . $y;
                                        $sel = (($preview['school_year'] ?? $sy) === $val) ? 'selected' : '';
                                        echo "<option value='$val' $sel>$val</option>";
                                    }
                                    ?>
                                </select>
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

                        <!-- Photo Upload (separate form, after record is saved) -->
                        <?php if ($save_type === 'success' && $conn->insert_id): ?>
                        <form action="upload_photo.php" method="POST" enctype="multipart/form-data" class="mt-3 p-3 rounded-3" style="background:#f8fff0;">
                            <?= csrfField() ?>
                            <input type="hidden" name="student_id" value="<?= $conn->insert_id ?>">
                            <label class="form-label small fw-semibold text-success"><i class="fa-solid fa-camera me-1"></i>Upload Student Photo (optional)</label>
                            <div class="d-flex gap-2">
                                <input type="file" class="form-control form-control-sm" name="photo" accept="image/jpeg,image/png,image/webp">
                                <button class="btn btn-sm btn-outline-success px-3"><i class="fa-solid fa-upload"></i></button>
                            </div>
                        </form>
                        <?php endif; ?>
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
                                <?= csrfField() ?>
                                <input type="hidden" name="fname" value="<?= htmlspecialchars($preview['fn']) ?>">
                                <input type="hidden" name="m_initial" value="<?= htmlspecialchars($preview['mid']) ?>">
                                <input type="hidden" name="lname" value="<?= htmlspecialchars($preview['ln']) ?>">
                                <input type="hidden" name="gender" value="<?= htmlspecialchars($preview['gender']) ?>">
                                <input type="hidden" name="grade_level" value="<?= htmlspecialchars($preview['grade'] ?? '') ?>">
                                <input type="hidden" name="section" value="<?= htmlspecialchars($preview['section'] ?? '') ?>">
                                <input type="hidden" name="school_year" value="<?= htmlspecialchars($preview['school_year'] ?? '') ?>">
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
