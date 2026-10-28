<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("UPDATE attendance SET check_out = NOW() WHERE id = ? AND check_out IS NULL");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Session already closed or not found.']);
    exit;
}

log_activity($pdo, 'attendance_checkout', "Attendance #$id checked out");
echo json_encode(['success' => true, 'message' => 'Checked out.']);
