<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$current = $_POST['current_status'] ?? 'Active';
$new = $current === 'Active' ? 'Inactive' : 'Active';

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid member ID.']);
    exit;
}

try {
    $pdo->prepare("UPDATE members SET status = ? WHERE id = ?")->execute([$new, $id]);
    $pdo->prepare("INSERT INTO membership_history (member_id, action, remarks) VALUES (?, 'Status Changed', ?)")
        ->execute([$id, "Status changed to $new"]);

    echo json_encode(['status' => 'success', 'message' => "Member marked as $new.", 'new_status' => $new]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update status: ' . $e->getMessage()]);
}
