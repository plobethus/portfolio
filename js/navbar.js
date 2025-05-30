// js/include.js
document.addEventListener('DOMContentLoaded', async () => {
  //navbar stylesheet
  if(!document.querySelector('link[href="/css/navbar.css"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = '/css/navbar.css';
    document.head.appendChild(link);
  }

  //fetches and inserts navbar html
  try {
    const resp = await fetch('/includes/navbar.html');
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    document.getElementById('navbar').innerHTML = await resp.text();
  } catch (err) {
    console.error('Navbar load failed:', err);
  }
});