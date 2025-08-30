<?php
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /pages/login.php');
  exit;
}
$username = htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Menu</title>
  <link rel="stylesheet" href="/css/global.css" />
  <style>
    .wrap { max-width: 560px; margin: 3rem auto; text-align:center; }
    .grid { display:grid; gap:1rem; }
    .btn { display:block; padding:1rem; border:1px solid #ddd; border-radius:12px; text-decoration:none; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Welcome, <?= $username ?></h1>
    <div class="grid">
      <a class="btn" href="/pages/messages.php">View Messages</a>
      <a class="btn" href="/pages/review_discords.php">Review Discord Submissions</a>
      <a class="btn" href="/demos/UHDL.php">UHDL Menu</a>
    </div>
    <p style="margin-top:1rem;"><a href="/pages/logout.php">Log out</a></p>
  </div>
</body>
</html>
