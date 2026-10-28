<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
require_once __DIR__ . '/../../modules/reports/_queries.php';

header('Content-Type: application/json');

[$type, $from, $to] = resolve_report_params();
echo json_encode(['success' => true] + get_report_data($pdo, $type, $from, $to));
