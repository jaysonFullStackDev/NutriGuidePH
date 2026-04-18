<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'auth.php';
if (!isAdmin()) {
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit();
}

$id      = intval($_POST['id'] ?? 0);
$fn      = trim($_POST['fname'] ?? '');
$ln      = trim($_POST['lname'] ?? '');
$mid     = trim($_POST['m_initial'] ?? '');
$gender  = $_POST['gender'] ?? '';
$height  = floatval($_POST['height'] ?? 0);
$h_unit  = $_POST['height_unit'] ?? 'cm';
$weight  = floatval($_POST['weight'] ?? 0);
$w_unit  = $_POST['weight_unit'] ?? 'kg';
$guardian = trim($_POST['guardian_name'] ?? '');
$g_num   = trim($_POST['guardian_number'] ?? '');
$g_email = trim($_POST['guardian_email'] ?? '');

if ($id === 0 || empty($fn) || empty($ln) || $height <= 0 || $weight <= 0) {
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Recalculate BMI
if ($h_unit == 'cm')        { $h_m = $height / 100; }
elseif ($h_unit == 'm')     { $h_m = $height; }
elseif ($h_unit == 'inch')  { $h_m = $height * 0.0254; }
else                        { $h_m = $height * 0.3048; }

$w_kg = ($w_unit == 'lbs') ? round($weight * 0.453592, 2) : $weight;
$bmi  = round($w_kg / ($h_m * $h_m), 1);

if      ($bmi < 18.5)  { $classification = 'Underweight'; }
elseif  ($bmi <= 24.9) { $classification = 'Normal Weight'; }
elseif  ($bmi <= 29.9) { $classification = 'Overweight'; }
else                   { $classification = 'Obese'; }

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database error']);
    exit();
}

$stmt = $conn->prepare("UPDATE stdRecord SET std_first_name=?, std_last_name=?, std_mid_initial=?, gender=?, height=?, height_unit=?, weight=?, weight_unit=?, bmi=?, classification=?, guardian_name=?, guardian_number=?, guardian_email=? WHERE id=?");
$stmt->bind_param("ssssdsdsdssssi", $fn, $ln, $mid, $gender, $height, $h_unit, $weight, $w_unit, $bmi, $classification, $guardian, $g_num, $g_email, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'bmi' => $bmi,
        'classification' => $classification
    ]);
} else {
    echo json_encode(['error' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>
