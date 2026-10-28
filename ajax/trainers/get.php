<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid trainer ID.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM trainers WHERE id = ?");
$stmt->execute([$id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    echo json_encode(['status' => 'error', 'message' => 'Trainer not found.']);
    exit;
}

echo json_encode(['status' => 'success', 'trainer' => $trainer]);
