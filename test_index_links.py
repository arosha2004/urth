import urllib.request
import re
try:
    html = urllib.request.urlopen('https://urthspaces.com/urth_clone/index.html').read().decode('utf-8', errors='ignore')
    print('index.html links:', re.findall(r'<link[^>]*href=["\']([^"\'>]*\.css)["\'][^>]*>', html, re.IGNORECASE))
except Exception as e:
    print('Error:', e)
