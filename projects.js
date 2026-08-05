document.addEventListener('DOMContentLoaded', () => {
  const leftItems = document.querySelectorAll('.timeline-item');
  const rightImages = document.querySelectorAll('.timeline-images');
  const rightContainer = document.querySelector('.timeline-right');

  // If we don't have the elements, return
  if (!leftItems.length || !rightImages.length || !rightContainer) return;

  // Intersection Observer for images
  const observerOptions = {
    root: rightContainer,
    rootMargin: '-10% 0px -80% 0px', // Trigger when image row is near the top
    threshold: 0
  };

  let isScrollingByClick = false;
  let scrollTimeout;

  const observer = new IntersectionObserver((entries) => {
    if (isScrollingByClick) return; // Skip updating left menu if we are auto-scrolling

    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const index = entry.target.getAttribute('data-index');
        
        // Remove active from all
        leftItems.forEach(item => item.classList.remove('active'));
        rightImages.forEach(img => img.classList.remove('active'));

        // Add active to current
        entry.target.classList.add('active');
        const correspondingItem = document.querySelector(`.timeline-item[data-index="${index}"]`);
        if (correspondingItem) {
          correspondingItem.classList.add('active');
        }
      }
    });
  }, observerOptions);

  rightImages.forEach(img => {
    observer.observe(img);

    img.style.cursor = 'pointer'; // Make it look clickable

    img.addEventListener('mouseenter', () => {
      const index = img.getAttribute('data-index');
      const item = document.querySelector(`.timeline-item[data-index="${index}"]`);
      if (item) item.classList.add('hover');
    });

    img.addEventListener('mouseleave', () => {
      const index = img.getAttribute('data-index');
      const item = document.querySelector(`.timeline-item[data-index="${index}"]`);
      if (item) item.classList.remove('hover');
    });

    img.addEventListener('click', () => {
      const index = img.getAttribute('data-index');
      const item = document.querySelector(`.timeline-item[data-index="${index}"] .timeline-read-btn`);
      if (item && item.href) {
        window.location.href = item.href;
      }
    });
  });

  // Setup click events on left items
  leftItems.forEach(item => {
    item.addEventListener('click', (e) => {
      if (e.target.classList.contains('timeline-read-btn')) {
        // If clicking the read button, let it navigate
        return;
      }
      
      e.preventDefault();
      
      const index = item.getAttribute('data-index');
      const targetImage = document.querySelector(`.timeline-images[data-index="${index}"]`);
      
      if (targetImage && rightContainer) {
        isScrollingByClick = true;
        clearTimeout(scrollTimeout);
        
        // Remove active from all
        leftItems.forEach(left => left.classList.remove('active'));
        rightImages.forEach(right => right.classList.remove('active'));
        
        // Set new active immediately for snappiness
        item.classList.add('active');
        targetImage.classList.add('active');
        
        // Calculate position to align the image grid to the top (including top padding)
        const targetOffset = targetImage.offsetTop;
        
        rightContainer.scrollTo({
          top: targetOffset - 40, // subtract the 40px top padding
          behavior: 'smooth'
        });

        // Resume intersection observer after scrolling is likely finished
        scrollTimeout = setTimeout(() => {
          isScrollingByClick = false;
        }, 800);
      }
    });
  });

  // Set the first item as active initially to scroll it to middle if not already
  if (rightImages[0]) {
    // Manually trigger click on first item after a tiny delay
    setTimeout(() => {
      leftItems[0].click();
    }, 100);
  }
});
