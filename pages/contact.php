<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('Cache-Control: no-store');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contact Me</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex" />
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
        echo '<div class="alert alert-success" role="status" aria-live="polite">Thank you! Your message has been submitted.</div>';
    } elseif (!empty($_GET['error'])) {
        $err = htmlspecialchars((string)$_GET['error'], ENT_QUOTES, 'UTF-8');
        echo '<div class="alert alert-error" role="status" aria-live="assertive">'.$err.'</div>';
    }
    ?>

    <form action="/php/submit_contact.php" method="POST" class="contact-form" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>" />

      <!-- Honeypot: submit_contact.php rejects if this is filled -->
      <div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
        <label for="company">Company</label>
        <input type="text" id="company" name="company" tabindex="-1" autocomplete="off">
      </div>

      <label for="name">Your Name</label>
      <input
        type="text"
        id="name"
        name="name"
        required
        maxlength="100"
        placeholder="John Doe"
        autocomplete="name"
      />

      <label for="email">Your Email</label>
      <input
        type="email"
        id="email"
        name="email"
        required
        maxlength="255"
        placeholder="you@example.com"
        autocomplete="email"
        inputmode="email"
      />

      <label for="message">Message</label>
      <textarea
        id="message"
        name="message"
        rows="6"
        required
        maxlength="2000"
        placeholder="Write your message here…"
        autocomplete="off"
      ></textarea>

      <button type="submit" class="btn-submit">Send Message</button>
    </form>
  </main>

  <script src="/js/navbar.js?v=99" defer></script>
</body>
</html>
