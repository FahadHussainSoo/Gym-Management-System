<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

$id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$full_name  = trim($_POST['full_name'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$join_date  = trim($_POST['join_date'] ?? '');

if ($full_name === '' || $phone === '' || $join_date === '') {
    respond('error', 'Full name, phone and join date are required.');
}

$gender      = $_POST['gender'] ?? 'Male';
$dob         = $_POST['dob'] ?: null;
$email       = trim($_POST['email'] ?? '') ?: null;
$address     = trim($_POST['address'] ?? '') ?: null;
$plan_id     = $_POST['membership_plan_id'] ?: null;
$expiry_date = $_POST['expiry_date'] ?: null;
$status      = $_POST['status'] ?? 'Active';

try {
    $pdo->beginTransaction();

    // Handle profile picture upload (optional)
    $profile_picture = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed. Please try a different file.');
        }
        if ($_FILES['profile_picture']['size'] > 3 * 1024 * 1024) {
            throw new RuntimeException('Image must be under 3MB.');
        }
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $realMime = mime_content_type($_FILES['profile_picture']['tmp_name']); // sniffs actual file content, not just the extension
        if ($realMime === false) {
            throw new RuntimeException('Could not read the uploaded image. Make sure the PHP "fileinfo" extension is enabled in php.ini.');
        }
        if (!isset($mimeMap[$realMime])) {
            throw new RuntimeException('Invalid image. Allowed: jpg, jpeg, png, webp.');
        }
        $ext = $mimeMap[$realMime];
        $filename = 'member_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], UPLOAD_DIR . $filename);
        $profile_picture = 'uploads/profile_pictures/' . $filename;
    }

    if ($id > 0) {
        // ===== UPDATE =====
        $sql = "UPDATE members SET full_name=:full_name, gender=:gender, dob=:dob, email=:email,
                phone=:phone, address=:address, membership_plan_id=:plan_id, join_date=:join_date,
                expiry_date=:expiry_date, status=:status" .
                ($profile_picture ? ", profile_picture=:photo" : "") . "
                WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $params = [
            ':full_name' => $full_name, ':gender' => $gender, ':dob' => $dob, ':email' => $email,
            ':phone' => $phone, ':address' => $address, ':plan_id' => $plan_id, ':join_date' => $join_date,
            ':expiry_date' => $expiry_date, ':status' => $status, ':id' => $id,
        ];
        if ($profile_picture) $params[':photo'] = $profile_picture;
        $stmt->execute($params);

        $pdo->prepare("INSERT INTO membership_history (member_id, plan_id, action, start_date, end_date, remarks)
                        VALUES (?, ?, 'Status Changed', ?, ?, 'Member details updated')")
            ->execute([$id, $plan_id, $join_date, $expiry_date]);

        $message = 'Member updated successfully.';
    } else {
        // ===== INSERT =====
        $codeRow = $pdo->query("SELECT MAX(id) max_id FROM members")->fetch();
        $nextId  = ($codeRow['max_id'] ?? 0) + 1;
        $member_code = 'GYM' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO members
            (member_code, full_name, gender, dob, email, phone, address, profile_picture,
             membership_plan_id, join_date, expiry_date, status)
            VALUES (:code, :full_name, :gender, :dob, :email, :phone, :address, :photo,
             :plan_id, :join_date, :expiry_date, :status)");
        $stmt->execute([
            ':code' => $member_code, ':full_name' => $full_name, ':gender' => $gender, ':dob' => $dob,
            ':email' => $email, ':phone' => $phone, ':address' => $address,
            ':photo' => $profile_picture ?: 'assets/img/default-avatar.png',
            ':plan_id' => $plan_id, ':join_date' => $join_date, ':expiry_date' => $expiry_date, ':status' => $status,
        ]);
        $id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO membership_history (member_id, plan_id, action, start_date, end_date, remarks)
                        VALUES (?, ?, 'Joined', ?, ?, 'New member registered')")
            ->execute([$id, $plan_id, $join_date, $expiry_date]);

        $message = 'Member added successfully.';
    }

    // ===== Medical Info (upsert) =====
    $medFields = ['blood_group','height_cm','weight_kg','medical_conditions','allergies','medications','doctor_name','doctor_phone'];
    if (array_filter(array_map(fn($f) => trim($_POST[$f] ?? ''), $medFields)) || trim($_POST['med_notes'] ?? '') !== '') {
        $exists = $pdo->prepare("SELECT id FROM member_medical_info WHERE member_id=?");
        $exists->execute([$id]);
        $medData = [
            ':member_id' => $id,
            ':blood_group' => $_POST['blood_group'] ?: null,
            ':height_cm' => $_POST['height_cm'] ?: null,
            ':weight_kg' => $_POST['weight_kg'] ?: null,
            ':medical_conditions' => $_POST['medical_conditions'] ?: null,
            ':allergies' => $_POST['allergies'] ?: null,
            ':medications' => $_POST['medications'] ?: null,
            ':doctor_name' => $_POST['doctor_name'] ?: null,
            ':doctor_phone' => $_POST['doctor_phone'] ?: null,
            ':notes' => $_POST['med_notes'] ?: null,
        ];
        if ($row = $exists->fetch()) {
            $pdo->prepare("UPDATE member_medical_info SET blood_group=:blood_group, height_cm=:height_cm,
                weight_kg=:weight_kg, medical_conditions=:medical_conditions, allergies=:allergies,
                medications=:medications, doctor_name=:doctor_name, doctor_phone=:doctor_phone, notes=:notes
                WHERE member_id=:member_id")->execute($medData);
        } else {
            $pdo->prepare("INSERT INTO member_medical_info
                (member_id, blood_group, height_cm, weight_kg, medical_conditions, allergies, medications, doctor_name, doctor_phone, notes)
                VALUES (:member_id, :blood_group, :height_cm, :weight_kg, :medical_conditions, :allergies, :medications, :doctor_name, :doctor_phone, :notes)")
                ->execute($medData);
        }
    }

    // ===== Emergency Contact (upsert) =====
    $contact_name = trim($_POST['contact_name'] ?? '');
    $ec_phone     = trim($_POST['ec_phone'] ?? '');
    if ($contact_name !== '' && $ec_phone !== '') {
        $exists = $pdo->prepare("SELECT id FROM member_emergency_contacts WHERE member_id=?");
        $exists->execute([$id]);
        $ecData = [
            ':member_id' => $id,
            ':contact_name' => $contact_name,
            ':relationship' => $_POST['relationship'] ?: null,
            ':phone' => $ec_phone,
            ':alt_phone' => $_POST['ec_alt_phone'] ?: null,
            ':address' => $_POST['ec_address'] ?: null,
        ];
        if ($row = $exists->fetch()) {
            $pdo->prepare("UPDATE member_emergency_contacts SET contact_name=:contact_name, relationship=:relationship,
                phone=:phone, alt_phone=:alt_phone, address=:address WHERE member_id=:member_id")->execute($ecData);
        } else {
            $pdo->prepare("INSERT INTO member_emergency_contacts
                (member_id, contact_name, relationship, phone, alt_phone, address)
                VALUES (:member_id, :contact_name, :relationship, :phone, :alt_phone, :address)")->execute($ecData);
        }
    }

    $pdo->commit();
    respond('success', $message, ['id' => $id]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('members/save.php error: ' . $e->getMessage());
    respond('error', 'Something went wrong while saving this member: ' . $e->getMessage());
}
