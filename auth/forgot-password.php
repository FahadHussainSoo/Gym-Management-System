<?php
require_once __DIR__ . '/../config/session.php';

$message = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // Always show the same message whether or not the email exists (avoid account enumeration).
    $message = "If an account exists for that email, a reset link has been generated.";

    if ($admin) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $upd = $pdo->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $upd->execute([$token, $expires, $admin['id']]);
        log_activity($pdo, 'password_reset_requested', "Reset requested for admin #{$admin['id']}");

        $resetLink = BASE_URL . 'auth/reset-password.php?token=' . $token;
        // NOTE: A real deployment should email $resetLink via the SMTP settings configured
        // under Settings > Email Settings, rather than displaying it on screen. XAMPP's
        // default PHP mail() function does not send mail without extra local mail server
        // configuration, so this demo surfaces the link directly instead of pretending
        // an email always goes out.
    }
}

$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | <?php echo htmlspecialchars($gymName); ?></title>
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
    <h5 class="fw-700 mb-3">Reset your password</h5>
    <?php if ($message): ?>
      <div class="alert alert-info small"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($resetLink): ?>
      <div class="alert alert-warning small">
        Demo mode (no SMTP configured yet): <a href="<?php echo htmlspecialchars($resetLink); ?>"><?php echo htmlspecialchars($resetLink); ?></a>
      </div>
    <?php endif; ?>
    <form method="POST">
      <?php echo csrf_field(); ?>
      <div class="mb-3">
        <label class="form-label small fw-600">Account Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100">Send Reset Link</button>
      <div class="text-center small mt-3"><a href="login.php">&larr; Back to login</a></div>
    </form>
  </div>
</body>
</html>
