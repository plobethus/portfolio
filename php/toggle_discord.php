<?php
// php/toggle_discord.php
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
$role = $_SESSION['role'] ?? '';
if (empty($_SESSION['user_id']) || !in_array($role, ['admin','moderator'], true)) {
  header('Location: /pages/login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  http_response_code(400);
  echo "Bad request";
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve','suspend'], true)) {
  http_response_code(400);
  echo "Invalid parameters";
  exit;
}

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

if ($action === 'approve') {
  $stmt = $pdo->prepare("UPDATE discord_servers SET status='approved', approved_at=NOW() WHERE id=:id");
} else {
  $stmt = $pdo->prepare("UPDATE discord_servers SET status='suspended', approved_at=NULL WHERE id=:id");
}
$stmt->execute([':id'=>$id]);

header('Location: /pages/review_discords.php');
