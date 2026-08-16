import re
with open('c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html', 'r', encoding='utf-8') as f:
    content = f.read()

matches = re.findall(r'<div class="project-image">.*?</figure>|<div class="project-image">.*?</div>', content)
print(matches[:2])
