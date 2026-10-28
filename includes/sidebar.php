<?php
$current = basename($_SERVER['PHP_SELF']);
$moduleDir = basename(dirname($_SERVER['PHP_SELF']));
$gymName = $gymName ?? 'Forge Athletic';
$gymLogo = $gymLogo ?? '';
?>
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-brand">
    <?php if (!empty($gymLogo)): ?>
      <img src="<?php echo BASE_URL . htmlspecialchars($gymLogo); ?>" alt="" class="sidebar-logo-img">
    <?php else: ?>
      <i class="fa-solid fa-dumbbell"></i>
    <?php endif; ?>
    <span><?php echo htmlspecialchars($gymName); ?></span>
  </div>
  <ul class="sidebar-menu">
    <li class="<?php echo $moduleDir === 'dashboard' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'members' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/members/index.php"><i class="fa-solid fa-users"></i><span>Members</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'trainers' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/trainers/index.php"><i class="fa-solid fa-person-running"></i><span>Trainers</span></a>
    </li>

    <li class="sidebar-heading">Training</li>
    <li class="<?php echo $moduleDir === 'membership' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/membership/index.php"><i class="fa-solid fa-id-badge"></i><span>Membership</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'attendance' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/attendance/index.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'workout' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/workout/index.php"><i class="fa-solid fa-dumbbell"></i><span>Workout Plans</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'diet' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/diet/index.php"><i class="fa-solid fa-bowl-food"></i><span>Diet Plans</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'schedule' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/schedule/index.php"><i class="fa-solid fa-calendar-days"></i><span>Class Schedule</span></a>
    </li>

    <li class="sidebar-heading">Sales &amp; Finance</li>
    <li class="<?php echo $moduleDir === 'store' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/store/index.php"><i class="fa-solid fa-cart-shopping"></i><span>Product Store</span></a>
    </li>
    <li class="<?php echo $moduleDir === 'payments' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/payments/index.php"><i class="fa-solid fa-money-check-dollar"></i><span>Payments</span></a>
    </li>

    <li class="sidebar-heading">Reports</li>
    <li class="<?php echo $current === 'member_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/member_reports.php"><i class="fa-solid fa-id-card-clip"></i><span>Member Reports</span></a>
    </li>
    <li class="<?php echo $current === 'trainer_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/trainer_reports.php"><i class="fa-solid fa-chalkboard-user"></i><span>Trainer Reports</span></a>
    </li>
    <li class="<?php echo $current === 'attendance_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/attendance_reports.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance Reports</span></a>
    </li>
    <li class="<?php echo $current === 'revenue_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/revenue_reports.php"><i class="fa-solid fa-sack-dollar"></i><span>Revenue Reports</span></a>
    </li>
    <li class="<?php echo $current === 'membership_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/membership_reports.php"><i class="fa-solid fa-id-badge"></i><span>Membership Reports</span></a>
    </li>
    <li class="<?php echo $current === 'product_sales_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/product_sales_reports.php"><i class="fa-solid fa-cart-shopping"></i><span>Product Sales</span></a>
    </li>
    <li class="<?php echo $current === 'expense_reports.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/reports/expense_reports.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Expense Reports</span></a>
    </li>

    <li class="sidebar-heading">System</li>
    <li class="<?php echo $moduleDir === 'settings' && $current === 'index.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/settings/index.php"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
    </li>
    <li class="<?php echo $current === 'activity_logs.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/settings/activity_logs.php"><i class="fa-solid fa-clock-rotate-left"></i><span>Activity Logs</span></a>
    </li>
    <li class="<?php echo $current === 'notifications.php' ? 'active' : ''; ?>">
      <a href="<?php echo BASE_URL; ?>modules/settings/notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>
    </li>
    <li>
      <a href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </li>
  </ul>
</aside>
