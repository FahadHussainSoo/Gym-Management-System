<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT w.*, m.member_code, m.full_name
    FROM workout_plans w JOIN members m ON m.id = w.member_id
    WHERE w.id = ?
");
$stmt->execute([$id]);
$plan = $stmt->fetch();
if (!$plan) { echo json_encode(['success' => false, 'message' => 'Plan not found.']); exit; }

$ex = $pdo->prepare("SELECT * FROM workout_plan_exercises WHERE workout_plan_id = ? ORDER BY FIELD(day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), sort_order");
$ex->execute([$id]);

echo json_encode(['success' => true, 'plan' => $plan, 'exercises' => $ex->fetchAll()]);
