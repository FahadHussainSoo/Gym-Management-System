<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("
    SELECT s.id, s.sale_date, s.quantity, s.unit_price, s.total,
           pr.name AS product_name,
           m.full_name AS member_name, m.member_code
    FROM product_sales s
    JOIN products pr ON pr.id = s.product_id
    LEFT JOIN members m ON m.id = s.member_id
    ORDER BY s.id DESC
    LIMIT 100
")->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
