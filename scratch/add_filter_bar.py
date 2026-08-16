import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Add styles for the category bar
style_block = """
    /* Filter Bar Styles */
    .filter-bar-container {
        width: 100%;
        margin-bottom: 40px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        border-bottom: 1px solid #eee;
    }
    .filter-bar-container::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }
    .filter-bar {
        display: flex;
        gap: 32px;
        padding-bottom: 1px;
        min-width: max-content;
    }
    .filter-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 4px;
        color: #666;
        font-family: 'Montserrat', sans-serif;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        position: relative;
        transition: color 0.3s ease;
        white-space: nowrap;
    }
    .filter-tab:hover {
        color: #000;
    }
    .filter-tab.active {
        color: #000;
    }
    .filter-tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #000;
    }
    .filter-tab svg {
        width: 18px;
        height: 18px;
        fill: currentColor;
    }
    
    /* Project Item Transition */
    .project-item {
        transition: opacity 0.4s ease, transform 0.4s ease;
    }
"""

content = content.replace('</style>', style_block + '</style>')

filter_html = """
<div class="filter-bar-container">
    <div class="filter-bar" id="project-filters">
        <div class="filter-tab active" data-filter="All">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            All
        </div>
        <div class="filter-tab" data-filter="Architectural Design">
            <svg viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/></svg>
            Architectural Design
        </div>
        <div class="filter-tab" data-filter="Interior Designing">
            <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            Interior Designing
        </div>
        <div class="filter-tab" data-filter="Urban Planning">
            <svg viewBox="0 0 24 24"><path d="M15 11V5l-3-3-3 3v2H3v14h18V11h-6zm-8 8H5v-2h2v2zm0-4H5v-2h2v2zm0-4H5V9h2v2zm6 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V9h2v2zm0-4h-2V5h2v2zm6 12h-2v-2h2v2zm0-4h-2v-2h2v2z"/></svg>
            Urban Planning
        </div>
        <div class="filter-tab" data-filter="Landscape Design">
            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            Landscape Design
        </div>
        <div class="filter-tab" data-filter="Custom Furnitures">
            <svg viewBox="0 0 24 24"><path d="M20 10V7c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v3c-1.1 0-2 .9-2 2v5h1.33L4 19h1l.67-2h12.67l.66 2h1l.67-2H22v-5c0-1.1-.9-2-2-2zm-9 0H6V7h5v3zm7 0h-5V7h5v3z"/></svg>
            Custom Furnitures
        </div>
        <div class="filter-tab" data-filter="Spatial Redesign">
            <svg viewBox="0 0 24 24"><path d="M11.99 18.54l-7.37-5.73L3 14.07l9 7 9-7-1.63-1.27-7.38 5.74zM12 16l7.36-5.73L21 9l-9-7-9 7 1.63 1.27L12 16z"/></svg>
            Spatial Redesign
        </div>
        <div class="filter-tab" data-filter="Ideas Hub">
            <svg viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6C7.8 12.16 7 10.63 7 9c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.63-.8 3.16-2.15 4.1z"/></svg>
            Ideas Hub
        </div>
    </div>
</div>
"""

content = re.sub(r'(<div class="vertical-headline project-headline">.*?</div>)(<div class="w-dyn-list">)', r'\1' + filter_html + r'\2', content)

js_code = """
<script>
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
</script>
"""

content = content.replace('</body>', js_code + '</body>')

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Filter bar added successfully!")
