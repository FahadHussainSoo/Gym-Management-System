<?php
require_once __DIR__ . '/../../config/session.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM trainers WHERE id = ?");
$stmt->execute([$id]);
$trainer = $stmt->fetch();
if (!$trainer) die('Trainer not found.');

// Weekly schedule
$schedule = $pdo->prepare("SELECT * FROM trainer_schedule WHERE trainer_id = ?
                            ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
$schedule->execute([$id]);
$schedule = $schedule->fetchAll();

// Assigned members
$assigned = $pdo->prepare("SELECT id, member_code, full_name, phone, status FROM members WHERE assigned_trainer_id = ? ORDER BY full_name");
$assigned->execute([$id]);
$assigned = $assigned->fetchAll();

// Salary history
$salaries = $pdo->prepare("SELECT * FROM trainer_salary WHERE trainer_id = ? ORDER BY month_year DESC");
$salaries->execute([$id]);
$salaries = $salaries->fetchAll();

// Attendance summary (current month)
$curMonth = date('Y-m');
$attSummary = $pdo->prepare("SELECT status, COUNT(*) c FROM trainer_attendance
                              WHERE trainer_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?
                              GROUP BY status");
$attSummary->execute([$id, $curMonth]);
$attCounts = ['Present' => 0, 'Absent' => 0, 'Half Day' => 0, 'Leave' => 0];
foreach ($attSummary->fetchAll() as $row) $attCounts[$row['status']] = (int)$row['c'];

$pageTitle = 'Trainer Profile';
include __DIR__ . '/../../includes/header.php';
?>

<div class="profile-header d-flex flex-wrap align-items-center gap-4 mb-4">
  <img src="<?php echo BASE_URL . htmlspecialchars($trainer['profile_picture']); ?>" class="member-thumb-lg">
  <div class="flex-grow-1">
    <h3 class="mb-1"><?php echo htmlspecialchars($trainer['full_name']); ?></h3>
    <p class="mb-1 opacity-75"><i class="fa-solid fa-id-badge me-1"></i> <?php echo htmlspecialchars($trainer['trainer_code']); ?> &nbsp;|&nbsp;
       <i class="fa-solid fa-dumbbell me-1"></i> <?php echo htmlspecialchars($trainer['specialization'] ?: 'General'); ?></p>
    <span class="<?php echo $trainer['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
      <?php echo $trainer['status']; ?>
    </span>
  </div>
  <div class="no-print">
    <a href="index.php" class="btn btn-outline-light">
      <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-premium mb-3">
      <div class="card-header"><i class="fa-solid fa-user me-2"></i>Basic Details</div>
      <div class="card-body">
        <p><b>Gender:</b> <?php echo htmlspecialchars($trainer['gender']); ?></p>
        <p><b>Phone:</b> <?php echo htmlspecialchars($trainer['phone']); ?></p>
        <p><b>Email:</b> <?php echo htmlspecialchars($trainer['email'] ?: '—'); ?></p>
        <p><b>Address:</b> <?php echo htmlspecialchars($trainer['address'] ?: '—'); ?></p>
        <p><b>Experience:</b> <?php echo htmlspecialchars($trainer['experience_years']); ?> yrs</p>
        <p><b>Certification:</b> <?php echo htmlspecialchars($trainer['certification'] ?: '—'); ?></p>
        <p class="mb-0"><b>Joining Date:</b> <?php echo htmlspecialchars($trainer['joining_date']); ?></p>
      </div>
    </div>

    <div class="card card-premium mb-3">
      <div class="card-header"><i class="fa-solid fa-calendar-check me-2"></i>Attendance Summary (<?php echo date('F Y'); ?>)</div>
      <div class="card-body">
        <div class="row g-2 text-center">
          <div class="col-6">
            <div class="stat-card green p-2"><h3 class="mb-0"><?php echo $attCounts['Present']; ?></h3><p class="mb-0">Present</p></div>
          </div>
          <div class="col-6">
            <div class="stat-card pink p-2"><h3 class="mb-0"><?php echo $attCounts['Absent']; ?></h3><p class="mb-0">Absent</p></div>
          </div>
          <div class="col-6">
            <div class="stat-card cyan p-2"><h3 class="mb-0"><?php echo $attCounts['Half Day']; ?></h3><p class="mb-0">Half Day</p></div>
          </div>
          <div class="col-6">
            <div class="stat-card purple p-2"><h3 class="mb-0"><?php echo $attCounts['Leave']; ?></h3><p class="mb-0">Leave</p></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card card-premium mb-3">
      <div class="card-header"><i class="fa-solid fa-clock me-2"></i>Weekly Schedule</div>
      <div class="card-body">
        <?php if ($schedule): ?>
          <table class="table table-hover align-middle">
            <thead><tr><th>Day</th><th>Time</th><th>Session Type</th><th>Notes</th></tr></thead>
            <tbody>
              <?php foreach ($schedule as $s): ?>
                <tr>
                  <td><?php echo htmlspecialchars($s['day_of_week']); ?></td>
                  <td><?php echo htmlspecialchars(substr($s['start_time'],0,5)) . ' - ' . htmlspecialchars(substr($s['end_time'],0,5)); ?></td>
                  <td><?php echo htmlspecialchars($s['session_type'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($s['notes'] ?: '—'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0">No schedule slots added yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card card-premium mb-3">
      <div class="card-header"><i class="fa-solid fa-users me-2"></i>Assigned Members (<?php echo count($assigned); ?>)</div>
      <div class="card-body">
        <?php if ($assigned): ?>
          <table class="table table-hover align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($assigned as $a): ?>
                <tr>
                  <td><?php echo htmlspecialchars($a['member_code']); ?></td>
                  <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                  <td><?php echo htmlspecialchars($a['phone']); ?></td>
                  <td><span class="<?php echo $a['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $a['status']; ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0">No members currently assigned to this trainer.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card card-premium">
      <div class="card-header"><i class="fa-solid fa-sack-dollar me-2"></i>Salary History</div>
      <div class="card-body">
        <?php if ($salaries): ?>
          <table class="table table-hover align-middle">
            <thead><tr><th>Month</th><th>Base</th><th>Bonus</th><th>Deduction</th><th>Net</th><th>Status</th><th>Paid On</th></tr></thead>
            <tbody>
              <?php foreach ($salaries as $s): ?>
                <tr>
                  <td><?php echo htmlspecialchars($s['month_year']); ?></td>
                  <td>Rs <?php echo number_format($s['base_amount'], 2); ?></td>
                  <td>Rs <?php echo number_format($s['bonus'], 2); ?></td>
                  <td>Rs <?php echo number_format($s['deduction'], 2); ?></td>
                  <td class="fw-600">Rs <?php echo number_format($s['net_amount'], 2); ?></td>
                  <td><span class="<?php echo $s['payment_status'] === 'Paid' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $s['payment_status']; ?></span></td>
                  <td><?php echo htmlspecialchars($s['payment_date'] ?: '—'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0">No salary records yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
