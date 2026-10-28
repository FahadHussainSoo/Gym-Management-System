<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['success' => true, 'data' => []]); exit; }

$stmt = $pdo->prepare("
    SELECT id, member_code, full_name FROM members
    WHERE status = 'Active' AND (full_name LIKE ? OR member_code LIKE ? OR phone LIKE ?)
    ORDER BY full_name LIMIT 10
");
$like = "%$q%";
$stmt->execute([$like, $like, $like]);
echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
