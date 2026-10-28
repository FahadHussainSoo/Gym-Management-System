<?php
require_once __DIR__ . '/../config/session.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;

$stmt = $pdo->prepare("SELECT id, reset_expires FROM admins WHERE reset_token = ?");
$stmt->execute([$token]);
$admin = $stmt->fetch();

$valid = $admin && strtotime($admin['reset_expires']) > time();

if (!$valid) {
    $error = 'This reset link is invalid or has expired. Request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $upd->execute([$hash, $admin['id']]);
        log_activity($pdo, 'password_reset', "Admin #{$admin['id']} reset their password");
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body{ font-family:'Poppins',sans-serif; min-height:100vh; background:linear-gradient(135deg,#4F46E5,#8B5CF6 55%,#06B6D4); display:flex; align-items:center; justify-content:center; margin:0; padding:20px; }
  .auth-card{ width:100%; max-width:420px; background:rgba(255,255,255,.94); border-radius:20px; padding:2.2rem 2rem; box-shadow:0 20px 60px rgba(17,24,39,.35); animation:authCardIn .5s cubic-bezier(.2,.8,.2,1) both; }
  @keyframes authCardIn{ from{ opacity:0; transform:translateY(18px) scale(.98); } to{ opacity:1; transform:translateY(0) scale(1); } }
  .form-control{ border-radius:10px; }
  .btn-primary{ background:linear-gradient(90deg,#4F46E5,#8B5CF6); border:none; border-radius:10px; }
</style>
</head>
<body>
  <div class="auth-card">
    <h5 class="fw-700 mb-3">Set a new password</h5>

    <?php if ($error): ?><div class="alert alert-danger small"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success small">Password updated. You can now <a href="login.php">sign in</a>.</div>
    <?php elseif ($valid): ?>
      <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="mb-3">
          <label class="form-label small fw-600">New Password</label>
          <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Confirm Password</label>
          <input type="password" name="confirm" class="form-control" required minlength="8">
        </div>
        <button class="btn btn-primary w-100">Update Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
