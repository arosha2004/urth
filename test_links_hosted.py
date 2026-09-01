import re
print('hosted_about.html:', re.findall(r'<link[^>]*href=["\']([^"\'>]*\.css)["\'][^>]*>', open('hosted_about.html', encoding='utf-8', errors='ignore').read(), re.IGNORECASE))
