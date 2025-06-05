// portfolio/js/navbar.js

document.addEventListener('DOMContentLoaded', async () => {
  try {
    // Load navbar HTML into <header id="navbar">
    const resp = await fetch('/includes/navbar.html');
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const container = document.getElementById('navbar');
    container.innerHTML = await resp.text();

    // Highlight active link based on current URL path
    const currentPath = window.location.pathname;
    container.querySelectorAll('a').forEach(a => {
      const linkPath = new URL(a.href, window.location.origin).pathname;
      if (linkPath === currentPath) {
        a.classList.add('active');
      }
    });
  } catch (err) {
    console.error('Navbar load failed:', err);
  }
});
