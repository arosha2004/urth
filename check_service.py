import re

with open('c:/xampp/htdocs/Urth - Copy/urth/projects.php', 'r', encoding='utf-8') as f:
    html = f.read()

match = re.search(r'(<section[^>]*id="service"[^>]*>.*?)</section>', html, re.DOTALL | re.IGNORECASE)
if match:
    print(match.group(1)[:2500])
else:
    print("Not found")
