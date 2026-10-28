<?php
require_once __DIR__ . '/../config/session.php';

// Already logged in? Skip straight to the dashboard.
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            $error = 'Invalid username or password.';
            log_activity($pdo, 'login_failed', "Failed login attempt for '$username'");
        } elseif ($admin['status'] !== 'Active') {
            $error = 'This account has been deactivated. Contact a super admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];

            $upd = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $upd->execute([$admin['id']]);
            log_activity($pdo, 'login', "{$admin['name']} logged in");

            $redirect = $_GET['redirect'] ?? '';
            $redirect = $redirect ? urldecode($redirect) : '';
            // Open-redirect guard: only accept a same-site relative path (single leading slash,
            // no protocol-relative "//" or "/\" trick, no header-injection newlines).
            $redirectUrl = preg_match('#^/(?!/)(?!\\\\)[^\r\n]*$#', $redirect)
                ? $redirect
                : BASE_URL . 'modules/dashboard/index.php';

            if ($isAjax) {
                header('Content-Type: application/json');
                die(json_encode(['success' => true, 'message' => "Welcome back, {$admin['name']}!", 'redirect' => $redirectUrl]));
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    // If we got here on an AJAX request, login failed - respond with JSON instead of re-rendering the page.
    if ($isAjax) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => $error]));
    }
}

$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | <?php echo htmlspecialchars($gymName); ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  body{
    font-family:'Poppins', sans-serif; min-height:100vh;
    background:linear-gradient(135deg, #4F46E5, #8B5CF6 55%, #06B6D4);
    display:flex; align-items:center; justify-content:center; margin:0; padding:20px;
  }
  .auth-card{
    width:100%; max-width:400px;
    background:rgba(255,255,255,.92); backdrop-filter:blur(14px);
    border-radius:20px; padding:2.4rem 2rem; box-shadow:0 20px 60px rgba(17,24,39,.35);
    animation:authCardIn .5s cubic-bezier(.2,.8,.2,1) both;
  }
  @keyframes authCardIn{ from{ opacity:0; transform:translateY(18px) scale(.98); } to{ opacity:1; transform:translateY(0) scale(1); } }
  .auth-brand{ text-align:center; margin-bottom:1.6rem; }
  .auth-brand i{ font-size:2.2rem; color:#4F46E5; }
  .auth-brand h4{ font-weight:700; margin:.4rem 0 0; }
  .form-control{ border-radius:10px; padding:.65rem .9rem; }
  .form-control:focus{ border-color:#4F46E5; box-shadow:0 0 0 .2rem rgba(79,70,229,.15); }
  .btn-login{
    background:linear-gradient(90deg,#4F46E5,#8B5CF6); color:#fff; border:none; border-radius:10px;
    padding:.7rem; font-weight:600; width:100%; transition:.18s;
  }
  .btn-login:hover{ filter:brightness(1.08); color:#fff; transform:translateY(-1px); }
  .btn-login:disabled{ opacity:.75; transform:none; }
  a{ color:#4F46E5; }
</style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-brand">
      <i class="fa-solid fa-dumbbell"></i>
      <h4><?php echo htmlspecialchars($gymName); ?></h4>
      <div class="text-muted small">Admin Console</div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 small no-js-only"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>
      <?php echo csrf_field(); ?>
      <div class="mb-3">
        <label class="form-label small fw-600">Username or Email</label>
        <input type="text" name="username" class="form-control" required autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label small fw-600">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-login mb-2" id="loginBtn">Sign In</button>
      <div class="text-center small"><a href="forgot-password.php">Forgot password?</a></div>
    </form>

    <div class="text-center text-muted small mt-3">
      Default: <code>admin</code> / <code>Admin@123</code> — change this after first login.
    </div>
  </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Progressive enhancement: if JS/SweetAlert2 fails to load for any reason,
  // the form above still works exactly as before via a normal POST + PHP re-render.
  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    $('.no-js-only').remove();
    const $btn = $('#loginBtn');
    const original = $btn.text();
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Signing in...');

    $.ajax({
      url: window.location.href,
      method: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function (res) {
        if (res.success) {
          Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
          setTimeout(() => { window.location.href = res.redirect; }, 900);
        } else {
          Swal.fire({ icon: 'error', title: 'Login Failed', text: res.message, confirmButtonColor: '#4F46E5' });
          $btn.prop('disabled', false).text(original);
        }
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Server Error', text: 'Please try again.', confirmButtonColor: '#4F46E5' });
        $btn.prop('disabled', false).text(original);
      }
    });
  });
</script>
</body>
</html>
