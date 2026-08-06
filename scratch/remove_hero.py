import re

with open(r'c:\xampp\htdocs\Urth\urth\urth_clone\about2.html', 'r', encoding='utf-8') as f:
    content = f.read()

new_content = re.sub(r'<header class="hero-section">.*?</header>', '', content, flags=re.DOTALL)

with open(r'c:\xampp\htdocs\Urth\urth\urth_clone\about2.html', 'w', encoding='utf-8') as f:
    f.write(new_content)
