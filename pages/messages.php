<?php
// portfolio/pages/messages.php

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
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
} catch (PDOException $e) {
    echo '<p>Database connection error.</p>';
    exit;
}

$stmt = $pdo->query("
    SELECT id, name, email, ip_address, message, created_at
      FROM messages
     ORDER BY created_at DESC
");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contact Messages</title>
  <link rel="stylesheet" href="/css/messages.css" />

</head>
<body>
  <div class="top-bar">
    <span>Logged in as <strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong></span>
    <a href="/php/logout.php">Log out</a>
  </div>

  <h1>All Contact Form Submissions</h1>
  <?php if (empty($messages)): ?>
    <p>No messages found.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>IP Address</th>
          <th>Message</th>
          <th>Received At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($messages as $row): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><pre><?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8') ?></pre></td>
          <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
