<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$product['image_url'] = !empty($product['image'])
    ? PRODUCT_UPLOAD_URL . htmlspecialchars($product['image'])
    : BASE_URL . 'assets/img/default-product.png';

echo json_encode(['success' => true, 'product' => $product]);
