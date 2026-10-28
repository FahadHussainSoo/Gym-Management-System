<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

echo json_encode([
    'members' => $pdo->query("SELECT id, member_code, full_name FROM members ORDER BY full_name")->fetchAll(),
    'plans'   => $pdo->query("SELECT id, plan_name, price FROM membership_plans ORDER BY plan_name")->fetchAll(),
]);
