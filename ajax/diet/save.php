<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$memberId = (int)($_POST['member_id'] ?? 0);
$trainerId = $_POST['trainer_id'] !== '' ? (int)$_POST['trainer_id'] : null;
$title = trim($_POST['title'] ?? '');
$goal = trim($_POST['goal'] ?? '');
$calorieTarget = $_POST['calorie_target'] !== '' ? (int)$_POST['calorie_target'] : null;
$start = $_POST['start_date'] ?? '';
$end = $_POST['end_date'] ?: null;
$status = $_POST['status'] ?? 'Active';

if (!$memberId || $title === '' || $start === '') {
    echo json_encode(['success' => false, 'message' => 'Member, title, and start date are required.']);
    exit;
}

$meals = json_decode($_POST['meals_json'] ?? '[]', true) ?: [];
$validTypes = ['Breakfast','Lunch','Dinner','Snack'];

try {
    $pdo->beginTransaction();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE diet_plans SET member_id=?, trainer_id=?, title=?, goal=?, calorie_target=?, start_date=?, end_date=?, status=? WHERE id=?");
        $stmt->execute([$memberId, $trainerId, $title, $goal, $calorieTarget, $start, $end, $status, $id]);
        $pdo->prepare("DELETE FROM diet_plan_meals WHERE diet_plan_id = ?")->execute([$id]);
        $planId = $id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO diet_plans (member_id, trainer_id, title, goal, calorie_target, start_date, end_date, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$memberId, $trainerId, $title, $goal, $calorieTarget, $start, $end, $status]);
        $planId = $pdo->lastInsertId();
    }

    $ins = $pdo->prepare("INSERT INTO diet_plan_meals (diet_plan_id, meal_type, food_items, calories, sort_order) VALUES (?,?,?,?,?)");
    $order = 0;
    foreach ($meals as $m) {
        $type = in_array($m['meal_type'] ?? '', $validTypes, true) ? $m['meal_type'] : 'Breakfast';
        $items = trim($m['food_items'] ?? '');
        if ($items === '') continue;
        $ins->execute([$planId, $type, $items, $m['calories'] ?: null, $order++]);
    }

    $pdo->commit();
    log_activity($pdo, $id ? 'diet_update' : 'diet_create', "Diet plan \"$title\" saved");
    echo json_encode(['success' => true, 'message' => $id ? 'Plan updated.' : 'Plan created.']);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('diet/save.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save the diet plan.']);
}
