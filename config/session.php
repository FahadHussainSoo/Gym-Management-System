<?php
/**
 * Session bootstrap, login guard, and CSRF helpers.
 * Include this at the very top of every protected page,
 * BEFORE includes/header.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie behavior.
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Safety net for ajax/*.php endpoints: if a fatal PHP error (e.g. a missing
 * extension function, a TypeError, etc.) happens after this file is loaded,
 * convert it into a real JSON error response instead of a blank page or a
 * broken partial response — which is what makes "Save" look like it silently
 * does nothing. The real error is still written to the PHP error log either way.
 */
if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/ajax/') !== false) {
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            error_log('Fatal error in ' . $error['file'] . ':' . $error['line'] . ' — ' . $error['message']);
            echo json_encode([
                'status'  => 'error',
                'success' => false,
                'message' => 'Server error: ' . $error['message'] . ' (see PHP error log for details)',
            ]);
        }
    });
}

/** Redirect to login if no authenticated admin is in session. */
function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . BASE_URL . 'auth/login.php?redirect=' . $redirect);
        exit;
    }
}

/** Same as require_login() but for AJAX/API endpoints — returns JSON 401 instead of redirecting. */
function require_login_ajax(): void {
    if (empty($_SESSION['admin_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'success' => false, 'message' => 'Not authenticated. Please log in again.']));
    }
}

/** Optional role gate, e.g. require_role('super_admin'). */
function require_role(string $role): void {
    require_login();
    if (($_SESSION['admin_role'] ?? '') !== $role) {
        http_response_code(403);
        die('You do not have permission to view this page.');
    }
}

/** Generate (or reuse) a CSRF token for this session. */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** HTML hidden input embedding the CSRF token — echo this inside every <form>. */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/** Call at the top of every state-changing AJAX/POST handler. */
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        die(json_encode(['status' => 'error', 'message' => 'Your session expired. Please refresh the page and try again.']));
    }
}

/** Write one row to activity_logs. Never throws — logging failures shouldn't break the request. */
function log_activity(PDO $pdo, string $action, string $description = ''): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?,?,?,?)");
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('activity log failed: ' . $e->getMessage());
    }
}

/** Read a single settings value with a fallback default. */
function get_setting(PDO $pdo, string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach ($pdo->query("SELECT setting_key, setting_value FROM system_settings") as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

/** Insert a notification only if an identical one hasn't already fired today (avoids re-spamming on every page load). */
function notify_once(PDO $pdo, string $title, string $message, string $type = 'info'): void {
    try {
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE title = ? AND message = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$title, $message]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO notifications (title, message, type) VALUES (?,?,?)")->execute([$title, $message, $type]);
        }
    } catch (Throwable $e) {
        error_log('notify_once failed: ' . $e->getMessage());
    }
}

/**
 * Scans key business data (stock, expiring memberships, overdue payments) and raises
 * fresh notifications when something needs attention. Safe to call on every page load —
 * notify_once() de-dupes per calendar day. Called once from includes/header.php.
 */
function sync_system_notifications(PDO $pdo): void {
    static $ran = false;
    if ($ran) return;
    $ran = true;

    try {
        // Low stock (but not yet empty)
        $lowStock = $pdo->query("SELECT name, stock FROM products WHERE stock > 0 AND stock <= 10")->fetchAll();
        foreach ($lowStock as $p) {
            notify_once($pdo, 'Low stock alert', "{$p['name']} is running low — {$p['stock']} unit(s) left.", 'warning');
        }

        // Out of stock
        $outOfStock = $pdo->query("SELECT name FROM products WHERE stock <= 0")->fetchAll();
        foreach ($outOfStock as $p) {
            notify_once($pdo, 'Out of stock', "{$p['name']} is out of stock.", 'danger');
        }

        // Memberships expiring within 7 days
        $expiring = $pdo->query("
            SELECT full_name, member_code, expiry_date FROM members
            WHERE status = 'Active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ")->fetchAll();
        foreach ($expiring as $m) {
            notify_once($pdo, 'Membership expiring soon', "{$m['full_name']} ({$m['member_code']})'s membership expires on {$m['expiry_date']}.", 'warning');
        }

        // Overdue payments
        $overdueCount = (int)$pdo->query("SELECT COUNT(*) c FROM payments WHERE payment_status = 'Overdue'")->fetch()['c'];
        if ($overdueCount > 0) {
            notify_once($pdo, 'Overdue payments', "$overdueCount payment(s) are currently marked overdue.", 'danger');
        }
    } catch (Throwable $e) {
        error_log('sync_system_notifications failed: ' . $e->getMessage());
    }
}
