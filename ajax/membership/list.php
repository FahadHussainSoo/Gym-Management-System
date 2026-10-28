<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT m.id, m.member_code, m.full_name, m.status, m.join_date, m.expiry_date,
           m.membership_plan_id AS plan_id, p.plan_name
    FROM members m LEFT JOIN membership_plans p ON p.id = m.membership_plan_id
    ORDER BY m.id DESC
")->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
