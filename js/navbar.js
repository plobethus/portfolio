// js/include.js
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const resp = await fetch('/includes/navbar.html');
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    document.getElementById('navbar').innerHTML = await resp.text();
  } catch (err) {
    console.error('Navbar load failed:', err);
  }
});