<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$productId = (int)($_POST['product_id'] ?? 0);
$memberId  = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
$quantity  = (int)($_POST['quantity'] ?? 0);
$saleDate  = $_POST['sale_date'] ?: date('Y-m-d');

if (!$productId || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Please select a product and a valid quantity.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }
    if ($product['stock'] < $quantity) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => "Only {$product['stock']} unit(s) left in stock."]);
        exit;
    }

    $unitPrice = (float)$product['price'];
    $total = $unitPrice * $quantity;

    $ins = $pdo->prepare("INSERT INTO product_sales (product_id, member_id, quantity, unit_price, total, sale_date) VALUES (?,?,?,?,?,?)");
    $ins->execute([$productId, $memberId, $quantity, $unitPrice, $total, $saleDate]);
    $saleId = $pdo->lastInsertId();

    $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$quantity, $productId]);

    $pdo->commit();
    log_activity($pdo, 'product_sale', "Sold {$quantity} x {$product['name']} (Rs " . number_format($total, 2) . ")");
    echo json_encode(['success' => true, 'message' => 'Sale recorded successfully.', 'id' => $saleId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('store/sell.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong while recording the sale.']);
}
