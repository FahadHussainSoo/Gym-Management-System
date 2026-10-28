<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['class_name'] ?? '');
$trainerId = $_POST['trainer_id'] !== '' ? (int)$_POST['trainer_id'] : null;
$day = $_POST['day_of_week'] ?? 'Mon';
$start = $_POST['start_time'] ?? '';
$end = $_POST['end_time'] ?? '';
$room = trim($_POST['room'] ?? '');
$capacity = (int)($_POST['capacity'] ?? 20);
$status = $_POST['status'] ?? 'Active';

if ($name === '' || $start === '' || $end === '') {
    echo json_encode(['success' => false, 'message' => 'Class name, start time, and end time are required.']);
    exit;
}
if ($end <= $start) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
    exit;
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE class_schedule SET class_name=?, trainer_id=?, day_of_week=?, start_time=?, end_time=?, room=?, capacity=?, status=? WHERE id=?");
        $stmt->execute([$name, $trainerId, $day, $start, $end, $room, $capacity, $status, $id]);
        $msg = 'Class updated.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO class_schedule (class_name, trainer_id, day_of_week, start_time, end_time, room, capacity, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $trainerId, $day, $start, $end, $room, $capacity, $status]);
        $msg = 'Class created.';
    }
    log_activity($pdo, $id ? 'schedule_update' : 'schedule_create', "Class \"$name\" saved");
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Throwable $e) {
    error_log('schedule/save.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save this class.']);
}
