<?php
// demos/submitdiscord.php
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'portfolio';
$db_user = getenv('DB_USER') ?: 'contact_user';
$db_pass = getenv('DB_PASS') ?: '';
$dsn     = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$errors = []; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh and try again.';
  } else {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $prof      = trim($_POST['professor_name'] ?? '');
    $invite    = trim($_POST['invite_url'] ?? '');

    if ($course_id <= 0) $errors[] = 'Please pick a course.';
    if ($prof === '' || mb_strlen($prof) > 255) $errors[] = 'Professor name required (max 255 chars).';

    $re = '/^(https?:\/\/)?(discord\.gg|discord\.com\/invite)\/[A-Za-z0-9-]+\/?$/i';
    if ($invite === '' || !preg_match($re, $invite)) $errors[] = 'Provide a valid Discord invite link.';

    if (!$errors) {
      try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [
          PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false
        ]);

        $exists = $pdo->prepare("SELECT 1 FROM courses WHERE course_id=?");
        $exists->execute([$course_id]);
        if (!$exists->fetch()) $errors[] = 'Unknown course.';
        else {
          $ins = $pdo->prepare("
            INSERT INTO discord_servers
              (course_id, professor_name, invite_url, status, submitter_ip, user_agent)
            VALUES
              (:cid, :prof, :invite, 'suspended', INET6_ATON(:ip), :ua)
          ");
          $ins->execute([
            ':cid'   => $course_id,
            ':prof'  => $prof,
            ':invite'=> $invite,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            ':ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
          ]);
          $ok = true;
        }
      } catch (Throwable $e) {
        $errors[] = 'Server error. Try again later.';
      }
    }
  }
}


try {
  $pdo2 = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
  ]);
  $courses = $pdo2->query("SELECT course_id, course_name FROM courses ORDER BY course_id")->fetchAll();
} catch (Throwable $e) { $courses = []; }

$YOUTUBE_INVITE_HELP_URL = "https://www.youtube.com/watch?v=00IKj-j8aSE"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Submit a Discord</title>
  <link rel="stylesheet" href="/css/global.css">
  <style>
    .wrap { max-width: 720px; margin: 2rem auto; }
    label { display:block; margin-top:1rem; }
    input, select { width:100%; padding:.6rem; }
    .notice { font-size:.95rem; color:#555; }
    .success { background:#e6ffed; padding:.8rem; border-radius:8px; }
    .error { background:#ffecec; padding:.8rem; border-radius:8px; }
  </style>
</head>
<body>
<div class="wrap">
  <h1>Submit a Discord</h1>
  <?php if ($ok): ?>
    <p class="success">Thanks! Your link was submitted for review. It will appear after approval.</p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="error"><ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e,ENT_QUOTES).'</li>'; ?></ul></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
    <label for="course">Course</label>
    <select id="course" name="course_id" required>
      <option value="">-- Select course --</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['course_id'] ?>">
          <?= (int)$c['course_id'] ?> — <?= htmlspecialchars($c['course_name'], ENT_QUOTES) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="prof">Professor name</label>
    <input id="prof" name="professor_name" required maxlength="255" placeholder="e.g., Eick">

    <label for="invite">Discord invite link</label>
    <input id="invite" name="invite_url" required maxlength="255" placeholder="https://discord.gg/yourcode">
    <p class="notice">Please put a <strong>PERMANENT, NON-EXPIRING</strong> invite link.
      <a href="<?= htmlspecialchars($YOUTUBE_INVITE_HELP_URL, ENT_QUOTES) ?>" target="_blank" rel="noopener">How to create one (video)</a>.
    </p>

    <button type="submit" style="margin-top:1rem;">Submit</button>
  </form>

  <p style="margin-top:1.5rem;"><a href="/demos/UHDL.php">Back to menu</a></p>
</div>
</body>
</html>
