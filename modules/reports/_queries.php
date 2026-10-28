<?php
/**
 * Returns a normalized report payload for a given report type + date range.
 * Shared by: ajax/reports/data.php, modules/reports/export_excel.php, modules/reports/export_pdf.php
 *
 * Shape: [
 *   'title'   => string,
 *   'summary' => [ ['label' => ..., 'value' => ...], ... ],
 *   'columns' => [ 'Col A', 'Col B', ... ],
 *   'rows'    => [ [ ... ], ... ],
 *   'chart'   => [ 'labels' => [...], 'data' => [...] ],
 * ]
 */
function get_report_data(PDO $pdo, string $type, string $from, string $to): array {
    switch ($type) {

        case 'members':
            $rows = $pdo->prepare("
                SELECT m.member_code, m.full_name, m.phone, p.plan_name, m.join_date, m.expiry_date, m.status
                FROM members m LEFT JOIN membership_plans p ON p.id = m.membership_plan_id
                WHERE m.join_date BETWEEN ? AND ?
                ORDER BY m.join_date DESC
            ");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $byPlan = $pdo->query("SELECT COALESCE(p.plan_name,'No Plan') label, COUNT(*) c FROM members m LEFT JOIN membership_plans p ON p.id=m.membership_plan_id GROUP BY label")->fetchAll();

            return [
                'title'   => 'Member Report',
                'summary' => [
                    ['label' => 'New Members in Range', 'value' => count($data)],
                    ['label' => 'Total Active Members', 'value' => $pdo->query("SELECT COUNT(*) c FROM members WHERE status='Active'")->fetch()['c']],
                ],
                'columns' => ['Code', 'Name', 'Phone', 'Plan', 'Joined', 'Expiry', 'Status'],
                'rows'    => array_map(fn($r) => [$r['member_code'], $r['full_name'], $r['phone'], $r['plan_name'] ?: '—', $r['join_date'], $r['expiry_date'] ?: '—', $r['status']], $data),
                'chart'   => ['labels' => array_column($byPlan, 'label'), 'data' => array_map('intval', array_column($byPlan, 'c'))],
            ];

        case 'trainers':
            $rows = $pdo->prepare("
                SELECT trainer_code, full_name, phone, specialization, experience_years, monthly_salary, status
                FROM trainers WHERE joining_date BETWEEN ? AND ? ORDER BY joining_date DESC
            ");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $bySpec = $pdo->query("SELECT COALESCE(specialization,'General') label, COUNT(*) c FROM trainers GROUP BY label")->fetchAll();

            return [
                'title'   => 'Trainer Report',
                'summary' => [
                    ['label' => 'Trainers Joined in Range', 'value' => count($data)],
                    ['label' => 'Total Active Trainers', 'value' => $pdo->query("SELECT COUNT(*) c FROM trainers WHERE status='Active'")->fetch()['c']],
                ],
                'columns' => ['Code', 'Name', 'Phone', 'Specialization', 'Experience (yrs)', 'Monthly Salary', 'Status'],
                'rows'    => array_map(fn($r) => [$r['trainer_code'], $r['full_name'], $r['phone'], $r['specialization'] ?: '—', $r['experience_years'], number_format($r['monthly_salary'], 2), $r['status']], $data),
                'chart'   => ['labels' => array_column($bySpec, 'label'), 'data' => array_map('intval', array_column($bySpec, 'c'))],
            ];

        case 'attendance':
            $rows = $pdo->prepare("
                SELECT m.member_code, m.full_name, a.check_in, a.check_out
                FROM attendance a JOIN members m ON m.id = a.member_id
                WHERE DATE(a.check_in) BETWEEN ? AND ?
                ORDER BY a.check_in DESC
            ");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $byDay = $pdo->prepare("
                SELECT DATE(check_in) d, COUNT(*) c FROM attendance
                WHERE DATE(check_in) BETWEEN ? AND ? GROUP BY d ORDER BY d
            ");
            $byDay->execute([$from, $to]);
            $trend = $byDay->fetchAll();

            return [
                'title'   => 'Attendance Report',
                'summary' => [
                    ['label' => 'Total Check-ins in Range', 'value' => count($data)],
                    ['label' => 'Unique Members', 'value' => count(array_unique(array_column($data, 'member_code')))],
                ],
                'columns' => ['Code', 'Name', 'Check-in', 'Check-out'],
                'rows'    => array_map(fn($r) => [$r['member_code'], $r['full_name'], $r['check_in'], $r['check_out'] ?: '—'], $data),
                'chart'   => ['labels' => array_column($trend, 'd'), 'data' => array_map('intval', array_column($trend, 'c'))],
            ];

        case 'revenue':
            $rows = $pdo->prepare("
                SELECT p.payment_date, m.member_code, m.full_name, p.amount, p.payment_method, p.payment_status
                FROM payments p JOIN members m ON m.id = p.member_id
                WHERE p.payment_date BETWEEN ? AND ? ORDER BY p.payment_date DESC
            ");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $trend = $pdo->prepare("
                SELECT DATE_FORMAT(payment_date,'%Y-%m-%d') d, SUM(amount) s FROM payments
                WHERE payment_status='Paid' AND payment_date BETWEEN ? AND ? GROUP BY d ORDER BY d
            ");
            $trend->execute([$from, $to]);
            $trendData = $trend->fetchAll();

            $total = array_sum(array_map(fn($r) => $r['payment_status'] === 'Paid' ? (float)$r['amount'] : 0, $data));

            return [
                'title'   => 'Revenue Report',
                'summary' => [
                    ['label' => 'Total Revenue (Paid)', 'value' => 'Rs ' . number_format($total, 2)],
                    ['label' => 'Transactions', 'value' => count($data)],
                ],
                'columns' => ['Date', 'Member Code', 'Member', 'Amount', 'Method', 'Status'],
                'rows'    => array_map(fn($r) => [$r['payment_date'], $r['member_code'], $r['full_name'], number_format($r['amount'], 2), $r['payment_method'], $r['payment_status']], $data),
                'chart'   => ['labels' => array_column($trendData, 'd'), 'data' => array_map('floatval', array_column($trendData, 's'))],
            ];

        case 'membership':
            $rows = $pdo->prepare("
                SELECT h.created_at, m.member_code, m.full_name, h.action, p.plan_name, h.start_date, h.end_date
                FROM membership_history h
                JOIN members m ON m.id = h.member_id
                LEFT JOIN membership_plans p ON p.id = h.plan_id
                WHERE DATE(h.created_at) BETWEEN ? AND ?
                ORDER BY h.created_at DESC
            ");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $byAction = $pdo->prepare("SELECT action label, COUNT(*) c FROM membership_history WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY action");
            $byAction->execute([$from, $to]);
            $actionMix = $byAction->fetchAll();

            return [
                'title'   => 'Membership Report',
                'summary' => [
                    ['label' => 'Membership Events in Range', 'value' => count($data)],
                ],
                'columns' => ['Date', 'Member Code', 'Member', 'Action', 'Plan', 'Start', 'End'],
                'rows'    => array_map(fn($r) => [$r['created_at'], $r['member_code'], $r['full_name'], $r['action'], $r['plan_name'] ?: '—', $r['start_date'] ?: '—', $r['end_date'] ?: '—'], $data),
                'chart'   => ['labels' => array_column($actionMix, 'label'), 'data' => array_map('intval', array_column($actionMix, 'c'))],
            ];

        case 'product_sales':
            $rows = $pdo->prepare("
                SELECT s.sale_date, pr.name, s.quantity, s.unit_price, s.total, m.full_name
                FROM product_sales s JOIN products pr ON pr.id = s.product_id
                LEFT JOIN members m ON m.id = s.member_id
                WHERE s.sale_date BETWEEN ? AND ? ORDER BY s.sale_date DESC
            ");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $byProduct = $pdo->prepare("
                SELECT pr.name label, SUM(s.total) c FROM product_sales s JOIN products pr ON pr.id = s.product_id
                WHERE s.sale_date BETWEEN ? AND ? GROUP BY pr.name
            ");
            $byProduct->execute([$from, $to]);
            $productMix = $byProduct->fetchAll();

            $total = array_sum(array_column($data, 'total'));

            return [
                'title'   => 'Product Sales Report',
                'summary' => [
                    ['label' => 'Total Sales Revenue', 'value' => 'Rs ' . number_format($total, 2)],
                    ['label' => 'Items Sold', 'value' => array_sum(array_column($data, 'quantity'))],
                ],
                'columns' => ['Date', 'Product', 'Qty', 'Unit Price', 'Total', 'Buyer'],
                'rows'    => array_map(fn($r) => [$r['sale_date'], $r['name'], $r['quantity'], number_format($r['unit_price'], 2), number_format($r['total'], 2), $r['full_name'] ?: 'Walk-in'], $data),
                'chart'   => ['labels' => array_column($productMix, 'label'), 'data' => array_map('floatval', array_column($productMix, 'c'))],
            ];

        case 'expenses':
            $rows = $pdo->prepare("SELECT expense_date, category, description, amount FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC");
            $rows->execute([$from, $to]);
            $data = $rows->fetchAll();

            $byCategory = $pdo->prepare("SELECT category label, SUM(amount) c FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY category");
            $byCategory->execute([$from, $to]);
            $catMix = $byCategory->fetchAll();

            $total = array_sum(array_column($data, 'amount'));

            return [
                'title'   => 'Expense Report',
                'summary' => [
                    ['label' => 'Total Expenses', 'value' => 'Rs ' . number_format($total, 2)],
                    ['label' => 'Entries', 'value' => count($data)],
                ],
                'columns' => ['Date', 'Category', 'Description', 'Amount'],
                'rows'    => array_map(fn($r) => [$r['expense_date'], $r['category'], $r['description'] ?: '—', number_format($r['amount'], 2)], $data),
                'chart'   => ['labels' => array_column($catMix, 'label'), 'data' => array_map('floatval', array_column($catMix, 'c'))],
            ];

        default:
            return ['title' => 'Unknown Report', 'summary' => [], 'columns' => [], 'rows' => [], 'chart' => ['labels' => [], 'data' => []]];
    }
}

/** Validates & normalizes the ?type=, ?from=, ?to= query params shared by all report entry points. */
function resolve_report_params(): array {
    $allowed = ['members', 'trainers', 'attendance', 'revenue', 'membership', 'product_sales', 'expenses'];
    $type = in_array($_GET['type'] ?? '', $allowed, true) ? $_GET['type'] : 'members';
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to'] ?? date('Y-m-d');
    // Basic sanity check on date format; fall back to sensible defaults if malformed.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
    return [$type, $from, $to];
}
