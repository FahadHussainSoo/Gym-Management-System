<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id            = (int)($_POST['id'] ?? 0);
$memberId      = (int)($_POST['member_id'] ?? 0);
$planId        = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
$amount        = (float)($_POST['amount'] ?? 0);
$method        = $_POST['payment_method'] ?? 'Cash';
$status        = $_POST['payment_status'] ?? 'Paid';
$paymentDate   = $_POST['payment_date'] ?? '';
$notes         = trim($_POST['notes'] ?? '') ?: null;

$validMethods = ['Cash', 'Card', 'Bank Transfer', 'Online'];
$validStatus  = ['Paid', 'Pending', 'Overdue'];

if (!$memberId || $amount <= 0 || $paymentDate === '') {
    echo json_encode(['success' => false, 'message' => 'Member, amount, and payment date are required.']);
    exit;
}
if (!in_array($method, $validMethods, true)) $method = 'Cash';
if (!in_array($status, $validStatus, true)) $status = 'Paid';

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE payments SET member_id=?, plan_id=?, amount=?, payment_method=?, payment_status=?, payment_date=?, notes=? WHERE id=?");
        $stmt->execute([$memberId, $planId, $amount, $method, $status, $paymentDate, $notes, $id]);
        log_activity($pdo, 'payment_update', "Payment #$id updated (Rs " . number_format($amount, 2) . ")");
        echo json_encode(['success' => true, 'message' => 'Payment updated successfully.', 'id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_method, payment_status, payment_date, notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$memberId, $planId, $amount, $method, $status, $paymentDate, $notes]);
        $newId = $pdo->lastInsertId();
        log_activity($pdo, 'payment_create', "Payment of Rs " . number_format($amount, 2) . " recorded");
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.', 'id' => $newId]);
    }
} catch (Throwable $e) {
    error_log('payments/save.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong while saving this payment.']);
}
