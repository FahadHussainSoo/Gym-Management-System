<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale record.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM product_sales WHERE id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    if (!$sale) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Sale record not found.']);
        exit;
    }

    $pdo->prepare("DELETE FROM product_sales WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$sale['quantity'], $sale['product_id']]);

    $pdo->commit();
    log_activity($pdo, 'product_sale_delete', "Sale #$id deleted and stock restored");
    echo json_encode(['success' => true, 'message' => 'Sale removed and stock restored.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('store/sale_delete.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not remove this sale.']);
}
