<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT d.*, m.member_code, m.full_name, t.full_name AS trainer_name
    FROM diet_plans d
    JOIN members m ON m.id = d.member_id
    LEFT JOIN trainers t ON t.id = d.trainer_id
    ORDER BY d.id DESC
")->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
