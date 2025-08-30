<?php
// pages/review_discords.php
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /pages/login.php');
  exit;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'portfolio';
$db_user = getenv('DB_USER') ?: 'contact_user';
$db_pass = getenv('DB_PASS') ?: '';
$dsn     = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$pdo = new PDO($dsn, $db_user, $db_pass, [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false
]);

$q = $pdo->query("
  SELECT s.id, s.course_id, c.course_name, s.professor_name, s.invite_url,
         s.status, s.submitted_at, s.approved_at
  FROM discord_servers s
  JOIN courses c ON c.course_id = s.course_id
  ORDER BY s.status='suspended' DESC, s.submitted_at DESC
");
$rows = $q->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Review Discord Submissions</title>
  <link rel="stylesheet" href="/css/global.css">
  <style>
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #ddd;padding:.5rem;vertical-align:top}
    th{background:#f7f7f7}
    .pill{padding:.2rem .5rem;border-radius:999px;font-size:.85rem}
    .suspended{background:#ffecec}
    .approved{background:#e6ffed}
    form{display:inline}
  </style>
</head>
<body>
  <h1>Review Discord Submissions</h1>
  <table>
    <thead>
      <tr>
        <th>ID</th><th>Course</th><th>Professor</th><th>Invite</th>
        <th>Status</th><th>Submitted</th><th>Approved</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= (int)$r['course_id'] ?> — <?= htmlspecialchars($r['course_name'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($r['professor_name'], ENT_QUOTES) ?></td>
        <td><a href="<?= htmlspecialchars($r['invite_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">open</a></td>
        <td><span class="pill <?= $r['status'] ?>"><?= htmlspecialchars($r['status'], ENT_QUOTES) ?></span></td>
        <td><?= htmlspecialchars($r['submitted_at'] ?? '', ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($r['approved_at'] ?? '', ENT_QUOTES) ?></td>
        <td>
          <?php if ($r['status'] !== 'approved'): ?>
            <form method="POST" action="/php/toggle_discord.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <button type="submit">Approve</button>
            </form>
          <?php endif; ?>
          <?php if ($r['status'] !== 'suspended'): ?>
            <form method="POST" action="/php/toggle_discord.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="suspend">
              <button type="submit">Suspend</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:1rem;">
  <a href="/pages/admin_menu.php">Back to Admin Menu</a>
</p>

</body>
</html>
