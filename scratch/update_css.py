import re

with open('projects.css', 'r', encoding='utf-8') as f:
    css = f.read()

# 1. Update .projects-timeline
# It's already height: 100vh; overflow: hidden; wait, no, I didn't add overflow: hidden.
css = re.sub(
    r'\.projects-timeline \{.*?\}',
    '.projects-timeline {\n  width: 100%;\n  height: 100vh;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  position: relative;\n  background-color: #fff;\n  color: #000;\n  overflow: hidden;\n}',
    css,
    flags=re.DOTALL
)

# 2. Update .timeline-container
css = re.sub(
    r'\.timeline-container \{.*?\}',
    '.timeline-container {\n  width: 100%;\n  height: 100%;\n  display: flex;\n  flex-direction: row;\n  position: relative;\n}',
    css,
    flags=re.DOTALL
)

# 3. Replace .timeline-row and .timeline-item with new layout
row_item_replacement = """
.timeline-left {
  width: 35%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  z-index: 2;
  background: #fff;
}

.timeline-right {
  width: 65%;
  height: 100%;
  overflow-y: auto;
  position: relative;
  padding: 50vh 40px; /* Padding to allow scrolling first and last items to center */
  box-sizing: border-box;
  scroll-behavior: smooth;
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
.timeline-right::-webkit-scrollbar {
  display: none;
}

.timeline-item {
  width: 100%;
  height: 70px;
  padding: 0 10%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-sizing: border-box;
  transition: background-color 0.3s ease;
  cursor: pointer;
}

.timeline-item:hover,
.timeline-item.active {
  background-color: #f7f7f7;
}
"""
css = re.sub(r'/\* --- Row Layout --- \*/.*\.timeline-row:hover \.timeline-item,\s*\.timeline-row\.active \.timeline-item \{\s*background-color: #f7f7f7;\s*\}', row_item_replacement, css, flags=re.DOTALL)

# 4. Replace .timeline-images block
images_replacement = """
/* Right Column: Images Grid */
.timeline-images {
  width: 100%;
  margin-bottom: 20px;
  opacity: 1;
  visibility: visible;
}

.timeline-images:last-child {
  margin-bottom: 0;
}

.img-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
  width: 100%;
}

.img-grid img {
  width: 100%;
  height: 220px;
  object-fit: cover;
  object-position: center;
  filter: grayscale(100%);
  transition: filter 0.5s ease;
}

.timeline-images.active .img-grid img,
.timeline-images:hover .img-grid img {
  filter: grayscale(0%);
}
"""

css = re.sub(r'/\* Right Column: Images Grid \*/.*\.img-grid img \{.*\}', images_replacement, css, flags=re.DOTALL)

# Clean up .timeline-row hovers that might remain
css = re.sub(r'\.timeline-row:hover \.timeline-images,\s*\.timeline-row\.active \.timeline-images \{.*?\}', '', css, flags=re.DOTALL)

with open('projects.css', 'w', encoding='utf-8') as f:
    f.write(css)

print("projects.css updated")
