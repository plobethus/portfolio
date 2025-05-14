// js/include.js
document.addEventListener('DOMContentLoaded', () => {
    //header.html
    fetch('includes/header.html')
    .then(r => r.text())
    .then(html => {
      document.body.insertAdjacentHTML('afterbegin', html);
      initNavToggle();   
    })
    .catch(console.error);

  // Mobile nav toggle logic
  function initNavToggle() {
    const toggle = document.querySelector('.nav-toggle');
    const links  = document.querySelector('.nav-links');
    toggle.addEventListener('click', () => {
      links.classList.toggle('open');
      toggle.classList.toggle('open');
    });
  }
});
