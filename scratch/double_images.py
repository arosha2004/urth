import re

with open('projects.html', 'r', encoding='utf-8') as f:
    content = f.read()

def double_images(match):
    inner_html = match.group(1)
    return f'<div class="img-grid">{inner_html}{inner_html}</div>'

new_content = re.sub(r'<div class="img-grid">(.*?)</div>', double_images, content, flags=re.DOTALL)

with open('projects.html', 'w', encoding='utf-8') as f:
    f.write(new_content)
