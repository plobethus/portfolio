// portfolio/js/navbar.js
// Loads the navbar HTML and wires up a mobile-friendly hamburger menu
// plus active-link highlighting.

document.addEventListener('DOMContentLoaded', () => {
  const mount = document.getElementById('navbar');
  if (!mount) return;

  fetch('/includes/navbar.html')
    .then((resp) => {
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      return resp.text();
    })
    .then((html) => {
      mount.innerHTML = html;
      initNavbar(mount);
      highlightActiveLink(mount);
    })
    .catch((err) => {
      console.error('Navbar load failed:', err);
    });
});

function initNavbar(scope) {
  const nav = scope.querySelector('.navbar');
  if (!nav) return;

  const btn = nav.querySelector('.navbar-toggle');
  const menu = nav.querySelector('#mainmenu');

  if (!btn || !menu) return;

  const OPEN_CLASS = 'is-open';

  function setOpen(isOpen) {
    btn.setAttribute('aria-expanded', String(isOpen));
    btn.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    menu.hidden = !isOpen;
    nav.classList.toggle(OPEN_CLASS, isOpen);
  }

  // Start closed
  setOpen(false);

  // Toggle on click
  btn.addEventListener('click', () => {
    const isOpen = btn.getAttribute('aria-expanded') !== 'true';
    setOpen(isOpen);
  });

  // Close with ESC
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });

  // Close when clicking a link in the menu
  menu.addEventListener('click', (e) => {
    const t = e.target;
    if (t && t.matches('a')) setOpen(false);
  });

  // Close when clicking outside the nav
  document.addEventListener('click', (e) => {
    if (!nav.contains(e.target)) setOpen(false);
  });

  // Optional: close on resize to desktop to avoid stale state
  let lastIsMobile = isMobile();
  window.addEventListener('resize', () => {
    const nowIsMobile = isMobile();
    if (lastIsMobile !== nowIsMobile && !nowIsMobile) {
      setOpen(false);
    }
    lastIsMobile = nowIsMobile;
  });

  function isMobile() {
    return window.matchMedia('(max-width: 768px)').matches;
  }
}

function highlightActiveLink(scope) {
  const container = scope.querySelector('.navbar') || scope;
  const here = normalizePath(window.location.pathname);

  container.querySelectorAll('a[href]').forEach((a) => {
    const href = a.getAttribute('href');
    if (!href) return;

    const path = normalizePath(new URL(href, window.location.origin).pathname);

    // Exact match, with small nicety for root vs /index.html
    if (path === here ||
        (here === '/' && (path === '/' || path === '/index.html'))) {
      a.classList.add('active');
    }
  });
}

function normalizePath(pathname) {
  try {
    // remove trailing slashes
    let p = pathname.replace(/\/+$/, '');
    if (p === '') p = '/';
    return p;
  } catch {
    return pathname || '/';
  }
}
