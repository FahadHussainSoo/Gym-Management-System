<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT m.*, p.plan_name FROM members m
                        LEFT JOIN membership_plans p ON p.id = m.membership_plan_id
                        WHERE m.id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) die('Member not found.');
$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Member Card - <?php echo htmlspecialchars($member['full_name']); ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="../../assets/css/style.css" rel="stylesheet">
<style>
  body{ background:#eee; display:flex; align-items:center; justify-content:center; min-height:100vh; font-family:'Poppins',sans-serif;}
</style>
</head>
<body>
  <div class="id-card">
    <div class="id-card-header">
      <i class="fa-solid fa-dumbbell"></i>
      <b><?php echo htmlspecialchars($gymName); ?></b>
    </div>
    <div class="id-card-body">
      <img src="<?php echo '../../' . htmlspecialchars($member['profile_picture']); ?>">
      <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($member['full_name']); ?></h5>
      <p class="mb-1 small opacity-75"><?php echo htmlspecialchars($member['member_code']); ?></p>
      <p class="mb-1 small">Plan: <?php echo htmlspecialchars($member['plan_name'] ?: '—'); ?></p>
      <p class="mb-1 small">Valid Until: <?php echo htmlspecialchars($member['expiry_date'] ?: '—'); ?></p>
      <p class="mb-0 small">Phone: <?php echo htmlspecialchars($member['phone']); ?></p>
    </div>
  </div>
  <div class="text-center mt-3 no-print">
    <button class="btn btn-gradient" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
  </div>
</body>
</html>
