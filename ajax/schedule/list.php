<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT c.*, t.full_name AS trainer_name
    FROM class_schedule c LEFT JOIN trainers t ON t.id = c.trainer_id
")->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
