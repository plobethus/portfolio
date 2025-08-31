<?php
// portfolio/pages/login.php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Don’t cache login pages
header('Cache-Control: no-store');

// If already logged in, route by role
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'viewer';
    if ($role === 'admin') {
        header('Location: admin_menu.php'); exit;
    } elseif ($role === 'moderator') {
        header('Location: /pages/review_discords.php'); exit;
    } else {
        header('Location: /'); exit;
    }
}

$errors = [];
// Simple in-session throttle
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['login_last']     = $_SESSION['login_last'] ?? 0;

$now = time();
if ($_SESSION['login_attempts'] >= 8 && ($now - (int)$_SESSION['login_last']) < 600) {
    $errors[] = 'Too many login attempts. Please wait a few minutes and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Both username and password are required.';
    }

    if (empty($errors)) {
        $db_host = getenv('DB_HOST') ?: '127.0.0.1';
        $db_name = getenv('DB_NAME') ?: 'portfolio'; // default to portfolio
        $db_user = getenv('DB_USER') ?: 'contact_user';
        $db_pass = getenv('DB_PASS') ?: '';
        $dsn     = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            $errors[] = 'Database connection error.';
        }

        if (empty($errors)) {
            // Fetch role too
            $stmt = $pdo->prepare("
                SELECT id, password, role
                  FROM users
                 WHERE username = :username
                 LIMIT 1
            ");
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch();

            // tiny jitter to slow brute force a hair
            usleep(random_int(200_000, 450_000));

            if ($row && password_verify($password, $row['password'])) {
                // (Optional) auto-upgrade hash if algorithm/cost changed
                if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($newHash !== false) {
                        $u = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                        $u->execute([':p' => $newHash, ':id' => (int)$row['id']]);
                    }
                }

                session_regenerate_id(true);
                $_SESSION['user_id']  = (int)$row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $row['role'] ?? 'viewer';

                // reset throttle
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_last'] = $now;

                // Redirect based on role
                if ($_SESSION['role'] === 'admin') {
                    header('Location: admin_menu.php'); exit;
                } elseif ($_SESSION['role'] === 'moderator') {
                    header('Location: /pages/review_discords.php'); exit;
                } else {
                    // Viewers: send somewhere safe (or show a message)
                    header('Location: /'); exit;
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['login_last'] = $now;
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Login</title>
  <link rel="stylesheet" href="/css/global.css" />
  <link rel="stylesheet" href="/css/login.css" />
</head>
<body>
  <h1>Admin Login</h1>

  <?php if (!empty($errors)): ?>
    <ul class="error-list">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

    <label for="username">Username</label>
    <input type="text" id="username" name="username" required autofocus autocomplete="username">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">

    <button type="submit">Log In</button>
  </form>
</body>
</html>
