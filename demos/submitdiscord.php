<?php
// demos/submitdiscord.php
declare(strict_types=1);

session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

header('Cache-Control: no-store');

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'portfolio';
$db_user = getenv('DB_USER') ?: 'contact_user';
$db_pass = getenv('DB_PASS') ?: '';
$dsn     = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$YOUTUBE_INVITE_HELP_URL = "https://www.youtube.com/watch?v=00IKj-j8aSE";

function normalize_discord_invite(string $url): string {
  $url = trim($url);
  if ($url === '') return '';

  if (!preg_match('~^https?://~i', $url)) {
    $url = 'https://' . $url; // allow users to omit scheme
  }
  $parts = parse_url($url);
  if (!$parts || empty($parts['host']) || empty($parts['path'])) return '';

  $host = strtolower($parts['host']);
  $path = trim($parts['path'], '/');

  // Accept discord.gg/<code> OR discord.com/invite/<code>
  if ($host === 'discord.gg') {
    $segments = explode('/', $path);
    $code = $segments[0] ?? '';
  } elseif ($host === 'discord.com' && str_starts_with($path, 'invite/')) {
    $code = substr($path, strlen('invite/'));
  } else {
    return '';
  }

  // Vanity codes are alnum or dash
  if (!preg_match('/^[A-Za-z0-9-]{2,64}$/', $code)) return '';

  // Canonicalize
  return "https://discord.gg/" . $code;
}

$errors = []; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['csrf_token']) || !hash_equals($csrf, (string)$_POST['csrf_token'])) {
    $errors[] = 'Invalid request. Please refresh and try again.';
  } else {
    $course_id = (int)($_POST['course_id'] ?? 0);

    // Professor: trim + collapse whitespace + allow letters, spaces, . - '
    $prof = preg_replace('/\s+/u', ' ', trim((string)($_POST['professor_name'] ?? '')));
    if ($prof === '' || mb_strlen($prof) > 100 || !preg_match("/^[\p{L} .'-]+$/u", $prof)) {
      $errors[] = 'Professor name required (letters, spaces, . - \' only, max 100 chars).';
    }

    // Invite: normalize to canonical https://discord.gg/<code>
    $invite = normalize_discord_invite((string)($_POST['invite_url'] ?? ''));
    if ($invite === '') {
      $errors[] = 'Provide a valid Discord invite link (discord.gg or discord.com/invite).';
    }

    if (!$errors) {
      try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [
          PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false
        ]);

        // Rate limit: max 5 submissions per hour per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $cnt = $pdo->prepare("
          SELECT COUNT(*) FROM discord_servers
          WHERE submitter_ip = INET6_ATON(:ip)
            AND submitted_at >= NOW() - INTERVAL 1 HOUR
        ");
        $cnt->execute([':ip' => $ip]);
        if ((int)$cnt->fetchColumn() > 5) {
          $errors[] = 'Too many submissions from your IP. Try again later.';
        }

        // Ensure course exists
        if (!$errors) {
          $exists = $pdo->prepare("SELECT 1 FROM courses WHERE course_id = ?");
          $exists->execute([$course_id]);
          if (!$exists->fetch()) {
            $errors[] = 'Unknown course.';
          }
        }

        // Reject duplicates (same course/prof/invite)
        if (!$errors) {
          $dupe = $pdo->prepare("
            SELECT 1 FROM discord_servers
            WHERE course_id = :cid AND professor_name = :prof AND invite_url = :invite
            LIMIT 1
          ");
          $dupe->execute([':cid'=>$course_id, ':prof'=>$prof, ':invite'=>$invite]);
          if ($dupe->fetch()) {
            $errors[] = 'This link is already submitted (pending or approved).';
          }
        }

        // Insert suspended by default
        if (!$errors) {
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
            ':ip'    => $ip,
            ':ua'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
          ]);
          $ok = true;
        }
      } catch (Throwable $e) {
        // error_log($e->getMessage());
        $errors[] = 'Server error. Try again later.';
      }
    }
  }
}

// Load courses for dropdown
try {
  $pdo2 = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
  ]);
  $courses = $pdo2->query("SELECT course_id, course_name FROM courses ORDER BY course_id")->fetchAll();
} catch (Throwable $e) { $courses = []; }
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
  </style>
</head>
<body>
<div class="wrap">
  <h1>Submit a Discord</h1>

  <?php if ($ok): ?>
    <div class="alert alert-success" role="status" aria-live="polite">
      Thanks! Your link was submitted for review. It will appear after approval.
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-error" role="status" aria-live="assertive">
      <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e,ENT_QUOTES,'UTF-8').'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="course">Course</label>
    <select id="course" name="course_id" required>
      <option value="">-- Select course --</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['course_id'] ?>">
          <?= (int)$c['course_id'] ?> — <?= htmlspecialchars($c['course_name'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="prof">Professor name</label>
    <input id="prof" name="professor_name" required maxlength="100" placeholder="e.g., Eick" autocomplete="name" />

    <label for="invite">Discord invite link</label>
    <input id="invite" name="invite_url" required maxlength="255" placeholder="https://discord.gg/yourcode" inputmode="url" autocomplete="url" />
    <p class="notice">Please put a <strong>PERMANENT, NON-EXPIRING</strong> invite link.
      <a href="<?= htmlspecialchars($YOUTUBE_INVITE_HELP_URL, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">How to create one (video)</a>.
    </p>

    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Submit</button>
  </form>

  <p style="margin-top:1.5rem;"><a href="/demos/UHDL.php">Back to menu</a></p>
</div>
</body>
</html>
