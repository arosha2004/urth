import re
html = open('urth_clone/about.html', encoding='utf-8').read()
css_links = re.findall(r'<link[^>]*href=["\']([^"\'>]*\.css)["\'][^>]*>', html, re.IGNORECASE)
print(css_links)
