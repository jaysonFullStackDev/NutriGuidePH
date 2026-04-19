<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Super Admin']);

$conn = getDB();
if ($conn->connect_error) { die("Connection failed"); }

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $uid = intval($_POST['uid'] ?? 0);
    $action = $_POST['action'];

    if ($uid > 0) {
        if ($action === 'approve') {
            $conn->query("UPDATE accounts SET status='active' WHERE id=$uid");
            auditLog('approve_user', 'account', $uid);
        } elseif ($action === 'deactivate') {
            $conn->query("UPDATE accounts SET status='deactivated' WHERE id=$uid AND email != '{$_SESSION['user_id']}'");
            auditLog('deactivate_user', 'account', $uid);
        } elseif ($action === 'activate') {
            $conn->query("UPDATE accounts SET status='active' WHERE id=$uid");
            auditLog('activate_user', 'account', $uid);
        } elseif ($action === 'change_role') {
            $newRole = $_POST['new_role'] ?? '';
            $allowed = ['Employee', 'Admin', 'Super Admin'];
            if (in_array($newRole, $allowed)) {
                $stmt = $conn->prepare("UPDATE accounts SET role=? WHERE id=? AND email != ?");
                $me = $_SESSION['user_id'];
                $stmt->bind_param("sis", $newRole, $uid, $me);
                $stmt->execute();
                auditLog('change_role', 'account', $uid, "New role: $newRole");
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $conn->query("DELETE FROM accounts WHERE id=$uid AND email != '{$_SESSION['user_id']}'");
            auditLog('delete_user', 'account', $uid);
        }
    }

    // Create staff account
    if ($action === 'create_staff') {
        $fn = trim($_POST['new_fn'] ?? '');
        $ln = trim($_POST['new_ln'] ?? '');
        $em = trim($_POST['new_email'] ?? '');
        $pw = $_POST['new_password'] ?? '';
        $rl = $_POST['new_role'] ?? 'Employee';
        $allowed = ['Employee', 'Admin', 'Super Admin'];

        if (!empty($fn) && !empty($ln) && !empty($em) && strlen($pw) >= 6 && in_array($rl, $allowed)) {
            $hashed = password_hash($pw, PASSWORD_DEFAULT);
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $conn->prepare("INSERT INTO accounts (firstName, lastName, role, email, password, is_verified, status, verification_code, code_expires) VALUES (?, ?, ?, ?, ?, 0, 'active', ?, ?)");
            $stmt->bind_param("sssssss", $fn, $ln, $rl, $em, $hashed, $code, $expires);
            if ($stmt->execute()) {
                require_once 'mailer.php';
                sendVerificationEmail($em, $fn, $code);
                auditLog('create_staff', 'account', $conn->insert_id, "$fn $ln ($rl)");
            }
            $stmt->close();
        }
    }

    header("Location: user_management.php");
    exit();
}

// Get all accounts
$pending = $conn->query("SELECT * FROM accounts WHERE status='pending' AND role != 'Parent/Guardian' ORDER BY created_at DESC");
$active = $conn->query("SELECT * FROM accounts WHERE status='active' ORDER BY role DESC, firstName ASC");
$deactivated = $conn->query("SELECT * FROM accounts WHERE status='deactivated' ORDER BY firstName ASC");

$pendingCount = $pending->num_rows;
$activeCount = $active->num_rows;
$deactivatedCount = $deactivated->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if(localStorage.getItem('nutriph_dark')==='1')document.documentElement.setAttribute('data-theme','dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriPh Guide â€“ User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
</head>
<body style="background: linear-gradient(135deg, rgba(45,90,14,0.7), rgba(61,107,15,0.6)), url('../images/happy.jpg') center/cover no-repeat fixed; min-height:100vh;">

    <!-- Navbar -->
    <?php $activePage = 'users'; include 'navbar.php'; ?>


    <div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-2" data-aos="fade-down">
            <div>
                <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-users-gear me-2"></i>User Management</h4>
                <p class="text-white-50 small mb-0">Create, approve, promote, and manage user accounts</p>
            </div>
            <button class="btn btn-green px-4" data-bs-toggle="modal" data-bs-target="#createStaffModal">
                <i class="fa-solid fa-user-plus me-2"></i>Create Staff
            </button>
        </div>

        <!-- Stats -->
        <div class="row g-2 g-lg-3 mb-4">
            <div class="col-4" data-aos="fade-up">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-clock"></i></div>
                        <div><div class="stat-number text-warning"><?= $pendingCount ?></div><div class="text-muted small fw-semibold">Pending</div></div>
                    </div>
                </div>
            </div>
            <div class="col-4" data-aos="fade-up" data-aos-delay="50">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-user-check"></i></div>
                        <div><div class="stat-number text-success"><?= $activeCount ?></div><div class="text-muted small fw-semibold">Active</div></div>
                    </div>
                </div>
            </div>
            <div class="col-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon" style="background-color:#f8d7da;color:#721c24;"><i class="fa-solid fa-user-slash"></i></div>
                        <div><div class="stat-number" style="color:#721c24;"><?= $deactivatedCount ?></div><div class="text-muted small fw-semibold">Deactivated</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <?php if ($pendingCount > 0): ?>
        <div class="card table-card shadow-sm mb-4" data-aos="fade-up">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:#e67e22;"><i class="fa-solid fa-clock me-2"></i>Pending Approval (<?= $pendingCount ?>)</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small fw-semibold">Name</th>
                                <th class="small fw-semibold">Email</th>
                                <th class="small fw-semibold">Registered</th>
                                <th class="small fw-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $pending->fetch_assoc()): ?>
                            <tr>
                                <td class="small fw-semibold"><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></td>
                                <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-center text-nowrap">
                                    <form method="POST" class="d-inline"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="approve">
                                        <button class="btn btn-sm btn-success py-0 px-2"><i class="fa-solid fa-check me-1" style="font-size:0.7rem;"></i>Approve</button>
                                    </form>
                                    <form method="POST" class="d-inline"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Delete this account?')"><i class="fa-solid fa-trash" style="font-size:0.7rem;"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Active Users -->
        <div class="card table-card shadow-sm mb-4" data-aos="fade-up">
            <div class="card-body p-4">
                <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-user-check me-2"></i>Active Users (<?= $activeCount ?>)</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small fw-semibold">Name</th>
                                <th class="small fw-semibold">Email</th>
                                <th class="small fw-semibold">Role</th>
                                <th class="small fw-semibold">Joined</th>
                                <th class="small fw-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $active->fetch_assoc()):
                                $isMe = ($u['email'] === $_SESSION['user_id']);
                                $roleBadge = match($u['role']) {
                                    'Super Admin' => 'bg-danger-subtle text-danger',
                                    'Admin' => 'bg-primary-subtle text-primary',
                                    'Employee' => 'bg-success-subtle text-success',
                                    default => 'bg-secondary-subtle text-secondary'
                                };
                            ?>
                            <tr>
                                <td class="small fw-semibold">
                                    <?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?>
                                    <?php if ($isMe): ?><span class="badge bg-dark rounded-pill ms-1" style="font-size:0.6rem;">You</span><?php endif; ?>
                                    <?php if (!$u['is_verified']): ?><span class="badge bg-warning-subtle text-warning rounded-pill ms-1" style="font-size:0.6rem;">Unverified</span><?php endif; ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if (!$isMe && $u['role'] !== 'Parent/Guardian'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <select name="new_role" class="form-select form-select-sm d-inline-block" style="width:auto;font-size:0.78rem;" onchange="this.form.submit()">
                                            <option value="Employee" <?= $u['role']==='Employee'?'selected':'' ?>>Employee</option>
                                            <option value="Admin" <?= $u['role']==='Admin'?'selected':'' ?>>Admin</option>
                                            <option value="Super Admin" <?= $u['role']==='Super Admin'?'selected':'' ?>>Super Admin</option>
                                        </select>
                                    </form>
                                    <?php else: ?>
                                        <span class="badge rounded-pill <?= $roleBadge ?>" style="font-size:0.75rem;"><?= $u['role'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-center">
                                    <?php if (!$isMe): ?>
                                    <form method="POST" class="d-inline"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="deactivate">
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Deactivate this account?')" title="Deactivate"><i class="fa-solid fa-user-slash" style="font-size:0.7rem;"></i></button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small">â€”</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Deactivated -->
        <?php if ($deactivatedCount > 0): ?>
        <div class="card table-card shadow-sm mb-4" data-aos="fade-up">
            <div class="card-body p-4">
                <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-user-slash me-2"></i>Deactivated (<?= $deactivatedCount ?>)</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small fw-semibold">Name</th>
                                <th class="small fw-semibold">Email</th>
                                <th class="small fw-semibold">Role</th>
                                <th class="small fw-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $deactivated->fetch_assoc()): ?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></td>
                                <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="small"><?= $u['role'] ?></td>
                                <td class="text-center text-nowrap">
                                    <form method="POST" class="d-inline"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="activate">
                                        <button class="btn btn-sm btn-outline-success py-0 px-2"><i class="fa-solid fa-user-check me-1" style="font-size:0.7rem;"></i>Reactivate</button>
                                    </form>
                                    <form method="POST" class="d-inline"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Permanently delete this account?')"><i class="fa-solid fa-trash" style="font-size:0.7rem;"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Create Staff Modal -->
    <div class="modal fade" id="createStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 py-3 px-4" style="background-color:#3d6b0f;">
                    <h6 class="modal-title text-white fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Create Staff Account</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_staff">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">First Name</label>
                                <input type="text" class="form-control form-control-sm" name="new_fn" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Last Name</label>
                                <input type="text" class="form-control form-control-sm" name="new_ln" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Email</label>
                            <input type="email" class="form-control form-control-sm" name="new_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Password</label>
                            <input type="password" class="form-control form-control-sm" name="new_password" required minlength="6" placeholder="Min 6 characters">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Role</label>
                            <select class="form-select form-select-sm" name="new_role">
                                <option value="Employee">Employee</option>
                                <option value="Admin">Admin</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                        <p class="text-muted small mb-3"><i class="fa-solid fa-info-circle me-1"></i>A verification code will be sent to their email. They must verify before logging in.</p>
                        <button type="submit" class="btn btn-green w-100 py-2">
                            <i class="fa-solid fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>
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
<?php $conn->close(); ?>
