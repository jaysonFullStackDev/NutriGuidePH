<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Parent/Guardian']);

$conn = getDB();
$email = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM stdRecord WHERE guardian_email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $email);
$stmt->execute();
$allRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by student
$students = [];
foreach ($allRecords as $r) {
    $key = $r['std_first_name'] . '|' . $r['std_last_name'];
    $students[$key][] = $r;
}

// Feeding records
$stmt2 = $conn->prepare("SELECT f.*, s.std_first_name, s.std_mid_initial, s.std_last_name FROM feedingRecord f JOIN stdRecord s ON f.student_id = s.id WHERE s.guardian_email = ? ORDER BY f.feed_date DESC LIMIT 30");
$stmt2->bind_param("s", $email);
$stmt2->execute();
$feedings = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(localStorage.getItem('nutriph_dark')==='1')document.documentElement.setAttribute('data-theme','dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide – Parent Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css?v=3">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#3d6b0f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="<?= generateCsrf() ?>">
</head>
<body class="page-bg">

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-3 px-lg-5">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../index.php">
                <img src="../images/logo.png" alt="Logo" height="40"><span class="fw-bold brand-text">NutriPh Guide</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto gap-1 align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="parent_dashboard.php">My Child's Records</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-success-light fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user me-1"></i><?= htmlspecialchars($_SESSION['firstName']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3">
                            <li class="px-3 py-2"><div class="small fw-bold"><?= htmlspecialchars($_SESSION['firstName']) ?></div><div class="text-muted" style="font-size:0.75rem;">Parent/Guardian</div></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">
        <div class="mb-4">
            <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-child me-2"></i>My Child's Health Dashboard</h4>
            <p class="text-white-50 small mb-0">View health records, BMI trends, and feeding program history</p>
        </div>

        <?php if (empty($students)): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <i class="fa-solid fa-child fa-3x mb-3 text-muted opacity-25"></i>
                    <h6 class="fw-bold text-muted">No Records Found</h6>
                    <p class="text-muted small">No student records are linked to your email (<b><?= htmlspecialchars($email) ?></b>).</p>
                </div>
            </div>
        <?php else: ?>

        <?php foreach ($students as $key => $records):
            $latest = $records[0];
            $fullName = $latest['std_first_name'] . ' ' . $latest['std_mid_initial'] . ' ' . $latest['std_last_name'];
            $cls = $latest['classification'];
            $badge = match($cls) { 'Underweight'=>'badge-underweight', 'Normal Weight'=>'badge-normal', 'Overweight'=>'badge-overweight', default=>'badge-obese' };

            // Weight change
            $weightChange = '';
            if (count($records) > 1) {
                $prev = $records[1];
                $currW = ($latest['weight_unit'] === 'lbs') ? $latest['weight'] * 0.453592 : $latest['weight'];
                $prevW = ($prev['weight_unit'] === 'lbs') ? $prev['weight'] * 0.453592 : $prev['weight'];
                $diff = round($currW - $prevW, 1);
                if ($diff > 0) $weightChange = '<span class="text-success small fw-bold">+' . $diff . ' kg</span>';
                elseif ($diff < 0) $weightChange = '<span class="text-danger small fw-bold">' . $diff . ' kg</span>';
                else $weightChange = '<span class="text-muted small">No change</span>';
            }
        ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">
                            <?php if ($latest['photo']): ?>
                                <img src="../uploads/photos/<?= htmlspecialchars($latest['photo']) ?>" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;">
                            <?php endif; ?>
                            <?= htmlspecialchars($fullName) ?>
                        </h5>
                        <div class="text-muted small">
                            <?= htmlspecialchars($latest['gender']) ?>
                            <?php if ($latest['grade_level']): ?> · <?= htmlspecialchars($latest['grade_level']) ?><?php endif; ?>
                            <?php if ($latest['section']): ?> - <?= htmlspecialchars($latest['section']) ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="badge rounded-pill <?= $badge ?>"><?= $cls ?></span>
                </div>

                <!-- Stats Row -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 text-center" style="background:#f8f9fa;">
                            <div class="fw-bold" style="font-size:1.2rem;"><?= $latest['bmi'] ?></div>
                            <div class="text-muted" style="font-size:0.7rem;">BMI</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 text-center" style="background:#f8f9fa;">
                            <div class="fw-bold" style="font-size:1.2rem;"><?= $latest['weight'] ?> <?= $latest['weight_unit'] ?></div>
                            <div class="text-muted" style="font-size:0.7rem;">Weight <?= $weightChange ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 text-center" style="background:#f8f9fa;">
                            <div class="fw-bold" style="font-size:1.2rem;"><?= $latest['height'] ?> <?= $latest['height_unit'] ?></div>
                            <div class="text-muted" style="font-size:0.7rem;">Height</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 text-center" style="background:#f8f9fa;">
                            <div class="fw-bold" style="font-size:1.2rem;"><?= count($records) ?></div>
                            <div class="text-muted" style="font-size:0.7rem;">Total Records</div>
                        </div>
                    </div>
                </div>

                <!-- BMI Trend Chart -->
                <?php if (count($records) > 1): ?>
                <div class="mb-3">
                    <h6 class="fw-bold small text-success mb-2"><i class="fa-solid fa-chart-line me-1"></i>BMI Trend</h6>
                    <canvas id="bmiChart_<?= md5($key) ?>" height="120"></canvas>
                </div>
                <?php endif; ?>

                <!-- Health Recommendations -->
                <?php if ($cls !== 'Normal Weight'): ?>
                <div class="alert alert-<?= ($cls === 'Overweight') ? 'warning' : 'danger' ?> small py-2 mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    <b>Health Alert:</b>
                    <?php if ($cls === 'Underweight'): ?>
                        Your child is underweight. Increase calorie intake with rice, eggs, chicken, and vegetables. Consult a doctor for a personalized plan.
                    <?php elseif ($cls === 'Overweight'): ?>
                        Your child is overweight. Reduce sugary drinks and junk food. Encourage 60 minutes of physical activity daily.
                    <?php else: ?>
                        Your child is classified as obese. Please consult a doctor immediately for a dietary and exercise plan.
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Record History -->
                <h6 class="fw-bold small text-muted mb-2"><i class="fa-solid fa-history me-1"></i>Record History</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover small mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Height</th><th>Weight</th><th>BMI</th><th>Status</th><th>Change</th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $i => $r):
                            $b = match($r['classification']) { 'Underweight'=>'badge-underweight', 'Normal Weight'=>'badge-normal', 'Overweight'=>'badge-overweight', default=>'badge-obese' };
                            $wc = '';
                            if ($i < count($records) - 1) {
                                $next = $records[$i + 1];
                                $cw = ($r['weight_unit']==='lbs') ? $r['weight']*0.453592 : $r['weight'];
                                $pw = ($next['weight_unit']==='lbs') ? $next['weight']*0.453592 : $next['weight'];
                                $d = round($cw - $pw, 1);
                                $wc = $d > 0 ? '<span class="text-success">+'.$d.' kg</span>' : ($d < 0 ? '<span class="text-danger">'.$d.' kg</span>' : '—');
                            }
                        ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td><?= $r['height'] ?> <?= $r['height_unit'] ?></td>
                            <td><?= $r['weight'] ?> <?= $r['weight_unit'] ?></td>
                            <td class="fw-bold"><?= $r['bmi'] ?></td>
                            <td><span class="badge rounded-pill <?= $b ?>"><?= $r['classification'] ?></span></td>
                            <td><?= $wc ?: '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Feeding History -->
        <?php if (!empty($feedings)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-utensils me-2"></i>Feeding Program History</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover small mb-0">
                        <thead class="table-light"><tr><th>Child</th><th>Date</th><th>Meal</th><th>Notes</th></tr></thead>
                        <tbody>
                        <?php foreach ($feedings as $f): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($f['std_first_name'] . ' ' . $f['std_mid_initial'] . ' ' . $f['std_last_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($f['feed_date'])) ?></td>
                            <td><?= htmlspecialchars($f['meal_type']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($f['notes'] ?: '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <footer class="footer-section mt-4">
        <div class="text-center py-3 border-top border-secondary small text-muted footer-bottom">&copy; <?= date('Y') ?> NutriPh Guide</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    <?php foreach ($students as $key => $records): if (count($records) > 1): ?>
        new Chart(document.getElementById('bmiChart_<?= md5($key) ?>'), {
            type: 'line',
            data: {
                labels: [<?php foreach (array_reverse($records) as $r) echo "'" . date('M d', strtotime($r['created_at'])) . "',"; ?>],
                datasets: [{
                    label: 'BMI',
                    data: [<?php foreach (array_reverse($records) as $r) echo $r['bmi'] . ','; ?>],
                    borderColor: '#78bc27',
                    backgroundColor: 'rgba(120,188,39,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#78bc27'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: false, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    <?php endif; endforeach; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>
