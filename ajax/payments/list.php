<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT p.id, p.amount, p.payment_method, p.payment_status, p.payment_date, p.notes,
           m.id AS member_id, m.member_code, m.full_name,
           pl.plan_name
    FROM payments p
    JOIN members m ON m.id = p.member_id
    LEFT JOIN membership_plans pl ON pl.id = p.plan_id
    ORDER BY p.id DESC
")->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
