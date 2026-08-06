/**
 * Featured Projects — Premium Stacked Scroll Interaction
 * Uses GSAP + ScrollTrigger for the card stacking effect
 */
(function () {
  'use strict';

  function initFeaturedProjects() {
    var section = document.querySelector('.fp-section');
    if (!section) return;

    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
      setTimeout(initFeaturedProjects, 100);
      return;
    }

    gsap.registerPlugin(ScrollTrigger);

    var cards = gsap.utils.toArray('.fp-card');
    var isMobile = window.matchMedia('(max-width: 768px)').matches;

    if (isMobile) {
      initMobileAnimations(cards);
      return;
    }

    initDesktopStacking(cards, section);
  }

  function initDesktopStacking(cards, section) {
    var STACK_OFFSET = 40;

    cards.forEach(function (card, i) {
      var img      = card.querySelector('.fp-card__img');
      var location = card.querySelector('.fp-card__location');
      var title    = card.querySelector('.fp-card__title');
      var desc     = card.querySelector('.fp-card__desc');
      var link     = card.querySelector('.fp-card__link');

      card.style.position = 'sticky';
      card.style.top      = (80 + i * STACK_OFFSET) + 'px';
      card.style.zIndex   = 10 + i;

      if (i > 0) {
        gsap.set([location, title, desc, link], { opacity: 0, y: 40 });

        ScrollTrigger.create({
          trigger: card,
          start: 'top 85%',
          end: 'top 40%',
          onEnter: function () {
            gsap.to([location, title, desc, link], {
              opacity: 1, y: 0, duration: 1.1, ease: 'power3.out', stagger: 0.12,
            });
          },
          onLeaveBack: function () {
            gsap.to([location, title, desc, link], {
              opacity: 0, y: 40, duration: 0.7, ease: 'power2.in', stagger: 0.06,
            });
          },
        });
      } else {
        gsap.from([location, title, desc, link], {
          opacity: 0, y: 40, duration: 1.2, ease: 'power3.out', stagger: 0.14, delay: 0.3,
        });
      }

      if (img) {
        gsap.to(img, {
          yPercent: -12, ease: 'none',
          scrollTrigger: {
            trigger: card, start: 'top bottom', end: 'bottom top', scrub: 1.8,
          },
        });

        ScrollTrigger.create({
          trigger: card, start: 'top 60%', end: 'top 20%', scrub: 1,
          onUpdate: function (self) {
            var scale = gsap.utils.mapRange(0, 1, 1.06, 1.0, self.progress);
            gsap.set(img, { scale: scale });
          },
        });
      }
    });
  }

  function initMobileAnimations(cards) {
    cards.forEach(function (card) {
      var inner = card.querySelector('.fp-card__inner');
      gsap.from(inner, {
        opacity: 0, y: 50, duration: 1.0, ease: 'power3.out',
        scrollTrigger: { trigger: card, start: 'top 88%' },
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFeaturedProjects);
  } else {
    initFeaturedProjects();
  }
})();
