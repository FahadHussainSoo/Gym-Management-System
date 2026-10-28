<?php
require_once __DIR__ . '/../../config/session.php';
require_login_ajax();
verify_csrf();
header('Content-Type: application/json');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

$id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$full_name     = trim($_POST['full_name'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$joining_date  = trim($_POST['joining_date'] ?? '');
$monthly_salary = $_POST['monthly_salary'] ?? '';

if ($full_name === '' || $phone === '' || $joining_date === '' || $monthly_salary === '') {
    respond('error', 'Full name, phone, joining date and salary are required.');
}

$gender           = $_POST['gender'] ?? 'Male';
$dob              = $_POST['dob'] ?: null;
$email            = trim($_POST['email'] ?? '') ?: null;
$address          = trim($_POST['address'] ?? '') ?: null;
$specialization   = trim($_POST['specialization'] ?? '') ?: null;
$experience_years = $_POST['experience_years'] ?: 0;
$certification    = trim($_POST['certification'] ?? '') ?: null;
$status           = $_POST['status'] ?? 'Active';

try {
    $pdo->beginTransaction();

    // Handle profile picture upload (optional)
    $profile_picture = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $uploadDir = __DIR__ . '/../../uploads/trainer_pictures/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed. Please try a different file.');
        }
        if ($_FILES['profile_picture']['size'] > 3 * 1024 * 1024) {
            throw new RuntimeException('Image must be under 3MB.');
        }
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $realMime = mime_content_type($_FILES['profile_picture']['tmp_name']);
        if ($realMime === false) {
            throw new RuntimeException('Could not read the uploaded image. Make sure the PHP "fileinfo" extension is enabled in php.ini.');
        }
        if (!isset($mimeMap[$realMime])) {
            throw new RuntimeException('Invalid image. Allowed: jpg, jpeg, png, webp.');
        }
        $ext = $mimeMap[$realMime];
        $filename = 'trainer_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename);
        $profile_picture = 'uploads/trainer_pictures/' . $filename;
    }

    if ($id > 0) {
        // ===== UPDATE =====
        $sql = "UPDATE trainers SET full_name=:full_name, gender=:gender, dob=:dob, email=:email,
                phone=:phone, address=:address, specialization=:specialization, experience_years=:experience_years,
                certification=:certification, monthly_salary=:monthly_salary, joining_date=:joining_date, status=:status" .
                ($profile_picture ? ", profile_picture=:photo" : "") . "
                WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $params = [
            ':full_name' => $full_name, ':gender' => $gender, ':dob' => $dob, ':email' => $email,
            ':phone' => $phone, ':address' => $address, ':specialization' => $specialization,
            ':experience_years' => $experience_years, ':certification' => $certification,
            ':monthly_salary' => $monthly_salary, ':joining_date' => $joining_date, ':status' => $status, ':id' => $id,
        ];
        if ($profile_picture) $params[':photo'] = $profile_picture;
        $stmt->execute($params);

        $message = 'Trainer updated successfully.';
    } else {
        // ===== INSERT =====
        $codeRow = $pdo->query("SELECT MAX(id) max_id FROM trainers")->fetch();
        $nextId  = ($codeRow['max_id'] ?? 0) + 1;
        $trainer_code = 'TRN' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO trainers
            (trainer_code, full_name, gender, dob, email, phone, address, profile_picture,
             specialization, experience_years, certification, monthly_salary, joining_date, status)
            VALUES (:code, :full_name, :gender, :dob, :email, :phone, :address, :photo,
             :specialization, :experience_years, :certification, :monthly_salary, :joining_date, :status)");
        $stmt->execute([
            ':code' => $trainer_code, ':full_name' => $full_name, ':gender' => $gender, ':dob' => $dob,
            ':email' => $email, ':phone' => $phone, ':address' => $address,
            ':photo' => $profile_picture ?: 'assets/img/default-avatar.png',
            ':specialization' => $specialization, ':experience_years' => $experience_years,
            ':certification' => $certification, ':monthly_salary' => $monthly_salary,
            ':joining_date' => $joining_date, ':status' => $status,
        ]);
        $id = $pdo->lastInsertId();
        $message = 'Trainer added successfully.';
    }

    $pdo->commit();
    respond('success', $message, ['id' => $id]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('trainers/save.php error: ' . $e->getMessage());
    respond('error', 'Something went wrong while saving this trainer: ' . $e->getMessage());
}
