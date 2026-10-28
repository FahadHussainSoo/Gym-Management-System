<?php
require_once __DIR__ . '/../../config/session.php';
require_login();

$pageTitle = 'Dashboard';

$totalMembers   = (int)$pdo->query("SELECT COUNT(*) c FROM members WHERE status='Active'")->fetch()['c'];
$totalTrainers  = (int)$pdo->query("SELECT COUNT(*) c FROM trainers WHERE status='Active'")->fetch()['c'];
$monthRevenue   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE payment_status='Paid' AND MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetch()['s'];
$todayAttendance = (int)$pdo->query("SELECT COUNT(*) c FROM attendance WHERE DATE(check_in) = CURDATE()")->fetch()['c'];

$revTrend = $pdo->query("
    SELECT DATE_FORMAT(payment_date, '%b') label, SUM(amount) total
    FROM payments
    WHERE payment_status='Paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(payment_date), MONTH(payment_date)
    ORDER BY YEAR(payment_date), MONTH(payment_date)
")->fetchAll();

$planMix = $pdo->query("
    SELECT p.plan_name, COUNT(m.id) c
    FROM membership_plans p LEFT JOIN members m ON m.membership_plan_id = p.id AND m.status='Active'
    GROUP BY p.id
")->fetchAll();

$recentActivity = $pdo->query("
    SELECT al.action, al.description, al.created_at, a.name AS admin_name
    FROM activity_logs al LEFT JOIN admins a ON a.id = al.admin_id
    ORDER BY al.id DESC LIMIT 8
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card purple fade-in-up" style="animation-delay:.02s;"><i class="fa-solid fa-users"></i><p>Active Members</p><h3><?php echo number_format($totalMembers); ?></h3></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card pink fade-in-up" style="animation-delay:.08s;"><i class="fa-solid fa-person-running"></i><p>Active Trainers</p><h3><?php echo number_format($totalTrainers); ?></h3></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card cyan fade-in-up" style="animation-delay:.14s;"><i class="fa-solid fa-sack-dollar"></i><p>Revenue This Month</p><h3>Rs <?php echo number_format($monthRevenue); ?></h3></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card green fade-in-up" style="animation-delay:.2s;"><i class="fa-solid fa-calendar-check"></i><p>Check-ins Today</p><h3><?php echo number_format($todayAttendance); ?></h3></div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card card-premium h-100">
      <div class="card-header">Revenue — Last 6 Months</div>
      <div class="card-body"><canvas id="revenueChart" height="90"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card card-premium h-100">
      <div class="card-header">Membership Mix</div>
      <div class="card-body"><canvas id="planChart" height="200"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-12">
    <div class="card card-premium">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Activity</span>
        <a href="<?php echo BASE_URL; ?>modules/settings/activity_logs.php" class="text-white small">View all &rarr;</a>
      </div>
      <div class="card-body p-0">
        <table class="table mb-0 table-animated">
          <tbody>
          <?php if (!$recentActivity): ?>
            <tr><td class="p-3 text-muted">No activity recorded yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentActivity as $log): ?>
            <tr>
              <td class="ps-3" style="width:160px;"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['action']); ?></span></td>
              <td><?php echo htmlspecialchars($log['description'] ?: ($log['admin_name'] ?? 'System')); ?></td>
              <td class="text-muted small text-end pe-3" style="width:160px;"><?php echo date('d M, h:i A', strtotime($log['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode(array_column($revTrend, 'label')); ?>,
    datasets: [{
      label: 'Revenue',
      data: <?php echo json_encode(array_map('floatval', array_column($revTrend, 'total'))); ?>,
      borderColor: '#4F46E5',
      backgroundColor: 'rgba(79,70,229,.12)',
      fill: true, tension: .35, pointRadius: 4
    }]
  },
  options: { responsive:true, plugins:{legend:{display:false}} }
});

new Chart(document.getElementById('planChart'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode(array_column($planMix, 'plan_name')); ?>,
    datasets: [{
      data: <?php echo json_encode(array_map('intval', array_column($planMix, 'c'))); ?>,
      backgroundColor: ['#4F46E5', '#EC4899', '#06B6D4', '#F59E0B', '#8B5CF6'],
      borderWidth: 0
    }]
  },
  options: { responsive:true, cutout:'65%', plugins:{legend:{position:'bottom'}} }
});
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
