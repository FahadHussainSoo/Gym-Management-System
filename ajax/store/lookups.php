<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

echo json_encode([
    'members' => $pdo->query("SELECT id, member_code, full_name FROM members WHERE status='Active' ORDER BY full_name")->fetchAll(),
]);
