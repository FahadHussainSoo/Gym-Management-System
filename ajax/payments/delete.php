<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment.']);
    exit;
}

try {
    $pdo->prepare("DELETE FROM payments WHERE id = ?")->execute([$id]);
    log_activity($pdo, 'payment_delete', "Payment #$id deleted");
    echo json_encode(['success' => true, 'message' => 'Payment deleted successfully.']);
} catch (Throwable $e) {
    error_log('payments/delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not delete this payment.']);
}
