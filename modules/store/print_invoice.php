<?php
require_once __DIR__ . '/../../config/session.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT s.*, pr.name AS product_name, m.full_name AS member_name, m.member_code, m.phone
    FROM product_sales s
    JOIN products pr ON pr.id = s.product_id
    LEFT JOIN members m ON m.id = s.member_id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) die('Sale record not found.');

$gymName    = get_setting($pdo, 'gym_name', 'Forge Athletic');
$gymAddress = get_setting($pdo, 'gym_address', '');
$gymPhone   = get_setting($pdo, 'gym_phone', '');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice #<?php echo str_pad($sale['id'], 5, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($gymName); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body{ font-family:'Poppins',sans-serif; background:#eee; margin:0; padding:24px; color:#1f2330; }
  .invoice{ max-width:600px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.12); }
  .invoice-header{ background:linear-gradient(135deg,#4F46E5,#06B6D4); color:#fff; padding:24px; }
  .invoice-header h2{ margin:0 0 4px; font-weight:700; }
  .invoice-header p{ margin:0; opacity:.9; font-size:13px; }
  .invoice-body{ padding:24px; }
  .meta-row{ display:flex; justify-content:space-between; margin-bottom:18px; font-size:13px; color:#555; }
  table{ width:100%; border-collapse:collapse; }
  th{ background:#F5F7FB; text-align:left; padding:8px 10px; font-size:12px; color:#555; }
  td{ padding:8px 10px; border-bottom:1px solid #eee; font-size:13px; }
  .total-row td{ font-weight:700; font-size:15px; border-top:2px solid #4F46E5; border-bottom:none; }
  .footer-note{ text-align:center; margin-top:20px; font-size:12px; color:#888; }
  .print-btn{ text-align:center; margin-top:16px; }
  .print-btn button{ background:linear-gradient(135deg,#4F46E5,#8B5CF6); color:#fff; border:none; padding:10px 22px; border-radius:8px; font-weight:600; cursor:pointer; }
  @media print{ body{ background:#fff; padding:0; } .invoice{ box-shadow:none; } .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="invoice">
    <div class="invoice-header">
      <h2><?php echo htmlspecialchars($gymName); ?></h2>
      <p><?php echo htmlspecialchars($gymAddress); ?><?php echo $gymPhone ? ' &nbsp;|&nbsp; ' . htmlspecialchars($gymPhone) : ''; ?></p>
    </div>
    <div class="invoice-body">
      <div class="meta-row">
        <div>
          <strong>Invoice #<?php echo str_pad($sale['id'], 5, '0', STR_PAD_LEFT); ?></strong><br>
          Date: <?php echo htmlspecialchars($sale['sale_date']); ?>
        </div>
        <div class="text-end">
          Buyer: <?php echo $sale['member_name'] ? htmlspecialchars($sale['member_name']) . ' (' . htmlspecialchars($sale['member_code']) . ')' : 'Walk-in Customer'; ?>
        </div>
      </div>
      <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
          <tr>
            <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
            <td><?php echo (int)$sale['quantity']; ?></td>
            <td>Rs <?php echo number_format($sale['unit_price'], 2); ?></td>
            <td>Rs <?php echo number_format($sale['total'], 2); ?></td>
          </tr>
          <tr class="total-row">
            <td colspan="3">Grand Total</td>
            <td>Rs <?php echo number_format($sale['total'], 2); ?></td>
          </tr>
        </tbody>
      </table>
      <p class="footer-note">Thank you for shopping with <?php echo htmlspecialchars($gymName); ?>!</p>
    </div>
  </div>
  <div class="print-btn no-print">
    <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print Invoice</button>
  </div>
</body>
</html>
