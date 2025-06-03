<?php
// /portfolio/php/logout.php

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$_SESSION = [];
session_destroy();

header('Location: /pages/login.php');
exit;
?>
