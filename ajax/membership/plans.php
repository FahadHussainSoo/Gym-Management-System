<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("SELECT id, plan_name, price, duration_months FROM membership_plans ORDER BY price ASC")->fetchAll();
echo json_encode(['success' => true, 'data' => $rows]);
