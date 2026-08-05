import re

with open('projects.html', 'r', encoding='utf-8') as f:
    html = f.read()

def keep_three_images(match):
    inner_html = match.group(1)
    imgs = re.findall(r'<img[^>]*>', inner_html)
    # Keep only the first 3 images
    kept_imgs = '\n            '.join(imgs[:3])
    return f'<div class="img-grid">\n            {kept_imgs}\n          </div>'

new_html = re.sub(r'<div class="img-grid">(.*?)</div>', keep_three_images, html, flags=re.DOTALL)

with open('projects.html', 'w', encoding='utf-8') as f:
    f.write(new_html)

print("Reduced to 3 images per grid.")
