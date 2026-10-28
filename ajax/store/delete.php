<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    log_activity($pdo, 'product_delete', "Product \"{$product['name']}\" deleted");
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
} catch (Throwable $e) {
    error_log('store/delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not delete this product.']);
}
