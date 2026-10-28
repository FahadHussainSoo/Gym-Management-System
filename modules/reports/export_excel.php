<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
require_once __DIR__ . '/_queries.php';

[$type, $from, $to] = resolve_report_params();
$report = get_report_data($pdo, $type, $from, $to);
log_activity($pdo, 'export_excel', "Exported {$report['title']} ($from to $to)");

$autoload = __DIR__ . '/../../vendor/autoload.php';
$filename = $type . '_report_' . date('Ymd_His') . '.xlsx';

// ---------------------------------------------------------------------
// Preferred path: PhpSpreadsheet (richer styling), if Composer deps are
// installed. Run "composer install" in the project root to enable this.
// ---------------------------------------------------------------------
if (file_exists($autoload)) {
    require_once $autoload;

    if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
        use_phpspreadsheet($report, $type, $from, $filename);
        exit;
    }
}

// ---------------------------------------------------------------------
// Fallback path: always works, zero dependencies. Produces a genuine,
// valid .xlsx file using PHP's built-in ZipArchive extension - no
// composer install required. This is what runs on a fresh checkout.
// ---------------------------------------------------------------------
require_once __DIR__ . '/../../includes/SimpleXlsxWriter.php';

$writer = new SimpleXlsxWriter($report['title']);
$writer->setTitle($report['title'] . ' (' . $from . ' to ' . $to . ')');
$writer->setHeaders($report['columns']);
$writer->setRows($report['rows']);
$writer->output($filename);

/**
 * Builds and streams the report using PhpSpreadsheet (only called when
 * vendor/autoload.php + the library are actually available).
 */
function use_phpspreadsheet(array $report, string $type, string $from, string $filename): void
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($report['title'], 0, 31));

    $sheet->setCellValue('A1', $report['title'] . ' (' . $from . ' to ' . ($_GET['to'] ?? date('Y-m-d')) . ')');
    $sheet->mergeCells('A1:' . chr(64 + max(count($report['columns']), 1)) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $col = 'A'; $row = 3;
    foreach ($report['columns'] as $c) { $sheet->setCellValue($col . $row, $c); $col++; }
    $headerRange = 'A' . $row . ':' . chr(64 + count($report['columns'])) . $row;
    $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');

    $row++;
    foreach ($report['rows'] as $r) {
        $col = 'A';
        foreach ($r as $val) { $sheet->setCellValue($col . $row, $val); $col++; }
        $row++;
    }
    foreach (range('A', chr(64 + count($report['columns']))) as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
}
