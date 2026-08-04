import os
import re

directory = r"c:\xampp\htdocs\Urth\urth"

for root, dirs, files in os.walk(directory):
    for file in files:
        if file.endswith(".html"):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Regex to match <a href="...">SERVICES</a> with optional spaces around it
            # We specifically want to remove it inside the nav bar, but removing all of them is fine too
            # Let's target: \s*<a[^>]*>SERVICES</a>
            new_content, count = re.subn(r'\s*<a[^>]*>SERVICES</a>', '', content, flags=re.IGNORECASE)
            
            if count > 0:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Modified {filepath} - removed {count} occurrences")

