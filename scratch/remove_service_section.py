import re

def remove_service_section(file_path):
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()

    # The section starts with <section id="service" class="section subtle-grey">
    # and ends where the next section begins, e.g. <section id="why" class="section">
    # We will use re.sub to remove it.
    
    # Check if the section exists
    if '<section id="service"' not in content:
        print(f"Service section not found in {file_path}")
        return

    # Replace it
    new_content = re.sub(r'<section id="service" class="section subtle-grey">.*?</section>(<section id="why" class="section">)', r'\1', content, flags=re.DOTALL)
    
    if new_content != content:
        with open(file_path, "w", encoding="utf-8") as f:
            f.write(new_content)
        print(f"Successfully removed service section from {file_path}")
    else:
        print(f"Could not match the regex for {file_path}")

if __name__ == "__main__":
    remove_service_section(r"c:\xampp\htdocs\Urth - Copy\urth\urth_clone\index.html")
