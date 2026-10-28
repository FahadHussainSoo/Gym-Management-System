<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Only a super admin can restore the database.']);
    exit;
}

if (empty($_FILES['sql_file']['name'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit;
}
if ($_FILES['sql_file']['size'] > 20 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 20MB).']);
    exit;
}
$ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql') {
    echo json_encode(['success' => false, 'message' => 'Only .sql files are accepted.']);
    exit;
}

$sql = file_get_contents($_FILES['sql_file']['tmp_name']);
if ($sql === false) {
    echo json_encode(['success' => false, 'message' => 'Could not read the uploaded file.']);
    exit;
}

// Strip comment lines, then split on statement-terminating semicolons.
// NOTE: this naive splitter does not support statements containing literal
// semicolons inside string values, or stored procedures/triggers with
// DELIMITER blocks. It's sufficient for straight schema+data dumps such as
// the one produced by ajax/settings/backup.php.
$sql = preg_replace('/^--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', explode(";\n", $sql)));

$executed = 0;
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        $pdo->exec($stmt);
        $executed++;
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    log_activity($pdo, 'database_restore', "Restored database from uploaded file ($executed statements executed)");
    echo json_encode(['success' => true, 'message' => "Restore complete. $executed statements executed."]);
} catch (PDOException $e) {
    error_log('restore.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Restore failed partway through: ' . $e->getMessage() . '. Your database may now be partially restored — restore from a fresh backup to recover.']);
}
