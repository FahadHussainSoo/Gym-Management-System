<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
require_once __DIR__ . '/_queries.php';

[$type, $from, $to] = resolve_report_params();
$report = get_report_data($pdo, $type, $from, $to);
log_activity($pdo, 'export_pdf', "Exported {$report['title']} ($from to $to)");

$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
$autoload = __DIR__ . '/../../vendor/autoload.php';
$dompdfAvailable = file_exists($autoload);
if ($dompdfAvailable) {
    require_once $autoload;
    $dompdfAvailable = class_exists(\Dompdf\Dompdf::class);
}

ob_start();
?>
<html>
<head>
<style>
  body{ font-family: DejaVu Sans, Arial, sans-serif; color:#1f2330; font-size:12px; }
  h1{ font-size:18px; color:#4F46E5; margin-bottom:0; }
  .meta{ color:#6b7280; font-size:11px; margin-bottom:14px; }
  table{ width:100%; border-collapse:collapse; margin-top:10px; }
  th{ background:#4F46E5; color:#fff; padding:6px 8px; text-align:left; font-size:11px; }
  td{ padding:6px 8px; border-bottom:1px solid #e7e9f1; font-size:11px; }
  .summary{ margin-top:10px; }
  .summary span{ display:inline-block; background:#F5F7FB; border:1px solid #e7e9f1; border-radius:6px; padding:6px 12px; margin-right:8px; font-size:11px; }
  <?php if (!$dompdfAvailable): ?>
  .print-bar{ text-align:center; margin:0 0 18px; }
  .print-bar button{ background:linear-gradient(135deg,#4F46E5,#8B5CF6); color:#fff; border:none; padding:12px 26px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; }
  @media print { .print-bar{ display:none; } body{ padding:0; } }
  <?php endif; ?>
</style>
</head>
<body>
  <?php if (!$dompdfAvailable): ?>
  <div class="print-bar"><button onclick="window.print()">🖨️ Print / Save as PDF</button></div>
  <?php endif; ?>

  <h1><?php echo htmlspecialchars($gymName); ?> — <?php echo htmlspecialchars($report['title']); ?></h1>
  <div class="meta">Range: <?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?> &nbsp;|&nbsp; Generated: <?php echo date('d M Y, h:i A'); ?></div>

  <div class="summary">
    <?php foreach ($report['summary'] as $s): ?>
      <span><strong><?php echo htmlspecialchars((string)$s['value']); ?></strong> — <?php echo htmlspecialchars($s['label']); ?></span>
    <?php endforeach; ?>
  </div>

  <table>
    <thead><tr><?php foreach ($report['columns'] as $c) echo '<th>' . htmlspecialchars($c) . '</th>'; ?></tr></thead>
    <tbody>
      <?php foreach ($report['rows'] as $r): ?>
        <tr><?php foreach ($r as $v) echo '<td>' . htmlspecialchars((string)$v) . '</td>'; ?></tr>
      <?php endforeach; ?>
      <?php if (!$report['rows']): ?>
        <tr><td colspan="<?php echo count($report['columns']); ?>">No records in this date range.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (!$dompdfAvailable): ?>
  <script>window.onload = function(){ setTimeout(function(){ window.print(); }, 350); };</script>
  <?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();
$filename = $type . '_report_' . date('Ymd_His') . '.pdf';

// ---------------------------------------------------------------------
// Preferred path: Dompdf, if Composer deps are installed - produces a
// real downloaded .pdf file. Run "composer install" to enable this.
// ---------------------------------------------------------------------
if ($dompdfAvailable) {
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

// ---------------------------------------------------------------------
// Fallback path: always works, zero dependencies. Opens a print-ready
// page in the browser with "Save as PDF" pre-triggered - no composer
// install required. This is what runs on a fresh checkout.
// ---------------------------------------------------------------------
echo $html;
