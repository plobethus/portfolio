<?php
// php/import_servers.php
// Run once from CLI: php /var/www/portfolio/php/import_servers.php
// Reads /var/www/portfolio/api/servers.json and imports:
//   - courses into courses table
//   - each professor/link into discord_servers with status='approved'

$apiJson = '/var/www/portfolio/api/servers.json';
if (!is_readable($apiJson)) {
  fwrite(STDERR, "Cannot read $apiJson\n");
  exit(1);
}

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'portfolio';
$db_user = getenv('DB_USER') ?: 'contact_user';
$db_pass = getenv('DB_PASS') ?: '';
$dsn     = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$pdo = new PDO($dsn, $db_user, $db_pass, [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
]);

$data = json_decode(file_get_contents($apiJson), true);
if (!is_array($data)) {
  fwrite(STDERR, "Invalid JSON structure.\n");
  exit(1);
}

$pdo->beginTransaction();

$insCourse = $pdo->prepare("
  INSERT INTO courses (course_id, course_name)
  VALUES (:id, :name)
  ON DUPLICATE KEY UPDATE course_name = VALUES(course_name)
");

$insServer = $pdo->prepare("
  INSERT INTO discord_servers (course_id, professor_name, invite_url, status, approved_at)
  VALUES (:course_id, :prof, :invite, 'approved', NOW())
");

foreach ($data as $course) {
  $cid  = (int)($course['courseId'] ?? 0);
  $cname= trim((string)($course['courseName'] ?? ''));
  if ($cid <= 0 || $cname === '') continue;

  $insCourse->execute([':id'=>$cid, ':name'=>$cname]);

  $profs = $course['professors'] ?? [];
  if (is_array($profs)) {
    foreach ($profs as $p) {
      $prof   = trim((string)($p['name'] ?? ''));
      $invite = trim((string)($p['link'] ?? ''));
      if ($prof === '' || $invite === '') continue;
      $insServer->execute([
        ':course_id' => $cid,
        ':prof'      => $prof,
        ':invite'    => $invite
      ]);
    }
  }
}

$pdo->commit();
echo "Import complete.\n";
