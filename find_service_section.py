import re

with open('c:/xampp/htdocs/Urth - Copy/urth/projects.php', 'r', encoding='utf-8') as f:
    html = f.read()

# Find the section with id="service"
match = re.search(r'(<[^>]*id="service"[^>]*>.*?)</section>', html, re.DOTALL | re.IGNORECASE)
if match:
    print("Found id='service' element:")
    print(match.group(1)[:500])
else:
    print("No element with id='service' found.")

# Find the section that contains "OUR SERVICE"
matches = re.finditer(r'(<section[^>]*>)(.*?OUR\s*SERVICE.*?</section>)', html, re.DOTALL | re.IGNORECASE)
for m in matches:
    print("\nFound section containing 'OUR SERVICE':")
    print(m.group(1))
    print(m.group(2)[:200])
