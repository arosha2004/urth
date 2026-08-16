import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Remove transform from CSS
content = content.replace('transition: opacity 0.4s ease, transform 0.4s ease;', 'transition: opacity 0.4s ease;')

# Update the JavaScript
old_js = """<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const projectItems = document.querySelectorAll('.project-item');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');

            projectItems.forEach(item => {
                const tagElement = item.querySelector('.category-tag');
                if (tagElement) {
                    const category = tagElement.textContent.trim();
                    if (filterValue === 'All' || category === filterValue) {
                        item.style.display = 'block';
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, 50);
                    } else {
                        item.style.opacity = '0';
                        item.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 400);
                    }
                }
            });
        });
    });
});
</script>"""

new_js = """<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const projectItems = document.querySelectorAll('.project-item');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');
            
            let visibleIndex = 0;

            projectItems.forEach(item => {
                const tagElement = item.querySelector('.category-tag');
                if (tagElement) {
                    const category = tagElement.textContent.trim();
                    if (filterValue === 'All' || category === filterValue) {
                        item.style.display = 'block';
                        // Fix the sticky top value dynamically based on visible index
                        const spacing = 40; // var(--_spacing---semi-large) approximate fallback
                        const topValue = `calc(var(--_spacing---semi-large, 40px) + ${visibleIndex * 30}px)`;
                        item.style.top = topValue;
                        item.style.setProperty('top', topValue, 'important');
                        
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'none';
                        }, 50);
                        visibleIndex++;
                    } else {
                        item.style.opacity = '0';
                        item.style.transform = 'none';
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 400);
                    }
                }
            });
        });
    });
});
</script>"""

content = content.replace(old_js, new_js)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Fix applied successfully!")
