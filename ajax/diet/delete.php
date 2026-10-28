<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$pdo->prepare("DELETE FROM diet_plans WHERE id = ?")->execute([$id]);
log_activity($pdo, 'diet_delete', "Diet plan #$id deleted");
echo json_encode(['success' => true]);
