<?php
// portfolio/pages/login.php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: admin_menu.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Both username and password are required.';
    }

    if (empty($errors)) {
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
        } catch (PDOException $e) {
            $errors[] = 'Database connection error.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                SELECT id, password
                  FROM users
                 WHERE username = :username
                 LIMIT 1
            ");
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch();

            if ($row && password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $username;
                header('Location: admin_menu.php');
                exit;
            } else {
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
    <input type="text" id="username" name="username" required autofocus>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Log In</button>
  </form>
</body>
</html>

