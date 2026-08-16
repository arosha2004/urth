import re
with open('c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html', 'r', encoding='utf-8') as f:
    content = f.read()

print(re.findall(r'<div class="category-tag">(.*?)</div>', content))
