<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Activity Logs';

$logs = $pdo->query("
    SELECT al.*, a.name AS admin_name
    FROM activity_logs al LEFT JOIN admins a ON a.id = al.admin_id
    ORDER BY al.id DESC LIMIT 500
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card card-premium">
  <div class="card-header">Activity Logs</div>
  <div class="card-body">
    <table id="logsTable" class="table table-hover" style="width:100%">
      <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td><?php echo date('d M Y, h:i A', strtotime($l['created_at'])); ?></td>
            <td><?php echo htmlspecialchars($l['admin_name'] ?? 'System'); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($l['action']); ?></span></td>
            <td><?php echo htmlspecialchars($l['description'] ?? ''); ?></td>
            <td class="text-muted small"><?php echo htmlspecialchars($l['ip_address'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#logsTable').DataTable({ responsive: true, order: [[0, 'desc']], pageLength: 25 });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
