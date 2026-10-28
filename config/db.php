<?php
/**
 * Database Connection (PDO)
 * Gym Management System
 */
define('DB_HOST', 'sql201.infinityfree.com');
define('DB_NAME', 'if0_42536478_gym_system');
define('DB_USER', 'if0_42536478');
define('DB_PASS', 'ENFBRCiDneos');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB connection failed: ' . $e->getMessage());
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed. Check config/db.php credentials and that the gym_system database has been imported.']));
}

// Base URL / paths used across the Members module
define('BASE_URL', '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/profile_pictures/');
define('UPLOAD_URL', BASE_URL . 'uploads/profile_pictures/');

// Product Store image uploads (Phase 4)
define('PRODUCT_UPLOAD_DIR', __DIR__ . '/../uploads/product_images/');
define('PRODUCT_UPLOAD_URL', BASE_URL . 'uploads/product_images/');
