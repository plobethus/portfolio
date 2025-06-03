<?php
// portfolio/php/submit_contact.php


session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/', 
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $err = urlencode('Invalid CSRF token.');
    header("Location: /pages/contact.php?error={$err}");
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Name must be under 100 characters.';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (mb_strlen($email) > 255) {
    $errors[] = 'Email must be under 255 characters.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email is not valid.';
}

if ($message === '') {
    $errors[] = 'Message is required.';
} elseif (mb_strlen($message) > 2000) {
    $errors[] = 'Message must be under 2000 characters.';
}

if (!empty($errors)) {
    $err = urlencode($errors[0]);
    header("Location: /pages/contact.php?error={$err}");
    exit;
}

$safe_name    = strip_tags($name);
$safe_email   = filter_var($email, FILTER_SANITIZE_EMAIL);
$safe_message = strip_tags($message);

$remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $remote_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

if (!isset($_SESSION['last_submit_time'])) {
    $_SESSION['last_submit_time'] = time();
    $_SESSION['submit_count']     = 1;
} else {
    $elapsed = time() - $_SESSION['last_submit_time'];
    if ($elapsed < 3600) {
        $_SESSION['submit_count']++;
        if ($_SESSION['submit_count'] > 5) {
            $err = urlencode('Too many submissions. Please wait before trying again.');
            header("Location: /pages/contact.php?error={$err}");
            exit;
        }
    } else {
        $_SESSION['last_submit_time'] = time();
        $_SESSION['submit_count']     = 1;
    }
}

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'contact_db';
$db_user = getenv('DB_USER') ?: 'contact_user';
$db_pass = getenv('DB_PASS') ?: '';
$dsn     = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO messages (name, email, ip_address, message)
        VALUES (:name, :email, :ip, :message)
    ");
    $stmt->execute([
        ':name'    => $safe_name,
        ':email'   => $safe_email,
        ':ip'      => $remote_ip,
        ':message' => $safe_message,
    ]);

    header("Location: /pages/contact.php?status=success");
    exit;
} catch (PDOException $e) {
    $err = urlencode('Database error. Please try again later.');
    header("Location: /pages/contact.php?error={$err}");
    exit;
}
