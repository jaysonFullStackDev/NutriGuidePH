<?php
require_once 'auth.php';
secureSessionStart();
checkAccess(['Employee', 'Admin', 'Super Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    header("Location: addrecord.php");
    exit();
}

verifyCsrf();

$studentId = intval($_POST['student_id'] ?? 0);
$file = $_FILES['photo'];

if ($studentId === 0 || $file['error'] !== UPLOAD_ERR_OK) {
    header("Location: addrecord.php");
    exit();
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed) || $file['size'] > 2 * 1024 * 1024) {
    header("Location: addrecord.php");
    exit();
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'student_' . $studentId . '_' . time() . '.' . $ext;
$dest = __DIR__ . '/../uploads/photos/' . $filename;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    $conn = getDB();
    $stmt = $conn->prepare("UPDATE stdRecord SET photo = ? WHERE id = ?");
    $stmt->bind_param("si", $filename, $studentId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    auditLog('upload_photo', 'stdRecord', $studentId, $filename);
}

header("Location: addrecord.php");
exit();
?>
