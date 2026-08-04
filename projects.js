document.addEventListener('DOMContentLoaded', () => {
  const timelineItems = document.querySelectorAll('.timeline-item');
  const imageWrappers = document.querySelectorAll('.timeline-image-wrapper');

  if (timelineItems.length === 0 || imageWrappers.length === 0) return;

  // Options for IntersectionObserver
  // RootMargin set to trigger when item is near the middle of the viewport
  const options = {
    root: null,
    rootMargin: '-40% 0px -40% 0px',
    threshold: 0
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        // Remove active class from all items and images
        timelineItems.forEach(item => item.classList.remove('active'));
        imageWrappers.forEach(img => img.classList.remove('active'));

        // Add active class to the currently intersecting item
        const activeItem = entry.target;
        activeItem.classList.add('active');

        // Find and activate the corresponding image based on data-index
        const index = activeItem.getAttribute('data-index');
        const activeImage = document.querySelector(`.timeline-image-wrapper[data-index="${index}"]`);
        if (activeImage) {
          activeImage.classList.add('active');
        }
      }
    });
  }, options);

  // Observe all timeline items
  timelineItems.forEach(item => {
    observer.observe(item);
  });
  
  // Set initial state (first item active) just in case observer doesn't fire immediately
  if (!document.querySelector('.timeline-item.active')) {
    timelineItems[0].classList.add('active');
    imageWrappers[0].classList.add('active');
  }
});
