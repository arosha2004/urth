import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace CSS
old_css = """    /* Filter Bar Styles */
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
    }"""

new_css = """    /* Filter Bar Styles */
    .filter-bar-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
        margin-bottom: 40px;
    }
    .filter-bar-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        scroll-behavior: smooth;
    }
    .filter-bar-container::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }
    .filter-bar-wrapper::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 1px;
        background-color: #eee;
        z-index: 0;
    }
    .filter-bar {
        display: flex;
        gap: 32px;
        padding: 0 16px;
        padding-bottom: 1px;
        min-width: max-content;
        position: relative;
        z-index: 1;
    }
    .filter-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: #fff;
        border: 1px solid #eee;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        color: #333;
        transition: opacity 0.3s ease, visibility 0.3s ease, background 0.3s ease;
    }
    .filter-arrow svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
    }
    .filter-arrow:hover {
        background: #f0f0f0;
    }
    .filter-arrow-left {
        left: -18px;
    }
    .filter-arrow-right {
        right: -18px;
    }
    .filter-arrow.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }"""

content = content.replace(old_css, new_css)

# Replace HTML structure
old_html_start = """<div class="filter-bar-container">
    <div class="filter-bar" id="project-filters">"""

new_html_start = """<div class="filter-bar-wrapper">
    <button class="filter-arrow filter-arrow-left hidden" aria-label="Scroll left">
        <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
    </button>
    <div class="filter-bar-container">
        <div class="filter-bar" id="project-filters">"""

content = content.replace(old_html_start, new_html_start)

# Finding the end of the filter bar is trickier since we have SVGs inside.
# We know the last tab is Ideas Hub:
old_html_end = """            Ideas Hub
        </div>
    </div>
</div>"""

new_html_end = """            Ideas Hub
        </div>
    </div>
    <button class="filter-arrow filter-arrow-right hidden" aria-label="Scroll right">
        <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    </button>
</div>"""

content = content.replace(old_html_end, new_html_end)


# Add JS code just inside the existing DOMContentLoaded listener
js_addition = """
    // Arrow logic
    const container = document.querySelector('.filter-bar-container');
    const leftArrow = document.querySelector('.filter-arrow-left');
    const rightArrow = document.querySelector('.filter-arrow-right');

    function updateArrows() {
        if (!container || !leftArrow || !rightArrow) return;
        
        if (container.scrollLeft <= 10) {
            leftArrow.classList.add('hidden');
        } else {
            leftArrow.classList.remove('hidden');
        }
        
        // Use Math.ceil to prevent fractional pixel issues
        if (Math.ceil(container.scrollLeft) >= container.scrollWidth - container.clientWidth - 10) {
            rightArrow.classList.add('hidden');
        } else {
            rightArrow.classList.remove('hidden');
        }
    }

    if (container && leftArrow && rightArrow) {
        // Run after a tiny delay to ensure fonts/layout are loaded
        setTimeout(updateArrows, 100);
        
        container.addEventListener('scroll', updateArrows);
        window.addEventListener('resize', updateArrows);
        
        leftArrow.addEventListener('click', () => {
            container.scrollBy({ left: -250, behavior: 'smooth' });
        });
        
        rightArrow.addEventListener('click', () => {
            container.scrollBy({ left: 250, behavior: 'smooth' });
        });
    }
"""

content = content.replace('const filterTabs = document.querySelectorAll(\'.filter-tab\');', js_addition + '\n    const filterTabs = document.querySelectorAll(\'.filter-tab\');')

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Added arrows successfully!")
