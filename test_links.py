import re
print('about.html:', re.findall(r'<link[^>]*href=["\']([^"\'>]*\.css)["\'][^>]*>', open('urth_clone/about.html', encoding='utf-8').read(), re.IGNORECASE))
print('index_pretty.html:', re.findall(r'<link[^>]*href=["\']([^"\'>]*\.css)["\'][^>]*>', open('urth_clone/index_pretty.html', encoding='utf-8').read(), re.IGNORECASE))
