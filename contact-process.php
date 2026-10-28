<?php
require_once __DIR__ . '/config/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

verify_csrf();

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    die(json_encode(['success' => false, 'message' => 'Please fill in your name, email, and message.']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Please enter a valid email address.']));
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $phone ?: null, $subject ?: null, $message]);

    log_activity($pdo, 'contact_form', "New contact message from $name ($email)");

    echo json_encode([
        'success' => true,
        'message' => 'Thanks for reaching out! We\'ll get back to you within 24 hours.',
    ]);
} catch (Throwable $e) {
    error_log('contact_process error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'The contact_messages table may be missing. Please run sql/public_site.sql, then try again.',
    ]);
}
