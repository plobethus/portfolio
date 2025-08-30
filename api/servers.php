<?php
header('Content-Type: application/json');

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_NAME') ?: 'portfolio';
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
  http_response_code(500);
  echo json_encode(['error' => 'db_error']);
  exit;
}

$courses = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_id")->fetchAll();

$stmt = $pdo->query("
  SELECT course_id, professor_name, invite_url
  FROM discord_servers
  WHERE status = 'approved'
  ORDER BY course_id, professor_name
");
$approved = [];
foreach ($stmt as $row) {
  $approved[$row['course_id']][] = [
    'name' => $row['professor_name'],
    'link' => $row['invite_url'],
  ];
}

$out = [];
foreach ($courses as $c) {
  $out[] = [
    'courseId'   => (int)$c['course_id'],
    'courseName' => $c['course_name'],
    'professors' => $approved[$c['course_id']] ?? [],
  ];
}

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
