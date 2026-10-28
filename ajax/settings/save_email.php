<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$fields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption'];

try {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($fields as $f) {
        $stmt->execute([$f, trim($_POST[$f] ?? '')]);
    }
    log_activity($pdo, 'settings_update', 'Updated email/SMTP settings');
    echo json_encode(['success' => true, 'message' => 'Email settings saved.']);
} catch (Throwable $e) {
    error_log('save_email.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save email settings.']);
}
