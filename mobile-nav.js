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
