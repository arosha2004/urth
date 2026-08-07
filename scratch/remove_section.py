import re

with open('scratch/line206.txt', 'r', encoding='utf-16') as f:
    content = f.read()

new_content = re.sub(r'<section id="project" class="section">.*?</section>', '', content, flags=re.DOTALL)

with open('scratch/line206_new.txt', 'w', encoding='utf-8') as f:
    f.write(new_content)
