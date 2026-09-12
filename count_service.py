import re

with open('c:/xampp/htdocs/Urth - Copy/urth/projects.php', 'r', encoding='utf-8') as f:
    html = f.read()

count = html.count('id="service"')
print(f"Count of id='service' in projects.php: {count}")
