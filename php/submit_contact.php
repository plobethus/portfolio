<?php
// portfolio/php/submit_contact.php
declare(strict_types=1);

// Only accept POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    header('Location: /pages/contact.php');
    exit;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// CSRF
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_POST['csrf_token'])
) {
    $err = urlencode('Invalid CSRF token.');
    header("Location: /pages/contact.php?error={$err}");
    exit;
}

// Honeypot (must match hidden field in contact.php)
if (!empty($_POST['company'])) {
    $err = urlencode('Invalid submission.');
    header("Location: /pages/contact.php?error={$err}");
    exit;
}

// Normalize inputs
$name    = trim((string)($_POST['name']    ?? ''));
$email   = trim((string)($_POST['email']   ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

// Validate
$errors = [];

$name = preg_replace('/\s+/u', ' ', $name);
if ($name === '') {
    $errors[] = 'Name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Name must be under 100 characters.';
} elseif (!preg_match("/^[\p{L} .'-]+$/u", $name)) {
    $errors[] = 'Name may contain letters, spaces, dot, hyphen, apostrophe.';
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

// Sanitize for storage (UI must still escape on render)
$safe_name    = strip_tags($name);
$safe_email   = filter_var($email, FILTER_SANITIZE_EMAIL);
$safe_message = strip_tags($message);

// Real client IP (Cloudflare-aware; ideally also enable mod_remoteip)
$remote_ip = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '';

// Session-based throttle (per browser session)
if (!isset($_SESSION['last_submit_time'])) {
    $_SESSION['last_submit_time'] = time();
    $_SESSION['submit_count']     = 1;
} else {
    $elapsed = time() - (int)$_SESSION['last_submit_time'];
    if ($elapsed < 3600) {
        $_SESSION['submit_count'] = (int)$_SESSION['submit_count'] + 1;
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

// DB
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

    try {
        $q = $pdo->prepare("
            SELECT COUNT(*) FROM messages
            WHERE ip_address = :ip
              AND created_at >= NOW() - INTERVAL 1 HOUR
        ");
        $q->execute([':ip' => $remote_ip]);
        if ((int)$q->fetchColumn() > 10) {
            $err = urlencode('Too many submissions from your IP. Please try again later.');
            header("Location: /pages/contact.php?error={$err}");
            exit;
        }
    } catch (Throwable $rateEx) {

    }

    // Insert
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

    // Success
    header("Location: /pages/contact.php?status=success");
    exit;

} catch (PDOException $e) {
    $err = urlencode('Database error. Please try again later.');
    header("Location: /pages/contact.php?error={$err}");
    exit;
}
