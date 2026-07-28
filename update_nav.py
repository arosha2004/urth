import os
import glob

def update_nav(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Change Service to Services
    content = content.replace('<div button-text="" class="button-text">Service</div>', '<div button-text="" class="button-text">Services</div>')
    
    # Change Project to Projects
    content = content.replace('<div button-text="" class="button-text">Project</div>', '<div button-text="" class="button-text">Projects</div>')

    # Add Shop link if not exists
    if 'class="button-text">Shop</div>' not in content:
        # Find the project link block to clone it for shop
        project_link_start = content.find('<a href="index.html#project"')
        if project_link_start != -1:
            project_link_end = content.find('</a>', project_link_start) + 4
            
            shop_link = content[project_link_start:project_link_end]
            shop_link = shop_link.replace('index.html#project', '#')
            shop_link = shop_link.replace('<div button-text="" class="button-text">Projects</div>', '<div button-text="" class="button-text">Shop</div>')
            
            content = content[:project_link_end] + shop_link + content[project_link_end:]

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

def main():
    directory = 'c:\\xampp\\htdocs\\Urth\\urth\\urth_clone'
    html_files = glob.glob(os.path.join(directory, '*.html'))
    
    for html_file in html_files:
        update_nav(html_file)

if __name__ == "__main__":
    main()
