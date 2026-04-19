<?php
// Shared navbar - set $activePage before including
$activePage = $activePage ?? '';
$navFirstName = htmlspecialchars($_SESSION['firstName'] ?? '');
$navRole = htmlspecialchars($_SESSION['role'] ?? '');
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid px-3 px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-2" href="../index.php">
            <img src="../images/logo.png" alt="Logo" height="40"><span class="fw-bold brand-text">NutriPh Guide</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto gap-1 align-items-center">
                <li class="nav-item"><a class="nav-link <?= $activePage==='dashboard'?'active':'' ?>" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage==='records'?'active':'' ?>" href="records.php">Records</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage,['feeding','meal_plan'])?'active':'' ?>" href="#" role="button" data-bs-toggle="dropdown">Feeding</a>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3">
                        <li><a class="dropdown-item small" href="feeding_program.php"><i class="fa-solid fa-utensils me-2 text-success"></i>Feeding Program</a></li>
                        <li><a class="dropdown-item small" href="meal_planning.php"><i class="fa-solid fa-calendar-week me-2 text-primary"></i>Meal Planning</a></li>
                    </ul>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage,['reports','import'])?'active':'' ?>" href="#" role="button" data-bs-toggle="dropdown">Tools</a>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3">
                        <li><a class="dropdown-item small" href="reports.php"><i class="fa-solid fa-print me-2 text-success"></i>Print Reports</a></li>
                        <li><a class="dropdown-item small" href="deped_reports.php"><i class="fa-solid fa-file-lines me-2 text-info"></i>DepEd Reports (SBFP/OPT)</a></li>
                        <li><a class="dropdown-item small" href="bulk_import.php"><i class="fa-solid fa-file-import me-2 text-primary"></i>Bulk Import</a></li>
                        <?php if (isSuperAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small" href="backup.php"><i class="fa-solid fa-database me-2 text-warning"></i>Download Backup</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (isSuperAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage,['users','audit'])?'active':'' ?>" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3">
                        <li><a class="dropdown-item small" href="user_management.php"><i class="fa-solid fa-users-gear me-2 text-success"></i>Users</a></li>
                        <li><a class="dropdown-item small" href="audit_log.php"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Audit Log</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-success-light fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user me-1"></i><?= $navFirstName ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3" style="min-width:200px;">
                        <li class="px-3 py-2"><div class="small fw-bold"><?= $navFirstName ?></div><div class="text-muted" style="font-size:0.75rem;"><?= $navRole ?></div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small" href="#" id="openProfileBtn"><i class="fa-solid fa-user-pen me-2 text-success"></i>My Profile</a></li>
                        <li><a class="dropdown-item small" href="#" id="openPasswordBtn"><i class="fa-solid fa-key me-2 text-warning"></i>Change Password</a></li>
                        <li><a class="dropdown-item small" href="#" id="darkModeToggle"><i class="fa-solid fa-moon me-2 text-info"></i><span id="darkModeLabel">Dark Mode</span></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-end text-bg-dark" id="mobileSidebar">
    <div class="offcanvas-header"><h5 class="offcanvas-title brand-text">NutriPh Guide</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body">
        <ul class="navbar-nav gap-2">
            <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="records.php"><i class="fa-solid fa-folder-open me-2"></i>Records</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="feeding_program.php"><i class="fa-solid fa-utensils me-2"></i>Feeding Program</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="meal_planning.php"><i class="fa-solid fa-calendar-week me-2"></i>Meal Planning</a></li>
            <?php if (isAdmin()): ?>
            <li class="nav-item"><a class="nav-link text-white" href="reports.php"><i class="fa-solid fa-print me-2"></i>Reports</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="deped_reports.php"><i class="fa-solid fa-file-lines me-2"></i>DepEd Reports</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="bulk_import.php"><i class="fa-solid fa-file-import me-2"></i>Bulk Import</a></li>
            <?php endif; ?>
            <?php if (isSuperAdmin()): ?>
            <li class="nav-item"><a class="nav-link text-white" href="user_management.php"><i class="fa-solid fa-users-gear me-2"></i>Users</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="audit_log.php"><i class="fa-solid fa-clipboard-list me-2"></i>Audit Log</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="backup.php"><i class="fa-solid fa-database me-2"></i>Backup</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="#" id="openProfileBtnMobile"><i class="fa-solid fa-user-pen me-2"></i>My Profile</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="#" id="openPasswordBtnMobile"><i class="fa-solid fa-key me-2"></i>Change Password</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="#" id="darkModeToggleMobile"><i class="fa-solid fa-moon me-2"></i><span id="darkModeLabelMobile">Dark Mode</span></a></li>
            <li class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>

<script>
(function() {
    var saved = localStorage.getItem('nutriph_dark') === '1';
    if (saved) document.documentElement.setAttribute('data-theme', 'dark');

    function updateLabels(isDark) {
        var icon = isDark ? 'fa-sun' : 'fa-moon';
        var text = isDark ? 'Light Mode' : 'Dark Mode';
        var color = isDark ? 'text-warning' : 'text-info';
        document.querySelectorAll('#darkModeToggle i, #darkModeToggleMobile i').forEach(function(i) { i.className = 'fa-solid ' + icon + ' me-2 ' + color; });
        document.querySelectorAll('#darkModeLabel, #darkModeLabelMobile').forEach(function(s) { s.textContent = text; });
    }

    function toggle(e) {
        e.preventDefault();
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('nutriph_dark', '0');
            updateLabels(false);
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('nutriph_dark', '1');
            updateLabels(true);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateLabels(saved);
        var dt = document.getElementById('darkModeToggle');
        var dtm = document.getElementById('darkModeToggleMobile');
        if (dt) dt.addEventListener('click', toggle);
        if (dtm) dtm.addEventListener('click', toggle);
    });
})();
</script>
