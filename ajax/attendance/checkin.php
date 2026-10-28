<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$memberId = (int)($_POST['member_id'] ?? 0);
if (!$memberId) { echo json_encode(['success' => false, 'message' => 'Missing member.']); exit; }

$open = $pdo->prepare("SELECT id FROM attendance WHERE member_id = ? AND check_out IS NULL");
$open->execute([$memberId]);
if ($open->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This member is already checked in.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO attendance (member_id, check_in) VALUES (?, NOW())");
$stmt->execute([$memberId]);

$name = $pdo->prepare("SELECT full_name FROM members WHERE id = ?");
$name->execute([$memberId]);
log_activity($pdo, 'attendance_checkin', ($name->fetchColumn() ?: 'Member') . ' checked in');

echo json_encode(['success' => true, 'message' => 'Checked in.']);
