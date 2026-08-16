import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace block with empty string to revert to CSS stylesheet value
content = content.replace("item.style.display = 'block';", "item.style.display = '';")

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Restored original display value.")
