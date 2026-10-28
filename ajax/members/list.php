<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$draw    = (int)($_POST['draw'] ?? 1);
$start   = (int)($_POST['start'] ?? 0);
$length  = (int)($_POST['length'] ?? 10);
$search  = trim($_POST['search']['value'] ?? '');

$baseSql = "FROM members m LEFT JOIN membership_plans p ON p.id = m.membership_plan_id";
$where   = "";
$params  = [];

if ($search !== '') {
    $where = " WHERE m.full_name LIKE :s OR m.member_code LIKE :s OR m.phone LIKE :s OR m.email LIKE :s";
    $params[':s'] = "%$search%";
}

$totalRecords = $pdo->query("SELECT COUNT(*) c $baseSql")->fetch()['c'];

$filteredStmt = $pdo->prepare("SELECT COUNT(*) c $baseSql $where");
$filteredStmt->execute($params);
$filteredRecords = $filteredStmt->fetch()['c'];

$sql = "SELECT m.id, m.member_code, m.full_name, m.phone, m.profile_picture,
               m.join_date, m.expiry_date, m.status, p.plan_name
        $baseSql $where
        ORDER BY m.id DESC
        LIMIT :start, :length";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$data = [];
foreach ($rows as $r) {
    $photo = BASE_URL . htmlspecialchars($r['profile_picture']);
    $statusBadge = $r['status'] === 'Active'
        ? '<span class="badge-active">Active</span>'
        : '<span class="badge-inactive">Inactive</span>';

    $actions = '
      <div class="d-flex gap-1">
        <a href="' . BASE_URL . 'modules/members/profile.php?id=' . $r['id'] . '" class="btn btn-sm btn-outline-gradient" title="View"><i class="fa-solid fa-eye"></i></a>
        <button class="btn btn-sm btn-outline-gradient" title="Edit" onclick="editMember(' . $r['id'] . ')"><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-sm btn-outline-gradient" title="Toggle Status" onclick="toggleStatus(' . $r['id'] . ', \'' . $r['status'] . '\')"><i class="fa-solid fa-toggle-on"></i></button>
        <a href="' . BASE_URL . 'modules/members/print_card.php?id=' . $r['id'] . '" target="_blank" class="btn btn-sm btn-outline-gradient" title="Print Card"><i class="fa-solid fa-print"></i></a>
        <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteMember(' . $r['id'] . ')"><i class="fa-solid fa-trash"></i></button>
      </div>';

    $data[] = [
        '<img src="' . $photo . '" class="member-thumb">',
        htmlspecialchars($r['member_code']),
        htmlspecialchars($r['full_name']),
        htmlspecialchars($r['phone']),
        htmlspecialchars($r['plan_name'] ?: '—'),
        htmlspecialchars($r['join_date']),
        htmlspecialchars($r['expiry_date'] ?: '—'),
        $statusBadge,
        $actions,
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $data,
]);
