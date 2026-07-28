/**
 * URTH — Architecture Studio
 * script.js — Carousel Logic
 */

(function () {
  'use strict';

  const track = document.getElementById('carouselTrack');
  const originalSlides = Array.from(track.querySelectorAll('.carousel-slide'));
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  const TOTAL = originalSlides.length;

  // Clone slides to create robust infinite loop effect for rapid clicking
  // We'll append 3 clone sets and prepend 3 clone sets.
  for (let i = 0; i < 3; i++) {
    originalSlides.forEach(slide => {
      const clone = slide.cloneNode(true);
      clone.classList.remove('active');
      track.appendChild(clone);
    });
  }
  
  for (let i = 0; i < 3; i++) {
    [...originalSlides].reverse().forEach(slide => {
      const clone = slide.cloneNode(true);
      clone.classList.remove('active');
      track.insertBefore(clone, track.firstChild);
    });
  }

  const allSlides = Array.from(track.querySelectorAll('.carousel-slide'));
  
  // Set initial active index to the first item in the original middle set (index = TOTAL * 3)
  let currentIndex = (TOTAL * 3) + originalSlides.findIndex(s => s.classList.contains('active'));
  if (currentIndex < (TOTAL * 3)) currentIndex = (TOTAL * 3) + 1; // Fallback

  allSlides.forEach(s => s.classList.remove('active'));
  allSlides[currentIndex].classList.add('active');

  const slideWidth = 686;
  const gap = 30;

  function updateTrackPosition(instant = false) {
    const trackOuter = document.querySelector('.carousel-track-outer');
    if (!trackOuter) return;
    const width = trackOuter.clientWidth;
    
    const targetLeft = (width - slideWidth) / 2;
    const currentLeft = (currentIndex * slideWidth) + (currentIndex * gap);
    
    const offsetPixels = targetLeft - currentLeft;

    if (instant) {
      track.style.transition = 'none';
      track.style.transform = `translateX(${offsetPixels}px)`;
      void track.offsetWidth; // force reflow
      track.style.transition = '';
    } else {
      track.style.transform = `translateX(${offsetPixels}px)`;
    }
  }

  function setActive(newIdx) {
    allSlides[currentIndex].classList.remove('active');
    currentIndex = newIdx;
    allSlides[currentIndex].classList.add('active');
    updateTrackPosition();
  }

  // Teleport track silently when the slide transition finishes
  track.addEventListener('transitionend', (e) => {
    if (e.target !== track) return;
    if (e.propertyName !== 'transform') return;

    let changed = false;
    
    // If we moved past the middle set, teleport back to the middle
    if (currentIndex >= TOTAL * 4) {
      currentIndex -= TOTAL;
      changed = true;
    } else if (currentIndex < TOTAL * 2) {
      currentIndex += TOTAL;
      changed = true;
    }

    if (changed) {
      allSlides.forEach(s => s.classList.remove('active'));
      allSlides[currentIndex].classList.add('active');
      updateTrackPosition(true);
    }
  });

  window.addEventListener('resize', () => updateTrackPosition(true));
  setTimeout(() => updateTrackPosition(true), 50);

  /* ── Arrow clicks ── */
  prevBtn.addEventListener('click', () => setActive(currentIndex - 1));
  nextBtn.addEventListener('click', () => setActive(currentIndex + 1));

  /* ── Event Delegation for Slides ── */
  track.addEventListener('click', (e) => {
    const slide = e.target.closest('.carousel-slide');
    if (!slide) return;
    const idx = allSlides.indexOf(slide);
    if (idx !== currentIndex && idx !== -1) {
      setActive(idx);
    }
  });

  /* ── Keyboard support ── */
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft')  { setActive(currentIndex - 1); }
    if (e.key === 'ArrowRight') { setActive(currentIndex + 1); }
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
      setActive(dx < 0 ? currentIndex + 1 : currentIndex - 1);
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
  let autoTimer = setInterval(() => setActive(currentIndex + 1), 5000);
  const resetTimer = () => {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => setActive(currentIndex + 1), 5000);
  };
  prevBtn.addEventListener('click', resetTimer);
  nextBtn.addEventListener('click', resetTimer);
  track.addEventListener('click', resetTimer);

})();
