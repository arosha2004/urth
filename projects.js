document.addEventListener('DOMContentLoaded', () => {
  const timelineRows = document.querySelectorAll('.timeline-row');

  if (timelineRows.length === 0) return;

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
        // Remove active class from all rows
        timelineRows.forEach(row => row.classList.remove('active'));

        // Add active class to the currently intersecting row
        entry.target.classList.add('active');
      }
    });
  }, options);

  // Observe all timeline rows
  timelineRows.forEach(row => {
    observer.observe(row);
  });
  
  // Set initial state (first item active) just in case observer doesn't fire immediately
  if (!document.querySelector('.timeline-row.active')) {
    timelineRows[0].classList.add('active');
  }
});
