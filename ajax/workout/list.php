<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT w.*, m.member_code, m.full_name, t.full_name AS trainer_name
    FROM workout_plans w
    JOIN members m ON m.id = w.member_id
    LEFT JOIN trainers t ON t.id = w.trainer_id
    ORDER BY w.id DESC
")->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
