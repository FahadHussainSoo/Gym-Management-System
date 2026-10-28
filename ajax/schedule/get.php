<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM class_schedule WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

echo $row ? json_encode(['success' => true, 'data' => $row]) : json_encode(['success' => false, 'message' => 'Class not found.']);
