import os
import glob
import re

def update_urth_clone_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # The new standard navbar content for urth_clone pages
    new_nav = '''<nav role="navigation" id="w-node-fac9006d-035f-3b6d-9ce9-dc43dfbaef45-dfbaef3d" class="nav-menu w-variant-4ef2cf08-a1c0-4642-f6d8-de240bd9728e w-nav-menu">
<div class="nav-menu-list" style="display: flex; align-items: center; justify-content: center; gap: 30px;">
<a href="../index.html" style="color: white; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 600; letter-spacing: 2px; font-family: sans-serif;">HOME</a>
<a href="index.html" style="color: white; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 600; letter-spacing: 2px; font-family: sans-serif;">ABOUT</a>
<a href="index.html#service" style="color: white; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 600; letter-spacing: 2px; font-family: sans-serif;">SERVICES</a>
<a href="../projects.html" style="color: white; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 600; letter-spacing: 2px; font-family: sans-serif;">PROJECTS</a>
<a href="../contact.html" style="color: white; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 600; letter-spacing: 2px; font-family: sans-serif;">CONTACT</a>
<a href="#" style="color: white; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 600; letter-spacing: 2px; font-family: sans-serif;">SHOP</a>
</div>
</nav>
<div class="menu-button w-nav-button"><div class="w-icon-nav-menu"></div></div>'''

    # Pattern to match everything from <nav role="navigation"... to the end of the contact button div
    pattern = re.compile(r'<nav role="navigation" id="w-node-fac9006d-035f-3b6d-9ce9-dc43dfbaef45-dfbaef3d".*?<div class="menu-button w-nav-button"><div class="w-icon-nav-menu"></div></div>.*?<div id="w-node-fac9006d-035f-3b6d-9ce9-dc43dfbaef51-dfbaef3d" class="navbar-button">.*?</a></div>', re.DOTALL)
    
    if pattern.search(content):
        content = pattern.sub(new_nav, content)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")
    else:
        # Maybe it doesn't have the navbar-button, just try to replace the nav part
        nav_pattern = re.compile(r'<nav role="navigation" id="w-node-fac9006d-035f-3b6d-9ce9-dc43dfbaef45-dfbaef3d".*?</nav>\s*<div class="menu-button w-nav-button"><div class="w-icon-nav-menu"></div></div>', re.DOTALL)
        if nav_pattern.search(content):
            content = nav_pattern.sub(new_nav, content)
            # Remove navbar-button if exists anywhere else
            contact_pattern = re.compile(r'<div id="w-node-fac9006d-035f-3b6d-9ce9-dc43dfbaef51-dfbaef3d" class="navbar-button">.*?</a></div>', re.DOTALL)
            content = contact_pattern.sub('', content)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated (fallback) {filepath}")
        else:
            print(f"No match in {filepath}")

def update_projects_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    new_nav = '''<nav class="center-nav">
        <a href="index.html">HOME</a>
        <a href="urth_clone/index.html">ABOUT</a>
        <a href="urth_clone/index.html#service">SERVICES</a>
        <a href="projects.html" class="active">PROJECTS</a>
        <a href="contact.html">CONTACT</a>
        <a href="#">SHOP</a>
      </nav>'''
      
    pattern = re.compile(r'<nav class="center-nav">.*?</nav>', re.DOTALL)
    
    if pattern.search(content):
        content = pattern.sub(new_nav, content)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")
    else:
        print(f"No match in {filepath}")

def update_home_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Actually the home footer nav already has exactly these links! Let's check
    pass

if __name__ == '__main__':
    urth_clone_files = glob.glob('c:/xampp/htdocs/Urth/urth/urth_clone/*.html')
    for f in urth_clone_files:
        update_urth_clone_html(f)
        
    projects_file = 'c:/xampp/htdocs/Urth/urth/projects.html'
    if os.path.exists(projects_file):
        update_projects_html(projects_file)
