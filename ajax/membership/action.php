<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

$mode = $_POST['mode'] ?? '';
$memberId = (int)($_POST['member_id'] ?? 0);

if (!$memberId) {
    echo json_encode(['success' => false, 'message' => 'Missing member.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) {
    echo json_encode(['success' => false, 'message' => 'Member not found.']);
    exit;
}

try {
    switch ($mode) {

        case 'renew':
        case 'change': {
            $planId = (int)($_POST['plan_id'] ?? 0);
            $startDate = $_POST['start_date'] ?? date('Y-m-d');
            $amount = (float)($_POST['amount_paid'] ?? 0);
            $method = $_POST['payment_method'] ?? 'Cash';

            $planStmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
            $planStmt->execute([$planId]);
            $plan = $planStmt->fetch();
            if (!$plan) {
                echo json_encode(['success' => false, 'message' => 'Invalid plan selected.']);
                exit;
            }

            $endDate = date('Y-m-d', strtotime($startDate . " +{$plan['duration_months']} months"));
            $action = $mode === 'renew' ? 'Renewed' : (($plan['price'] > 0 && $member['membership_plan_id'] && $plan['id'] != $member['membership_plan_id']) ? 'Upgraded' : 'Status Changed');

            $pdo->beginTransaction();

            $upd = $pdo->prepare("UPDATE members SET membership_plan_id = ?, expiry_date = ?, status = 'Active' WHERE id = ?");
            $upd->execute([$planId, $endDate, $memberId]);

            $hist = $pdo->prepare("INSERT INTO membership_history (member_id, plan_id, action, start_date, end_date, remarks) VALUES (?,?,?,?,?,?)");
            $hist->execute([$memberId, $planId, $action, $startDate, $endDate, ucfirst($mode) . ' via Membership module']);

            if ($amount > 0) {
                $pay = $pdo->prepare("INSERT INTO payments (member_id, plan_id, amount, payment_method, payment_status, payment_date) VALUES (?,?,?,?,?,?)");
                $pay->execute([$memberId, $planId, $amount, $method, 'Paid', $startDate]);
            }

            $pdo->commit();
            log_activity($pdo, 'membership_' . $mode, "{$member['full_name']} — {$plan['plan_name']} until $endDate");
            echo json_encode(['success' => true, 'message' => $mode === 'renew' ? 'Membership renewed.' : 'Plan changed.']);
            break;
        }

        case 'freeze': {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE members SET status = 'Inactive' WHERE id = ?")->execute([$memberId]);
            $pdo->prepare("INSERT INTO membership_history (member_id, action, remarks) VALUES (?, 'Frozen', 'Frozen via Membership module')")->execute([$memberId]);
            $pdo->commit();
            log_activity($pdo, 'membership_freeze', "{$member['full_name']} membership frozen");
            echo json_encode(['success' => true, 'message' => 'Membership frozen.']);
            break;
        }

        case 'unfreeze': {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = ?")->execute([$memberId]);
            $pdo->prepare("INSERT INTO membership_history (member_id, action, remarks) VALUES (?, 'Status Changed', 'Reactivated via Membership module')")->execute([$memberId]);
            $pdo->commit();
            log_activity($pdo, 'membership_unfreeze', "{$member['full_name']} membership reactivated");
            echo json_encode(['success' => true, 'message' => 'Membership reactivated.']);
            break;
        }

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('membership/action.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
