<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

$id       = (int)($_POST['id'] ?? 0);
$name     = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '') ?: null;
$price    = (float)($_POST['price'] ?? 0);
$stock    = (int)($_POST['stock'] ?? 0);

if ($name === '') {
    respond(false, 'Product name is required.');
}
if ($price < 0 || $stock < 0) {
    respond(false, 'Price and stock cannot be negative.');
}

try {
    // Handle image upload (optional)
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        if (!is_dir(PRODUCT_UPLOAD_DIR)) mkdir(PRODUCT_UPLOAD_DIR, 0755, true);

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            respond(false, 'Image upload failed. Please try a different file.');
        }
        if ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            respond(false, 'Image must be under 3MB.');
        }
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $realMime = mime_content_type($_FILES['image']['tmp_name']);
        if (!isset($mimeMap[$realMime])) {
            respond(false, 'Invalid image. Allowed: jpg, jpeg, png, webp.');
        }
        $ext = $mimeMap[$realMime];
        $filename = 'product_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], PRODUCT_UPLOAD_DIR . $filename);
        $image = $filename;
    }

    if ($id > 0) {
        $sql = "UPDATE products SET name=:name, category=:category, price=:price, stock=:stock" .
               ($image ? ", image=:image" : "") . " WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $params = [':name' => $name, ':category' => $category, ':price' => $price, ':stock' => $stock, ':id' => $id];
        if ($image) $params[':image'] = $image;
        $stmt->execute($params);
        log_activity($pdo, 'product_update', "Product \"$name\" updated");
        respond(true, 'Product updated successfully.', ['id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, image) VALUES (:name, :category, :price, :stock, :image)");
        $stmt->execute([':name' => $name, ':category' => $category, ':price' => $price, ':stock' => $stock, ':image' => $image]);
        $newId = $pdo->lastInsertId();
        log_activity($pdo, 'product_create', "Product \"$name\" added");
        respond(true, 'Product added successfully.', ['id' => $newId]);
    }
} catch (Throwable $e) {
    error_log('store/save.php: ' . $e->getMessage());
    respond(false, 'Something went wrong while saving this product. Please try again.');
}
