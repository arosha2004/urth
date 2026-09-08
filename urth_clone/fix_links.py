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
            
            # Simple string replacements instead of regex, to be safe.
            # But the 'href' might be '#contact' or something else, but it was confirmed as href="#contact".
            
            if '<section id="cta"' in text:
                print(f"Found CTA section in {filepath}")
                
                # Replace #contact with ../contact.html or contact.html depending on dir
                if 'urth_clone' in filepath:
                    contact_link = '../contact.html'
                    service_link = 'index_pretty.html#service'
                else:
                    contact_link = 'contact.html'
                    service_link = 'index.html#service'
                
                # Replace the contact button link
                text = re.sub(
                    r'href="[^"]*"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Book a Consultation)',
                    f'href="{contact_link}"\\1',
                    text,
                    flags=re.IGNORECASE
                )
                
                # Replace the service button link
                text = re.sub(
                    r'href="[^"]*"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Explore Service)',
                    f'href="{service_link}"\\1',
                    text,
                    flags=re.IGNORECASE
                )

            if text != original_text:
                with open(filepath, 'w', encoding='utf-8') as file:
                    file.write(text)
                count += 1
                print(f'Updated {filepath}')

print(f'Total files updated: {count}')
