import os
import re

count = 0
for r, d, files in os.walk('.'):
    for f in files:
        if f.endswith('.html'):
            filepath = os.path.join(r, f)
            with open(filepath, 'rb') as file:
                content = file.read()
            
            try:
                text = content.decode('utf-8')
            except UnicodeDecodeError:
                text = content.decode('utf-16', errors='ignore')
            
            # replace contact link
            new_text = re.sub(r'href=\"#contact\"([^>]*>.*?Book a Consultation)', r'href=\"../contact.html\"\1', text, flags=re.DOTALL)
            
            # replace service link (if not already linking to a service page or section)
            # The user wants "Explore Service" to point to "Service section".
            # The service section in index_pretty.html has id="service".
            # Let's link it to `index.html#service`. Or maybe `../index.html#service`.
            # Wait, index_pretty.html is what they might be using. Let's use `index_pretty.html#service` for local files or `../index.html#service` if it's the main index.
            # I will use `index.html#service` since `architectural-design.html` has `<a href="index.html#service" ...><div class="button-text">More Service</div>`.
            new_text = re.sub(r'href=\"#service\"([^>]*>.*?Explore Service)', r'href=\"index.html#service\"\1', new_text, flags=re.DOTALL)
            
            if new_text != text:
                with open(filepath, 'w', encoding='utf-8') as file:
                    file.write(new_text)
                count += 1
                print(f'Updated {filepath}')

print(f'Total files updated: {count}')
