<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment not found.']);
    exit;
}

echo json_encode(['success' => true, 'payment' => $payment]);
