<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
header('Content-Type: application/json');

$draw    = (int)($_POST['draw'] ?? 1);
$start   = (int)($_POST['start'] ?? 0);
$length  = (int)($_POST['length'] ?? 10);
$search  = trim($_POST['search']['value'] ?? '');

$baseSql = "FROM trainers t";
$where   = "";
$params  = [];

if ($search !== '') {
    $where = " WHERE t.full_name LIKE :s OR t.trainer_code LIKE :s OR t.phone LIKE :s OR t.specialization LIKE :s";
    $params[':s'] = "%$search%";
}

$totalRecords = $pdo->query("SELECT COUNT(*) c $baseSql")->fetch()['c'];

$filteredStmt = $pdo->prepare("SELECT COUNT(*) c $baseSql $where");
$filteredStmt->execute($params);
$filteredRecords = $filteredStmt->fetch()['c'];

$sql = "SELECT t.id, t.trainer_code, t.full_name, t.phone, t.profile_picture,
               t.specialization, t.experience_years, t.monthly_salary, t.status
        $baseSql $where
        ORDER BY t.id DESC
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
        <a href="' . BASE_URL . 'modules/trainers/profile.php?id=' . $r['id'] . '" class="btn btn-sm btn-outline-gradient" title="View"><i class="fa-solid fa-eye"></i></a>
        <button class="btn btn-sm btn-outline-gradient" title="Edit" onclick="editTrainer(' . $r['id'] . ')"><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-sm btn-outline-gradient" title="Toggle Status" onclick="toggleTrainerStatus(' . $r['id'] . ', \'' . $r['status'] . '\')"><i class="fa-solid fa-toggle-on"></i></button>
        <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteTrainer(' . $r['id'] . ')"><i class="fa-solid fa-trash"></i></button>
      </div>';

    $data[] = [
        '<img src="' . $photo . '" class="member-thumb">',
        htmlspecialchars($r['trainer_code']),
        htmlspecialchars($r['full_name']),
        htmlspecialchars($r['phone']),
        htmlspecialchars($r['specialization'] ?: '—'),
        htmlspecialchars($r['experience_years']) . ' yrs',
        'Rs ' . number_format($r['monthly_salary'], 2),
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
