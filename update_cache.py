content = open('urth_clone/about.html', encoding='utf-8').read()
content = content.replace('href="css/urth-studio.webflow.shared.c7afc3d60.css"', 'href="css/urth-studio.webflow.shared.c7afc3d60.css?v=2"')
open('urth_clone/about.html', 'w', encoding='utf-8').write(content)
