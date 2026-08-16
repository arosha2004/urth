import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Make sure transition is completely gone to avoid glitches
content = content.replace('transition: opacity 0.4s ease;', '')
content = content.replace('transition: opacity 0.4s ease, transform 0.4s ease;', '')

# Replace old JS with robust, instant JS
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
                        item.style.opacity = '1';
                        
                        const topValue = `calc(var(--_spacing---semi-large, 40px) + ${visibleIndex * 30}px)`;
                        item.style.setProperty('top', topValue, 'important');
                        
                        visibleIndex++;
                    } else {
                        item.style.display = 'none';
                        item.style.opacity = '0';
                    }
                }
            });
        });
    });
});
</script>"""

# Using regex to replace everything between <script> and </script> at the end of the file
# Specifically the filter logic script
content = re.sub(r'<script>\s*document\.addEventListener\("DOMContentLoaded", function\(\) {\s*const filterTabs.*?</script>', new_js, content, flags=re.DOTALL)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Robust script added")
