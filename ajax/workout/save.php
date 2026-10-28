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
$start = $_POST['start_date'] ?? '';
$end = $_POST['end_date'] ?: null;
$status = $_POST['status'] ?? 'Active';

if (!$memberId || $title === '' || $start === '') {
    echo json_encode(['success' => false, 'message' => 'Member, title, and start date are required.']);
    exit;
}

$exercises = json_decode($_POST['exercises_json'] ?? '[]', true) ?: [];
$validDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

try {
    $pdo->beginTransaction();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE workout_plans SET member_id=?, trainer_id=?, title=?, goal=?, start_date=?, end_date=?, status=? WHERE id=?");
        $stmt->execute([$memberId, $trainerId, $title, $goal, $start, $end, $status, $id]);
        $pdo->prepare("DELETE FROM workout_plan_exercises WHERE workout_plan_id = ?")->execute([$id]);
        $planId = $id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO workout_plans (member_id, trainer_id, title, goal, start_date, end_date, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$memberId, $trainerId, $title, $goal, $start, $end, $status]);
        $planId = $pdo->lastInsertId();
    }

    $ins = $pdo->prepare("INSERT INTO workout_plan_exercises (workout_plan_id, day_of_week, exercise_name, sets, reps, notes, sort_order) VALUES (?,?,?,?,?,?,?)");
    $order = 0;
    foreach ($exercises as $ex) {
        $day = in_array($ex['day_of_week'] ?? '', $validDays, true) ? $ex['day_of_week'] : 'Mon';
        $name = trim($ex['exercise_name'] ?? '');
        if ($name === '') continue;
        $ins->execute([$planId, $day, $name, $ex['sets'] ?: null, $ex['reps'] ?: null, $ex['notes'] ?: null, $order++]);
    }

    $pdo->commit();
    log_activity($pdo, $id ? 'workout_update' : 'workout_create', "Workout plan \"$title\" saved");
    echo json_encode(['success' => true, 'message' => $id ? 'Plan updated.' : 'Plan created.']);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('workout/save.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save the workout plan.']);
}
