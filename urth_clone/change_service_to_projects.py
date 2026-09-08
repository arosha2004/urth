import os
import re

count = 0
for r, d, files in os.walk('c:/xampp/htdocs/Urth - Copy/urth'):
    for f in files:
        if f.endswith('.html'):
            filepath = os.path.join(r, f)
            with open(filepath, 'rb') as file:
                content = file.read()
            
            try:
                text = content.decode('utf-8')
            except UnicodeDecodeError:
                text = content.decode('utf-16', errors='ignore')
            
            original_text = text
            
            if '<section id="cta"' in text:
                
                # Determine correct relative links for projects page
                if 'urth_clone' in filepath:
                    project_link = '../projects.php'
                else:
                    project_link = 'projects.php'
                
                # Replace the explore service button link
                text = re.sub(
                    r'href="[^"]*"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Explore Service)',
                    f'href="{project_link}"\\1',
                    text,
                    flags=re.IGNORECASE
                )

            if text != original_text:
                with open(filepath, 'w', encoding='utf-8') as file:
                    file.write(text)
                count += 1
                print(f'Updated {filepath}')

print(f'Total files updated: {count}')
