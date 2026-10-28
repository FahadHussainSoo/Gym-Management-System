<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT d.*, m.member_code, m.full_name
    FROM diet_plans d JOIN members m ON m.id = d.member_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$plan = $stmt->fetch();
if (!$plan) { echo json_encode(['success' => false, 'message' => 'Plan not found.']); exit; }

$meals = $pdo->prepare("SELECT * FROM diet_plan_meals WHERE diet_plan_id = ? ORDER BY FIELD(meal_type,'Breakfast','Lunch','Dinner','Snack'), sort_order");
$meals->execute([$id]);

echo json_encode(['success' => true, 'plan' => $plan, 'meals' => $meals->fetchAll()]);
