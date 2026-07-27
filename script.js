/**
 * URTH — Architecture Studio
 * script.js — Carousel Logic
 */

(function () {
  'use strict';

  const track      = document.getElementById('carouselTrack');
  const slides     = Array.from(track.querySelectorAll('.carousel-slide'));
  const prevBtn    = document.getElementById('prevBtn');
  const nextBtn    = document.getElementById('nextBtn');

  const TOTAL      = slides.length;
  let   activeIdx  = slides.findIndex(s => s.classList.contains('active'));
  if (activeIdx < 0) activeIdx = 1;

  /* ── helpers ── */

  function setActive(newIdx) {
    // wrap around
    newIdx = ((newIdx % TOTAL) + TOTAL) % TOTAL;

    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === newIdx);
    });

    activeIdx = newIdx;
  }

  /* ── Arrow clicks ── */

  prevBtn.addEventListener('click', () => {
    setActive(activeIdx - 1);
  });

  nextBtn.addEventListener('click', () => {
    setActive(activeIdx + 1);
  });

  /* ── Clicking a side slide activates it ── */

  slides.forEach((slide, i) => {
    slide.addEventListener('click', () => {
      if (i !== activeIdx) {
        setActive(i);
      }
    });
  });

  /* ── Keyboard support ── */

  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft')  { setActive(activeIdx - 1); }
    if (e.key === 'ArrowRight') { setActive(activeIdx + 1); }
  });

  /* ── Touch / swipe support ── */

  let touchStartX = null;

  document.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].clientX;
  }, { passive: true });

  document.addEventListener('touchend', (e) => {
    if (touchStartX === null) return;
    const dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) > 50) {
      setActive(dx < 0 ? activeIdx + 1 : activeIdx - 1);
    }
    touchStartX = null;
  }, { passive: true });

  /* ── Nav link active state on click ── */

  const navLinks = document.querySelectorAll('.footer-nav__link');
  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      navLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
    });
  });

  /* ── Auto-advance every 5 seconds ── */

  let autoTimer = setInterval(() => setActive(activeIdx + 1), 5000);

  const resetTimer = () => {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => setActive(activeIdx + 1), 5000);
  };

  prevBtn.addEventListener('click', resetTimer);
  nextBtn.addEventListener('click', resetTimer);
  slides.forEach(slide => slide.addEventListener('click', resetTimer));

})();
