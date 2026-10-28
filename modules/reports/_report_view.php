<?php
/**
 * Included by modules/reports/{type}_reports.php after these are set:
 *   $reportType  (e.g. 'members')
 *   $reportTitle (e.g. 'Member Reports')
 *   $reportIcon  (Font Awesome class, e.g. 'fa-id-card-clip')
 */
require_once __DIR__ . '/../../config/session.php';
require_login();

$pageTitle = $reportTitle;
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="report-filter-bar fade-in-up">
  <div>
    <label class="form-label small fw-600 mb-1">From</label>
    <input type="date" id="filterFrom" class="form-control form-control-sm" value="<?php echo htmlspecialchars($from); ?>">
  </div>
  <div>
    <label class="form-label small fw-600 mb-1">To</label>
    <input type="date" id="filterTo" class="form-control form-control-sm" value="<?php echo htmlspecialchars($to); ?>">
  </div>
  <div>
    <button class="btn btn-gradient btn-sm" id="applyFilter"><i class="fa-solid fa-filter me-1"></i>Apply</button>
  </div>
  <div class="ms-auto export-btn-group d-flex gap-2">
    <a class="btn btn-outline-gradient btn-sm" id="exportExcelBtn" target="_blank"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
    <a class="btn btn-outline-gradient btn-sm" id="exportPdfBtn" target="_blank"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
    <button class="btn btn-outline-gradient btn-sm" id="printBtn"><i class="fa-solid fa-print me-1"></i>Print</button>
  </div>
</div>

<div class="row g-3 mb-3" id="summaryCards"></div>

<div class="card card-premium mb-3">
  <div class="card-header"><i class="fa-solid <?php echo $reportIcon; ?> me-2"></i><?php echo htmlspecialchars($reportTitle); ?> — Trend</div>
  <div class="card-body"><canvas id="reportChart" height="90"></canvas></div>
</div>

<div class="card card-premium" id="printArea">
  <div class="card-header"><?php echo htmlspecialchars($reportTitle); ?> — Detail</div>
  <div class="card-body">
    <table id="reportTable" class="table table-hover" style="width:100%"></table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
(function () {
  const REPORT_TYPE = <?php echo json_encode($reportType); ?>;
  let table;

  function buildUrl(base) {
    const f = document.getElementById('filterFrom').value;
    const t = document.getElementById('filterTo').value;
    return `${BASE_URL}${base}?type=${REPORT_TYPE}&from=${f}&to=${t}`;
  }

  function refreshExportLinks() {
    document.getElementById('exportExcelBtn').href = buildUrl('modules/reports/export_excel.php');
    document.getElementById('exportPdfBtn').href = buildUrl('modules/reports/export_pdf.php');
  }

  function load() {
    refreshExportLinks();
    $.getJSON(buildUrl('ajax/reports/data.php'), function (res) {
      if (!res.success) return;

      // Summary cards
      const colors = ['purple', 'pink', 'cyan', 'green', 'orange'];
      let cardsHtml = '';
      res.summary.forEach((s, i) => {
        cardsHtml += `<div class="col-6 col-md-3"><div class="stat-card ${colors[i % colors.length]}"><p>${s.label}</p><h3>${s.value}</h3></div></div>`;
      });
      document.getElementById('summaryCards').innerHTML = cardsHtml;

      // Chart
      if (window.reportChartInstance) window.reportChartInstance.destroy();
      window.reportChartInstance = new Chart(document.getElementById('reportChart'), {
        type: 'bar',
        data: { labels: res.chart.labels, datasets: [{ label: res.title, data: res.chart.data, backgroundColor: '#4F46E5', borderRadius: 6 }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
      });

      // Table
      if (table) { table.destroy(); $('#reportTable').empty(); }
      const theadHtml = '<thead><tr>' + res.columns.map(c => `<th>${c}</th>`).join('') + '</tr></thead>';
      $('#reportTable').html(theadHtml);
      table = $('#reportTable').DataTable({
        data: res.rows,
        columns: res.columns.map((c, i) => ({ title: c })),
        responsive: true,
        pageLength: 10
      });
    });
  }

  document.getElementById('applyFilter').addEventListener('click', load);
  document.getElementById('printBtn').addEventListener('click', () => window.print());

  load();
})();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
