document.addEventListener('DOMContentLoaded', () => {
  const leftItems = document.querySelectorAll('.timeline-item');
  const rightImages = document.querySelectorAll('.timeline-images');
  const rightContainer = document.querySelector('.timeline-right');

  // If we don't have the elements, return
  if (!leftItems.length || !rightImages.length || !rightContainer) return;

  // Intersection Observer for images
  const observerOptions = {
    root: rightContainer,
    rootMargin: '-30% 0px -30% 0px', // Trigger when image row is near the vertical center
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
  });

  // Setup click events on left items
  leftItems.forEach(item => {
    item.addEventListener('click', (e) => {
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
        
        // Calculate position to center the image grid
        const containerHeight = rightContainer.clientHeight;
        const targetHeight = targetImage.clientHeight;
        const targetOffset = targetImage.offsetTop;
        
        rightContainer.scrollTo({
          top: targetOffset - (containerHeight / 2) + (targetHeight / 2),
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
