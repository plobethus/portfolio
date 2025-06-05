//portfolio/js/about.js

// /js/about.js
// ---------------------------------------------------------------
//  All the fun interactions for about.html
// ---------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {

  /* -------------------------------------------------------------
     1. ✍️ Dynamic Typewriter headline
  ----------------------------------------------------------------*/
  const headline  = document.getElementById('typewriter');
  const phrases   = [
    'Hi — I’m Your Name.',
    'I build delightful web apps.',
    'I turn coffee into commit hashes.',
    'Let’s create something amazing!'
  ];
  let idx = 0, char = 0, direction = 1;        // direction: 1 = typing, -1 = deleting
  const speed = 75;                            // ms per character

  const type = () => {
    const current = phrases[idx];
    const nextLen = char + direction;
    headline.textContent = current.slice(0, nextLen);

    // reached full phrase → pause then delete
    if (direction === 1 && nextLen === current.length) {
      direction = -1;
      setTimeout(type, 1200);
    }
    // fully deleted → move to next phrase
    else if (direction === -1 && nextLen === 0) {
      direction = 1;
      idx = (idx + 1) % phrases.length;
      setTimeout(type, 400);
    } else {
      char = nextLen;
      setTimeout(type, speed);
    }
  };
  type();


  /* -------------------------------------------------------------
     2. 🖼️  3-D tilt on portrait
  ----------------------------------------------------------------*/
  const photo = document.getElementById('profile-photo');
  const tilt  = (e) => {
    const rect = photo.getBoundingClientRect();
    const x    = e.clientX - rect.left - rect.width / 2;
    const y    = e.clientY - rect.top  - rect.height / 2;
    const max  = 10;                           // deg
    photo.style.transform = `rotateX(${-y / rect.height * max}deg)
                             rotateY(${ x / rect.width  * max}deg)`;
  };
  photo.addEventListener('pointermove', tilt);
  photo.addEventListener('pointerleave', () => { photo.style.transform = '' });


  /* -------------------------------------------------------------
     3. 📊 Animate skill bars & timeline on scroll
  ----------------------------------------------------------------*/
  const revealOpts = { threshold: 0.35 };
  const revealIO   = new IntersectionObserver((entries, io) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;

      // skills
      if (entry.target.classList.contains('skill-bar')) {
        const pct = entry.target.dataset.skill + '%';
        entry.target.style.width = pct;
      }

      // timeline fade-in
      if (entry.target.classList.contains('timeline-item')) {
        entry.target.classList.add('in-view');
      }

      io.unobserve(entry.target);
    });
  }, revealOpts);

  document
    .querySelectorAll('.skill-bar, .timeline-item')
    .forEach(el => revealIO.observe(el));


  /* -------------------------------------------------------------
     4. 🎉 Confetti celebration on résumé download
  ----------------------------------------------------------------*/
  importConfetti().then(({shoot}) => {
    document.getElementById('resume-btn')
            .addEventListener('click', () => shoot(200));
  });

  /* tiny 1-file, no-dep confetti helper -------------------------- */
  async function importConfetti() {
    // create once
    if (window._confetti) return window._confetti;

    const canvas = document.getElementById('confetti');
    const ctx    = canvas.getContext('2d');
    const dpr    = window.devicePixelRatio || 1;
    const resize = () => {
      canvas.width  = innerWidth  * dpr;
      canvas.height = innerHeight * dpr;
      canvas.classList.remove('hidden');
    };
    resize(); addEventListener('resize', resize);

    const pieces = [];
    function piece() {
      const x = Math.random() * canvas.width;
      const y = Math.random() * canvas.height * -0.1;
      const s = 6 + Math.random() * 8;
      const g = 0.25 + Math.random() * 0.25;
      const r = Math.random() * Math.PI * 2;
      return {x,y,s,g,r,vx:Math.random()*4-2,vy:0,vr:Math.random()*0.1-0.05};
    }
    function draw() {
      ctx.clearRect(0,0,canvas.width,canvas.height);
      pieces.forEach(p => {
        p.vy += p.g   * dpr;
        p.x  += p.vx * dpr;
        p.y  += p.vy;
        p.r  += p.vr;

        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.r);
        ctx.fillStyle = `hsl(${Math.random()*360},90%,60%)`;
        ctx.fillRect(-p.s/2, -p.s/2, p.s, p.s);
        ctx.restore();
      });
      // remove off-screen
      for (let i=pieces.length-1;i>=0;--i){
        if (pieces[i].y > canvas.height) pieces.splice(i,1);
      }
      requestAnimationFrame(draw);
    }
    draw();

    window._confetti = {
      shoot(n=120) { while (n--) pieces.push(piece()) }
    };
    return window._confetti;
  }

});   // DOMContentLoaded
