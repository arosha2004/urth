import os

root_dir = r"c:\xampp\htdocs\Urth - Copy\urth"
clone_dir = os.path.join(root_dir, "urth_clone")

# Replace in root directory
for filename in os.listdir(root_dir):
    if filename.endswith(".html"):
        filepath = os.path.join(root_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # We also might have href="projects.html" or href="projects.html"
        new_content = content.replace('href="projects.html"', 'href="urth_clone/projectsoverall.html"')
        
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename}")

# Replace in clone directory
for filename in os.listdir(clone_dir):
    if filename.endswith(".html"):
        filepath = os.path.join(clone_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        new_content = content.replace('href="../projects.html"', 'href="projectsoverall.html"')
        
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated urth_clone/{filename}")

print("Done")
