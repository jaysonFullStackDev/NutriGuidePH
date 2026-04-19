<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Employee', 'Admin', 'Super Admin']);

$conn = getDB();

$search = trim($_GET['search'] ?? '');
$grade = trim($_GET['grade'] ?? '');

// Get available grade levels for filter
$gradesResult = $conn->query("SELECT DISTINCT grade_level FROM stdRecord WHERE grade_level IS NOT NULL AND grade_level != '' ORDER BY grade_level");

// Build query for unique students (latest record only)
$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $like = "%$search%";
    $where .= " AND (s.std_first_name LIKE ? OR s.std_last_name LIKE ? OR s.guardian_name LIKE ? OR s.classification LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}
if ($grade !== '') {
    $where .= " AND s.grade_level = ?";
    $params[] = $grade;
    $types .= 's';
}

$sql = "SELECT s.*, (SELECT COUNT(*) FROM stdRecord r WHERE r.std_first_name = s.std_first_name AND r.std_last_name = s.std_last_name) as record_count
    FROM stdRecord s
    INNER JOIN (
        SELECT std_first_name, std_last_name, MAX(id) as latest_id
        FROM stdRecord GROUP BY std_first_name, std_last_name
    ) latest ON s.id = latest.latest_id
    WHERE 1=1 $where
    ORDER BY s.std_last_name, s.std_first_name";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $conn->query($sql);
}

$totalStudents = $students->num_rows;
$canEdit = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(localStorage.getItem('nutriph_dark')==='1')document.documentElement.setAttribute('data-theme','dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide â€“ Student Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css?v=3">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#3d6b0f">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body class="page-bg">

    <?php $activePage = 'records'; include 'navbar.php'; ?>


    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-2" data-aos="fade-down">
            <div>
                <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-folder-open me-2"></i>Student Records</h4>
                <p class="text-white-50 small mb-0"><?= $totalStudents ?> unique students â€” click a name to view full history</p>
            </div>
            <a href="addrecord.php" class="btn btn-green px-4"><i class="fa-solid fa-plus me-2"></i>Add Record</a>
        </div>

        <div class="card table-card shadow-sm mb-4" data-aos="fade-up">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <h6 class="fw-bold text-success mb-0"><i class="fa-solid fa-table-list me-2"></i>All Students (Latest Record)</h6>
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search name, guardian, classification..." value="<?= htmlspecialchars($search) ?>" style="max-width:220px;">
                        <select class="form-select form-select-sm" name="grade" style="max-width:140px;">
                            <option value="">All Grades</option>
                            <?php while ($g = $gradesResult->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($g['grade_level']) ?>" <?= $grade === $g['grade_level'] ? 'selected' : '' ?>><?= htmlspecialchars($g['grade_level']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button class="btn btn-green btn-sm px-3"><i class="fa-solid fa-search"></i></button>
                        <?php if ($search || $grade): ?>
                            <a href="records.php" class="btn btn-outline-secondary btn-sm px-2"><i class="fa-solid fa-xmark"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small fw-semibold" style="width:1%;">#</th>
                                <th class="small fw-semibold">Name</th>
                                <th class="small fw-semibold">Gender</th>
                                <th class="small fw-semibold">Grade / Section</th>
                                <th class="small fw-semibold">Height</th>
                                <th class="small fw-semibold">Weight</th>
                                <th class="small fw-semibold">BMI</th>
                                <th class="small fw-semibold">Classification</th>
                                <th class="small fw-semibold">Guardian</th>
                                <th class="small fw-semibold" style="width:1%;text-align:center;">Records</th>
                                <th class="small fw-semibold">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalStudents > 0): ?>
                                <?php $n = 1; while ($s = $students->fetch_assoc()):
                                    $cls = $s['classification'];
                                    $badge = match($cls) { 'Underweight'=>'badge-underweight', 'Normal Weight'=>'badge-normal', 'Overweight'=>'badge-overweight', default=>'badge-obese' };
                                ?>
                                <tr>
                                    <td class="small text-muted"><?= $n++ ?></td>
                                    <td class="small">
                                        <a href="#" class="text-decoration-none fw-semibold student-name-link" style="color:#2d5a0e;"
                                           data-fn="<?= htmlspecialchars($s['std_first_name']) ?>"
                                           data-ln="<?= htmlspecialchars($s['std_last_name']) ?>"
                                           data-mid="<?= htmlspecialchars($s['std_mid_initial']) ?>">
                                            <?= htmlspecialchars($s['std_first_name'] . ' ' . $s['std_mid_initial'] . ' ' . $s['std_last_name']) ?>
                                        </a>
                                        <?php if ($cls !== 'Normal Weight'): ?>
                                            <i class="fa-solid fa-triangle-exclamation ms-1 text-danger" style="font-size:0.65rem;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($s['gender']) ?></td>
                                    <td class="small" style="white-space:normal;"><?= htmlspecialchars(($s['grade_level'] ?? '') . ($s['section'] ? ' - ' . $s['section'] : '')) ?: '<span class="text-muted">â€”</span>' ?></td>
                                    <td class="small"><?= $s['height'] ?> <?= $s['height_unit'] ?></td>
                                    <td class="small"><?= $s['weight'] ?> <?= $s['weight_unit'] ?></td>
                                    <td class="small fw-bold"><?= $s['bmi'] ?></td>
                                    <td><span class="badge rounded-pill <?= $badge ?>"><?= $cls ?></span></td>
                                    <td class="small" style="white-space:normal;"><?= htmlspecialchars($s['guardian_name'] ?? '') ?: '<span class="text-muted">â€”</span>' ?></td>
                                    <td class="small text-center"><?= $s['record_count'] ?></td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-folder-open fa-2x mb-2 opacity-25 d-block"></i>
                                        <?= ($search || $grade) ? 'No students match your filters.' : 'No student records yet.' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold" id="historyLabel"><i class="fa-solid fa-clock-rotate-left me-2"></i>Record History</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="historyBody">
                    <div class="text-center py-5"><div class="spinner-border text-success"></div></div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-section mt-4">
        <div class="container py-5">
            <div class="row g-4 align-items-center">
                <div class="col-md-6"><div class="d-flex align-items-center gap-2 mb-3"><img src="../images/logo.png" alt="Logo" height="36" class="footer-logo"><h5 class="mb-0 text-success-light">NutriPh Guide</h5></div><p class="text-muted small">Community-based monitoring system for San Antonio Central School.</p></div>
                <div class="col-md-6"><h6 class="text-success-light text-uppercase small fw-bold mb-3">Contact</h6><ul class="list-unstyled text-muted small"><li class="mb-2"><i class="fa-solid fa-envelope me-2"></i>abcdefg@gmail.com</li><li class="mb-2"><i class="fa-solid fa-location-dot me-2"></i>San Antonio Central School, Philippines</li></ul></div>
            </div>
        </div>
        <div class="text-center py-3 border-top border-secondary small text-muted footer-bottom">&copy; <?= date('Y') ?> NutriPh Guide</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true });

        function esc(s) { if(!s)return''; const el=document.createElement('span'); el.textContent=s; return el.innerHTML; }
        function fmtDate(d) { return new Date(d).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }

        const hModal = new bootstrap.Modal(document.getElementById('historyModal'));
        const hBody = document.getElementById('historyBody');
        const hLabel = document.getElementById('historyLabel');

        document.querySelectorAll('.student-name-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const fn = this.dataset.fn, ln = this.dataset.ln, mid = this.dataset.mid;
                const fullName = esc(fn) + ' ' + esc(mid) + ' ' + esc(ln);
                hLabel.innerHTML = '<i class="fa-solid fa-clock-rotate-left me-2"></i>' + fullName;
                hBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success"></div><p class="text-muted small mt-2">Loading records...</p></div>';
                hModal.show();

                fetch('get_student_history.php?fn='+encodeURIComponent(fn)+'&ln='+encodeURIComponent(ln))
                    .then(r => r.json())
                    .then(data => {
                        if (!data.records || data.records.length === 0) {
                            hBody.innerHTML = '<div class="text-center text-muted py-4"><i class="fa-solid fa-inbox fa-2x mb-2 opacity-25"></i><p>No records found.</p></div>';
                            return;
                        }

                        const recs = data.records;
                        let h = '';

                        // BMI Trend
                        if (recs.length > 1) {
                            const bmis = recs.map(r => parseFloat(r.bmi)).reverse();
                            const maxB = Math.max(...bmis, 30);
                            h += '<div class="card border-0 shadow-sm rounded-3 mb-3"><div class="card-body p-3">';
                            h += '<h6 class="fw-bold small text-success mb-2"><i class="fa-solid fa-chart-line me-1"></i>BMI Trend</h6>';
                            h += '<div class="d-flex align-items-end gap-1" style="height:50px;">';
                            bmis.forEach(b => {
                                const pct = Math.max(12, (b/maxB)*100);
                                const clr = b<18.5?'#e74c3c':(b<=24.9?'#27ae60':(b<=29.9?'#f39c12':'#8e44ad'));
                                h += '<div style="flex:1;background:'+clr+';height:'+pct+'%;border-radius:3px 3px 0 0;min-width:6px;" title="BMI: '+b+'"></div>';
                            });
                            h += '</div><div class="d-flex justify-content-between mt-1" style="font-size:0.65rem;color:#aaa;"><span>Oldest</span><span>Latest</span></div>';
                            h += '</div></div>';
                        }

                        // Record cards
                        recs.forEach((r, i) => {
                            const cls = r.classification;
                            const isLatest = i === 0;
                            const bc = {Underweight:'#c0392b','Normal Weight':'#27ae60',Overweight:'#e67e22',Obese:'#8e44ad'}[cls] || '#8e44ad';
                            const bg = {Underweight:'badge-underweight','Normal Weight':'badge-normal',Overweight:'badge-overweight',Obese:'badge-obese'}[cls] || 'badge-obese';

                            h += '<div class="card border-0 shadow-sm rounded-3 mb-2" style="border-left:4px solid '+bc+' !important;">';
                            h += '<div class="card-body p-3">';
                            h += '<div class="d-flex justify-content-between align-items-center mb-2">';
                            if (isLatest) {
                                h += '<span class="fw-bold small text-success"><i class="fa-solid fa-star me-1"></i>Latest Record</span>';
                            } else {
                                h += '<span class="text-muted small"><i class="fa-solid fa-calendar me-1"></i>' + fmtDate(r.created_at) + '</span>';
                            }
                            h += '<div class="d-flex align-items-center gap-2">';
                            h += '<span class="badge rounded-pill '+bg+'">'+cls+'</span>';
                            if (cls !== 'Normal Weight') h += '<i class="fa-solid fa-triangle-exclamation text-danger" style="font-size:0.65rem;"></i>';
                            h += '</div></div>';

                            h += '<div class="row g-2" style="font-size:0.82rem;">';
                            h += '<div class="col-6 col-md-3"><span class="text-muted">BMI:</span> <b>'+r.bmi+'</b></div>';
                            h += '<div class="col-6 col-md-3"><span class="text-muted">Height:</span> '+esc(r.height)+' '+esc(r.height_unit)+'</div>';
                            h += '<div class="col-6 col-md-3"><span class="text-muted">Weight:</span> '+esc(r.weight)+' '+esc(r.weight_unit)+'</div>';
                            h += '<div class="col-6 col-md-3"><span class="text-muted">Gender:</span> '+esc(r.gender)+'</div>';
                            if (r.grade_level) h += '<div class="col-6"><span class="text-muted">Grade:</span> '+esc(r.grade_level)+(r.section?' - '+esc(r.section):'')+'</div>';
                            if (r.guardian_name) h += '<div class="col-6"><span class="text-muted">Guardian:</span> '+esc(r.guardian_name)+'</div>';
                            if (isLatest) h += '<div class="col-12 mt-1" style="font-size:0.72rem;color:#999;"><i class="fa-solid fa-calendar me-1"></i>'+fmtDate(r.created_at)+'</div>';
                            h += '</div></div></div>';
                        });

                        hBody.innerHTML = h;
                    })
                    .catch(() => { hBody.innerHTML = '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation fa-2x mb-2"></i><p>Failed to load records.</p></div>'; });
            });
        });
    </script>
</body>
</html>
<?php if(isset($stmt)) $stmt->close(); $conn->close(); ?>
