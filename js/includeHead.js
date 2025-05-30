// portfolio/js/includeHead.js
(async () => {
  try {
    const res = await fetch('/includes/favicons.html');
    if (!res.ok) throw new Error(res.status);

    document.head.insertAdjacentHTML('beforeend', await res.text());
  } catch (e) {
    console.error('Could not load favicons:', e);
  }
})();
