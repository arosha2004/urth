// Mobile Navigation Logic
document.addEventListener('DOMContentLoaded', () => {
    const overlays = document.querySelectorAll('.mobile-nav-overlay');
    const toggles = document.querySelectorAll('.mobile-nav-toggle');

    if (toggles.length > 0 && overlays.length > 0) {
        // Typically there's only one overlay per page
        const overlay = overlays[0];

        toggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const isOpen = overlay.classList.contains('active');

                if (isOpen) {
                    overlay.classList.remove('active');
                    toggles.forEach(t => t.classList.remove('open'));
                    document.body.style.overflow = '';
                } else {
                    overlay.classList.add('active');
                    toggles.forEach(t => t.classList.add('open'));
                    document.body.style.overflow = 'hidden';
                }
            });
        });
    }
});

// Sticky Navbar Logic
document.addEventListener("scroll", function () {
    // Disable sticky navbar on projects.php
    if (window.location.pathname.includes('projects.php')) return;

    var nav = document.querySelector('.hero-top-nav');
    if (!nav) return;

    if (window.scrollY > 10) {
        if (!nav.classList.contains('sticky')) {
            nav.classList.add('sticky');
            // Allow a small delay before adding the scrolled class to trigger the CSS transition
            setTimeout(() => {
                if (window.scrollY > 10) {
                    nav.classList.add('scrolled');
                }
            }, 20);
        }
    } else {
        nav.classList.remove('sticky', 'scrolled');
    }
});
