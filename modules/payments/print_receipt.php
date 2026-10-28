<?php
require_once __DIR__ . '/../../config/session.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, m.full_name AS member_name, m.member_code, m.phone,
           pl.plan_name
    FROM payments p
    JOIN members m ON m.id = p.member_id
    LEFT JOIN membership_plans pl ON pl.id = p.plan_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$payment = $stmt->fetch();
if (!$payment) die('Payment record not found.');

$gymName    = get_setting($pdo, 'gym_name', 'Forge Athletic');
$gymAddress = get_setting($pdo, 'gym_address', '');
$gymPhone   = get_setting($pdo, 'gym_phone', '');

$statusColor = ['Paid' => '#10B981', 'Pending' => '#F59E0B', 'Overdue' => '#EF4444'][$payment['payment_status']] ?? '#6b7280';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt #<?php echo str_pad($payment['id'], 5, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($gymName); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body{ font-family:'Poppins',sans-serif; background:#eee; margin:0; padding:24px; color:#1f2330; }
  .receipt{ max-width:600px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.12); }
  .receipt-header{ background:linear-gradient(135deg,#4F46E5,#8B5CF6); color:#fff; padding:24px; }
  .receipt-header h2{ margin:0 0 4px; font-weight:700; }
  .receipt-header p{ margin:0; opacity:.9; font-size:13px; }
  .receipt-body{ padding:24px; }
  .meta-row{ display:flex; justify-content:space-between; margin-bottom:18px; font-size:13px; color:#555; }
  .status-badge{ display:inline-block; padding:.35em .9em; border-radius:20px; color:#fff; font-weight:600; font-size:12px; background:<?php echo $statusColor; ?>; }
  table{ width:100%; border-collapse:collapse; margin-top:6px; }
  th{ background:#F5F7FB; text-align:left; padding:8px 10px; font-size:12px; color:#555; }
  td{ padding:8px 10px; border-bottom:1px solid #eee; font-size:13px; }
  .total-row td{ font-weight:700; font-size:15px; border-top:2px solid #4F46E5; border-bottom:none; }
  .footer-note{ text-align:center; margin-top:20px; font-size:12px; color:#888; }
  .print-btn{ text-align:center; margin-top:16px; }
  .print-btn button{ background:linear-gradient(135deg,#4F46E5,#8B5CF6); color:#fff; border:none; padding:10px 22px; border-radius:8px; font-weight:600; cursor:pointer; }
  @media print{ body{ background:#fff; padding:0; } .receipt{ box-shadow:none; } .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="receipt">
    <div class="receipt-header">
      <h2><?php echo htmlspecialchars($gymName); ?></h2>
      <p><?php echo htmlspecialchars($gymAddress); ?><?php echo $gymPhone ? ' &nbsp;|&nbsp; ' . htmlspecialchars($gymPhone) : ''; ?></p>
    </div>
    <div class="receipt-body">
      <div class="meta-row">
        <div>
          <strong>Receipt #<?php echo str_pad($payment['id'], 5, '0', STR_PAD_LEFT); ?></strong><br>
          Date: <?php echo htmlspecialchars($payment['payment_date']); ?>
        </div>
        <div class="text-end">
          <?php echo htmlspecialchars($payment['member_name']); ?> (<?php echo htmlspecialchars($payment['member_code']); ?>)<br>
          <span class="status-badge"><?php echo htmlspecialchars($payment['payment_status']); ?></span>
        </div>
      </div>
      <table>
        <thead><tr><th>Description</th><th>Method</th><th>Amount</th></tr></thead>
        <tbody>
          <tr>
            <td><?php echo $payment['plan_name'] ? htmlspecialchars($payment['plan_name']) . ' Membership Fee' : 'Membership Payment'; ?></td>
            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
            <td>Rs <?php echo number_format($payment['amount'], 2); ?></td>
          </tr>
          <tr class="total-row">
            <td colspan="2">Total</td>
            <td>Rs <?php echo number_format($payment['amount'], 2); ?></td>
          </tr>
        </tbody>
      </table>
      <?php if (!empty($payment['notes'])): ?>
        <p class="mt-3 small text-muted">Notes: <?php echo htmlspecialchars($payment['notes']); ?></p>
      <?php endif; ?>
      <p class="footer-note">Thank you for training with <?php echo htmlspecialchars($gymName); ?>!</p>
    </div>
  </div>
  <div class="print-btn no-print">
    <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print Receipt</button>
  </div>
</body>
</html>
