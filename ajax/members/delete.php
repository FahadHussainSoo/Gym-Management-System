<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid member ID.']);
    exit;
}

try {
    // Related medical_info, emergency_contacts, membership_history rows
    // cascade automatically via FOREIGN KEY ... ON DELETE CASCADE.
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'Member deleted successfully.']);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $e->getMessage()]);
}
