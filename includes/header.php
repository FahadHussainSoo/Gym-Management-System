<?php
require_once __DIR__ . '/../config/session.php';
require_login();
sync_system_notifications($pdo);
$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
$gymLogo = get_setting($pdo, 'gym_logo', '');
$themeMode = $_COOKIE['theme_mode'] ?? get_setting($pdo, 'theme_mode', 'light');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($themeMode); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?><?php echo htmlspecialchars($gymName); ?></title>

<!-- Bootstrap 5 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<!-- DataTables + Bootstrap 5 -->
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<!-- Custom Theme -->
<link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
<script>
  const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
  window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
</script>
</head>
<body>
<div class="app-wrapper">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="app-content">
  <nav class="topbar d-flex align-items-center justify-content-between px-4">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-icon d-lg-none" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
      <h5 class="mb-0 fw-600 text-white"><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-white-50 small d-none d-md-inline"><?php echo date('l, d M Y'); ?></span>

      <button class="btn btn-sm btn-icon" id="themeToggle" title="Toggle dark / light mode">
        <i class="fa-solid <?php echo $themeMode === 'dark' ? 'fa-sun' : 'fa-moon'; ?>" id="themeToggleIcon"></i>
      </button>

      <div class="dropdown">
        <button class="btn btn-sm btn-icon position-relative" data-bs-toggle="dropdown" title="Notifications">
          <i class="fa-solid fa-bell"></i>
          <span class="notif-dot" id="notifDot" style="display:none;"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-end notif-dropdown p-2" id="notifDropdown">
          <div class="text-center text-muted small py-3">Loading…</div>
        </div>
      </div>

      <div class="dropdown">
        <button class="btn btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <div class="admin-avatar"><i class="fa-solid fa-user-shield"></i></div>
          <span class="text-white small d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/settings/index.php"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
          <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/settings/activity_logs.php"><i class="fa-solid fa-clock-rotate-left me-2"></i>Activity Logs</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <main class="p-4">
