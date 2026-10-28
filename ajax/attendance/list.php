<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$openOnly = !empty($_GET['open']);
$sql = "
    SELECT a.id, a.check_in, a.check_out, m.member_code, m.full_name
    FROM attendance a JOIN members m ON m.id = a.member_id
";
if ($openOnly) $sql .= " WHERE a.check_out IS NULL ORDER BY a.check_in DESC";
else $sql .= " ORDER BY a.check_in DESC LIMIT 500";

echo json_encode(['success' => true, 'data' => $pdo->query($sql)->fetchAll()]);
