<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
// GET request triggered by clicking a link, not fetch — verify CSRF from the query string instead of POST.
if (empty($_GET['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    http_response_code(419);
    die('Invalid or expired request. Go back to Settings and try again.');
}

$filename = 'gym_system_backup_' . date('Ymd_His') . '.sql';
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "-- Forge Athletic / gym_system database backup\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $create['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`");
    foreach ($rows as $row) {
        $cols = array_keys($row);
        $vals = array_map(function ($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote($v);
        }, array_values($row));
        echo "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";

log_activity($pdo, 'database_backup', 'Downloaded a full database backup');
