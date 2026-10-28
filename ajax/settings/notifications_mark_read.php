<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
echo json_encode(['success' => true]);
