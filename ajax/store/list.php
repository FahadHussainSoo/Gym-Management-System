<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$rows = $pdo->query("SELECT id, name, category, price, stock, image FROM products ORDER BY id DESC")->fetchAll();

foreach ($rows as &$r) {
    $r['image_url'] = !empty($r['image'])
        ? PRODUCT_UPLOAD_URL . htmlspecialchars($r['image'])
        : BASE_URL . 'assets/img/default-product.png';
}
unset($r);

echo json_encode(['success' => true, 'data' => $rows]);
