import os
import glob
import re

def fix_links_urth_clone(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # localhost replacements
    content = content.replace('http://localhost/urth/urth/contact.html', '../contact.html')
    
    # webflow replacements
    content = content.replace('https://urth-studio.webflow.io/#about', 'index.html#about')
    content = content.replace('https://urth-studio.webflow.io/#service', 'index.html#service')
    content = content.replace('https://urth-studio.webflow.io/#project', 'index.html#project')
    content = content.replace('https://urth-studio.webflow.io/#faq', 'index.html#faq')
    content = content.replace('https://urth-studio.webflow.io/#contact', '../contact.html')
    
    content = content.replace('https://urth-studio.webflow.io/401', '401.html')
    content = content.replace('https://urth-studio.webflow.io/404', '404.html')

    # absolute paths replacements
    # replace href="/" with href="../index.html"
    content = re.sub(r'href="/"', r'href="../index.html"', content)
    
    # replace href="/project/something" with href="something.html"
    content = re.sub(r'href="/project/([^"]+)"', r'href="\1.html"', content)
    
    # replace href="/service/something" with href="something.html"
    content = re.sub(r'href="/service/([^"]+)"', r'href="\1.html"', content)
    
    # replace href="/style-guide" with href="style-guide.html"
    content = re.sub(r'href="/style-guide"', r'href="style-guide.html"', content)
    
    # replace href="/licenses" with href="licenses.html"
    content = re.sub(r'href="/licenses"', r'href="licenses.html"', content)
    
    # replace href="/changelog" with href="changelog.html"
    content = re.sub(r'href="/changelog"', r'href="changelog.html"', content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

def fix_links_root(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # localhost replacements
    content = content.replace('http://localhost/urth/urth/contact.html', 'contact.html')
    
    # webflow replacements
    content = content.replace('https://urth-studio.webflow.io/#about', 'urth_clone/index.html#about')
    content = content.replace('https://urth-studio.webflow.io/#service', 'urth_clone/index.html#service')
    content = content.replace('https://urth-studio.webflow.io/#project', 'urth_clone/index.html#project')
    content = content.replace('https://urth-studio.webflow.io/#faq', 'urth_clone/index.html#faq')
    content = content.replace('https://urth-studio.webflow.io/#contact', 'contact.html')
    
    content = content.replace('https://urth-studio.webflow.io/401', 'urth_clone/401.html')
    content = content.replace('https://urth-studio.webflow.io/404', 'urth_clone/404.html')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

def main():
    root_dir = 'c:\\xampp\\htdocs\\Urth\\urth'
    clone_dir = os.path.join(root_dir, 'urth_clone')
    
    # fix files in urth_clone
    for html_file in glob.glob(os.path.join(clone_dir, '*.html')):
        fix_links_urth_clone(html_file)
        
    # fix files in root
    for html_file in glob.glob(os.path.join(root_dir, '*.html')):
        fix_links_root(html_file)

if __name__ == "__main__":
    main()
