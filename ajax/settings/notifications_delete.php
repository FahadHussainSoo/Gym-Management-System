<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification.']);
    exit;
}

$pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$id]);
echo json_encode(['success' => true]);
