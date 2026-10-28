<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid trainer ID.']);
    exit;
}

try {
    // trainer_schedule / trainer_salary / trainer_attendance cascade via FK.
    // members.assigned_trainer_id is set to NULL automatically (ON DELETE SET NULL).
    $stmt = $pdo->prepare("DELETE FROM trainers WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'Trainer deleted successfully.']);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $e->getMessage()]);
}
