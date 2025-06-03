<?php
// portfolio/pages/contact.php

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contact Us</title>
  <link rel="stylesheet" href="/css/global.css" />
  <link rel="stylesheet" href="/css/contact.css" />
  <script src="/js/includeHead.js" defer></script>
</head>
<body>

  <div id="navbar"></div>

  <main class="contact-container">
    <h1>Contact Me</h1>

    <?php
    if (!empty($_GET['status']) && $_GET['status'] === 'success') {
        echo '<p class="success">Thank you! Your message has been submitted.</p>';
    } elseif (!empty($_GET['error'])) {
        $err = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
        echo "<p class=\"error\">{$err}</p>";
    }
    ?>

    <form action="/php/submit_contact.php" method="POST" novalidate class="contact-form">
      <input type="hidden" name="csrf_token"
             value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>" />

      <label for="name">Your Name</label>
      <input
        type="text"
        id="name"
        name="name"
        required
        maxlength="100"
        placeholder="John Doe"
      />

      <label for="email">Your Email</label>
      <input
        type="email"
        id="email"
        name="email"
        required
        maxlength="255"
        placeholder="you@example.com"
      />

      <label for="message">Message</label>
      <textarea
        id="message"
        name="message"
        rows="6"
        required
        maxlength="2000"
        placeholder="Write your message here…"
      ></textarea>

      <button type="submit" class="btn-submit">Send Message</button>
    </form>
  </main>
  <script src="/js/navbar.js" defer></script>
</body>
</html>
