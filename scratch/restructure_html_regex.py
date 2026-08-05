import re

with open('projects.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Extract all timeline-row items
row_pattern = re.compile(r'<div class="timeline-row">\s*(<div class="timeline-item">.*?</div>)\s*(<div class="timeline-images">.*?</div>)\s*</div>', re.DOTALL)
rows = row_pattern.findall(html)

if rows:
    left_col = '<div class="timeline-left">\n'
    right_col = '<div class="timeline-right">\n'
    
    for i, (item, images) in enumerate(rows):
        # Insert data-index
        item = item.replace('<div class="timeline-item">', f'<div class="timeline-item" data-index="{i}">')
        images = images.replace('<div class="timeline-images">', f'<div class="timeline-images" data-index="{i}">')
        left_col += item + '\n'
        right_col += images + '\n'
        
    left_col += '</div>\n'
    right_col += '</div>\n'
    
    # Replace the container content
    new_html = re.sub(
        r'<div class="timeline-container">.*?</div>\s*</main>',
        f'<div class="timeline-container">\n{left_col}{right_col}</div>\n  </main>',
        html,
        flags=re.DOTALL
    )
    
    with open('projects.html', 'w', encoding='utf-8') as f:
        f.write(new_html)
    print("Successfully restructured HTML.")
else:
    print("Could not find timeline rows.")
