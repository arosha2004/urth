with open('c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html', 'r', encoding='utf-8') as f:
    content = f.read()

import re
print("--- START ---")
match = re.search(r'<div class="vertical-headline project-headline">.*?<div class="w-dyn-list">', content, re.DOTALL)
if match:
    print(match.group(0))
else:
    print("Not found")
print("--- END ---")
