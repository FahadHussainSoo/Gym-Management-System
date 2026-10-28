<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

function save_setting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

try {
    save_setting($pdo, 'gym_name', trim($_POST['gym_name'] ?? ''));
    save_setting($pdo, 'gym_phone', trim($_POST['gym_phone'] ?? ''));
    save_setting($pdo, 'gym_email', trim($_POST['gym_email'] ?? ''));
    save_setting($pdo, 'gym_address', trim($_POST['gym_address'] ?? ''));

    if (!empty($_FILES['gym_logo']['name'])) {
        if ($_FILES['gym_logo']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Logo must be under 2MB.']);
            exit;
        }
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        $realMime = mime_content_type($_FILES['gym_logo']['tmp_name']);
        if (!isset($mimeMap[$realMime])) {
            echo json_encode(['success' => false, 'message' => 'Invalid logo file. Allowed: jpg, png, webp, svg.']);
            exit;
        }
        $dir = __DIR__ . '/../../uploads/logo/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'gym_logo_' . time() . '.' . $mimeMap[$realMime];
        move_uploaded_file($_FILES['gym_logo']['tmp_name'], $dir . $filename);
        save_setting($pdo, 'gym_logo', 'uploads/logo/' . $filename);
    }

    log_activity($pdo, 'settings_update', 'Updated gym information');
    echo json_encode(['success' => true, 'message' => 'Saved.', 'reload' => true]);
} catch (Throwable $e) {
    error_log('save_general.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save settings.']);
}
