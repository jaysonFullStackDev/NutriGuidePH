<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Admin', 'Super Admin']);

$conn = getDB();
$reportType = $_GET['type'] ?? 'summary';
$schoolYear = $_GET['sy'] ?? '';
$gradeFilter = $_GET['grade'] ?? '';

$where = '1=1';
if ($schoolYear) $where .= " AND school_year = '" . $conn->real_escape_string($schoolYear) . "'";
if ($gradeFilter) $where .= " AND grade_level = '" . $conn->real_escape_string($gradeFilter) . "'";

// Stats
$stats = $conn->query("SELECT classification, COUNT(*) as cnt FROM stdRecord WHERE $where GROUP BY classification")->fetch_all(MYSQLI_ASSOC);
$statMap = []; foreach ($stats as $s) $statMap[$s['classification']] = $s['cnt'];
$total = array_sum(array_column($stats, 'cnt'));

// Grade breakdown
$gradeStats = $conn->query("SELECT grade_level, classification, COUNT(*) as cnt FROM stdRecord WHERE $where AND grade_level IS NOT NULL GROUP BY grade_level, classification ORDER BY grade_level")->fetch_all(MYSQLI_ASSOC);

// Gender breakdown
$genderStats = $conn->query("SELECT gender, classification, COUNT(*) as cnt FROM stdRecord WHERE $where GROUP BY gender, classification")->fetch_all(MYSQLI_ASSOC);

$schoolYears = $conn->query("SELECT DISTINCT school_year FROM stdRecord WHERE school_year IS NOT NULL ORDER BY school_year DESC")->fetch_all(MYSQLI_ASSOC);
$grades = $conn->query("SELECT DISTINCT grade_level FROM stdRecord WHERE grade_level IS NOT NULL ORDER BY grade_level")->fetch_all(MYSQLI_ASSOC);

$isPrint = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(localStorage.getItem('nutriph_dark')==='1')document.documentElement.setAttribute('data-theme','dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide – Reports<?= $isPrint ? ' (Print)' : '' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <?php if (!$isPrint): ?>
    <link rel="stylesheet" href="../css/index.css">
    <?php endif; ?>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; font-size: 11pt; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .table th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge { border: 1px solid #999; }
        }
        .report-header { text-align: center; margin-bottom: 20px; }
        .report-header h4 { margin: 0; font-weight: 800; }
        .report-header p { margin: 2px 0; font-size: 0.9rem; color: #555; }
    </style>
</head>
<body class="<?= $isPrint ? '' : 'page-bg' ?>" <?= $isPrint ? 'style="background:#fff;"' : '' ?>>

    <?php if (!$isPrint): ?>
    <?php $activePage = 'reports'; include 'navbar.php'; ?>
    <?php endif; ?>

    <div class="<?= $isPrint ? 'container' : 'container-fluid px-3 px-lg-5' ?> py-3 py-lg-4">

        <?php if (!$isPrint): ?>
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-2 no-print">
            <div>
                <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-print me-2"></i>Reports</h4>
                <p class="text-white-50 small mb-0">Generate and print nutritional status reports</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-print me-1"></i>Print</button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
            <div class="card-body p-3">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <select class="form-select form-select-sm" name="sy" style="max-width:150px;">
                        <option value="">All Years</option>
                        <?php foreach ($schoolYears as $sy): ?>
                            <option value="<?= $sy['school_year'] ?>" <?= $schoolYear === $sy['school_year'] ? 'selected' : '' ?>><?= $sy['school_year'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select form-select-sm" name="grade" style="max-width:150px;">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['grade_level'] ?>" <?= $gradeFilter === $g['grade_level'] ? 'selected' : '' ?>><?= $g['grade_level'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-green btn-sm px-3"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                    <?php if ($schoolYear || $gradeFilter): ?>
                        <a href="reports.php" class="btn btn-outline-secondary btn-sm px-2"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Report Header -->
        <div class="report-header">
            <p>Republic of the Philippines</p>
            <h4>San Antonio Central School</h4>
            <p>Nutritional Status Report<?= $schoolYear ? " — S.Y. $schoolYear" : '' ?><?= $gradeFilter ? " — $gradeFilter" : '' ?></p>
            <p style="font-size:0.8rem;">Generated: <?= date('F d, Y') ?></p>
        </div>

        <!-- Summary Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Nutritional Status Summary</h6>
                <table class="table table-bordered table-sm text-center mb-0">
                    <thead class="table-light">
                        <tr><th>Total</th><th>Underweight</th><th>Normal</th><th>Overweight</th><th>Obese</th><th>Malnourished</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b><?= $total ?></b></td>
                            <td><?= $statMap['Underweight'] ?? 0 ?></td>
                            <td><?= $statMap['Normal Weight'] ?? 0 ?></td>
                            <td><?= $statMap['Overweight'] ?? 0 ?></td>
                            <td><?= $statMap['Obese'] ?? 0 ?></td>
                            <td><b><?= ($statMap['Underweight'] ?? 0) + ($statMap['Overweight'] ?? 0) + ($statMap['Obese'] ?? 0) ?></b></td>
                        </tr>
                        <?php if ($total > 0): ?>
                        <tr class="text-muted small">
                            <td>100%</td>
                            <td><?= round((($statMap['Underweight'] ?? 0) / $total) * 100, 1) ?>%</td>
                            <td><?= round((($statMap['Normal Weight'] ?? 0) / $total) * 100, 1) ?>%</td>
                            <td><?= round((($statMap['Overweight'] ?? 0) / $total) * 100, 1) ?>%</td>
                            <td><?= round((($statMap['Obese'] ?? 0) / $total) * 100, 1) ?>%</td>
                            <td><?= round(((($statMap['Underweight'] ?? 0) + ($statMap['Overweight'] ?? 0) + ($statMap['Obese'] ?? 0)) / $total) * 100, 1) ?>%</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- By Grade Level -->
        <?php
        $gradeData = [];
        foreach ($gradeStats as $gs) {
            $gradeData[$gs['grade_level']][$gs['classification']] = $gs['cnt'];
        }
        if (!empty($gradeData)):
        ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Breakdown by Grade Level</h6>
                <table class="table table-bordered table-sm text-center mb-0">
                    <thead class="table-light">
                        <tr><th>Grade</th><th>Total</th><th>Underweight</th><th>Normal</th><th>Overweight</th><th>Obese</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gradeData as $grade => $counts):
                            $gt = array_sum($counts);
                        ?>
                        <tr>
                            <td class="text-start"><b><?= htmlspecialchars($grade) ?></b></td>
                            <td><?= $gt ?></td>
                            <td><?= $counts['Underweight'] ?? 0 ?></td>
                            <td><?= $counts['Normal Weight'] ?? 0 ?></td>
                            <td><?= $counts['Overweight'] ?? 0 ?></td>
                            <td><?= $counts['Obese'] ?? 0 ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- By Gender -->
        <?php
        $genderData = [];
        foreach ($genderStats as $gs) {
            $genderData[$gs['gender']][$gs['classification']] = $gs['cnt'];
        }
        if (!empty($genderData)):
        ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Breakdown by Gender</h6>
                <table class="table table-bordered table-sm text-center mb-0">
                    <thead class="table-light">
                        <tr><th>Gender</th><th>Total</th><th>Underweight</th><th>Normal</th><th>Overweight</th><th>Obese</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($genderData as $gender => $counts):
                            $gt = array_sum($counts);
                        ?>
                        <tr>
                            <td class="text-start"><b><?= htmlspecialchars($gender) ?></b></td>
                            <td><?= $gt ?></td>
                            <td><?= $counts['Underweight'] ?? 0 ?></td>
                            <td><?= $counts['Normal Weight'] ?? 0 ?></td>
                            <td><?= $counts['Overweight'] ?? 0 ?></td>
                            <td><?= $counts['Obese'] ?? 0 ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Signature Lines -->
        <div class="row mt-5 pt-4" style="border-top:1px solid #ddd;">
            <div class="col-4 text-center">
                <div style="border-bottom:1px solid #333;margin-bottom:4px;height:40px;"></div>
                <small>Prepared by</small>
            </div>
            <div class="col-4 text-center">
                <div style="border-bottom:1px solid #333;margin-bottom:4px;height:40px;"></div>
                <small>Noted by</small>
            </div>
            <div class="col-4 text-center">
                <div style="border-bottom:1px solid #333;margin-bottom:4px;height:40px;"></div>
                <small>Approved by</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
