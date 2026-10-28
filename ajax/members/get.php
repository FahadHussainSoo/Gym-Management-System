<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid member ID.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    echo json_encode(['status' => 'error', 'message' => 'Member not found.']);
    exit;
}

$medical = $pdo->prepare("SELECT * FROM member_medical_info WHERE member_id = ?");
$medical->execute([$id]);
$medical = $medical->fetch() ?: [];

$emergency = $pdo->prepare("SELECT * FROM member_emergency_contacts WHERE member_id = ?");
$emergency->execute([$id]);
$emergency = $emergency->fetch() ?: [];

echo json_encode([
    'status' => 'success',
    'member' => $member,
    'medical' => $medical,
    'emergency' => $emergency,
]);
