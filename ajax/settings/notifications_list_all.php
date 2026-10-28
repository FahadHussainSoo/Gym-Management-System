<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 300")->fetchAll();
echo json_encode(['success' => true, 'data' => $rows]);
