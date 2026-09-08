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
            
            # Clean up the literal backslash mistake from the earlier script
            text = text.replace(r'href=\"../contact.html\"', 'href="../contact.html"')
            text = text.replace(r'href=\"index.html#service\"', 'href="index.html#service"')
            text = text.replace(r'href=\"index_pretty.html#service\"', 'href="index_pretty.html#service"')
            
            # Now, for files that still have #contact and #service in the CTA
            if 'Book a Consultation' in text or 'Explore Service' in text:
                
                # Determine correct relative links
                if 'urth_clone' in filepath:
                    contact_link = '../contact.html'
                    service_link = 'index_pretty.html#service'
                else:
                    contact_link = 'contact.html'
                    service_link = 'index.html#service'
                
                # Replace #contact with contact_link for "Book a Consultation" button
                text = re.sub(
                    r'href="#contact"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Book a Consultation)',
                    f'href="{contact_link}"\\1',
                    text,
                    flags=re.IGNORECASE
                )
                
                # Replace #service with service_link for "Explore Service" button
                text = re.sub(
                    r'href="#service"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Explore Service)',
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
