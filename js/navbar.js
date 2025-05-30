// js/navbar.js
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
    const container = document.getElementById('navbar');
    container.innerHTML = await resp.text();

    //mark current link in DOM
    const path = window.location.pathname;
    container 
    .querySelectorAll('a')
    .forEach(a => {
      const hrefPath = new URL(a.href).pathname;
      if (hrefPath === path) {
        a.classList.add('active');
      }
    });
  } catch (err) {
    console.error('Navbar load failed:', err);
  }
});