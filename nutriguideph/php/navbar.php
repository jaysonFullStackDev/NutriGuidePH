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
                <li class="nav-item"><a class="nav-link <?= $activePage==='feeding'?'active':'' ?>" href="feeding_program.php">Feeding Program</a></li>
                <?php if (isSuperAdmin()): ?>
                <li class="nav-item"><a class="nav-link <?= $activePage==='users'?'active':'' ?>" href="user_management.php">Users</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage==='audit'?'active':'' ?>" href="audit_log.php">Audit Log</a></li>
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
            <?php if (isSuperAdmin()): ?>
            <li class="nav-item"><a class="nav-link text-white" href="user_management.php"><i class="fa-solid fa-users-gear me-2"></i>Users</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="audit_log.php"><i class="fa-solid fa-clipboard-list me-2"></i>Audit Log</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="#" id="openProfileBtnMobile"><i class="fa-solid fa-user-pen me-2"></i>My Profile</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="#" id="openPasswordBtnMobile"><i class="fa-solid fa-key me-2"></i>Change Password</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>
