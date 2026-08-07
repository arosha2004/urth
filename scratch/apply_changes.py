import shutil
import os

html_path = r'c:\xampp\htdocs\Urth - Copy\urth\urth_clone\index.html'
backup_path = r'c:\xampp\htdocs\Urth - Copy\urth\urth_clone\index.html.bak'

# Backup original
shutil.copyfile(html_path, backup_path)

with open(r'c:\xampp\htdocs\Urth - Copy\urth\scratch\line206_new.txt', 'r', encoding='utf-8') as f:
    new_line206 = f.read()

# Make sure new_line206 doesn't end with extra newlines if original didn't
new_line206 = new_line206.rstrip('\n') + '\n'

with open(html_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# lines is 0-indexed, so line 206 is index 205
lines[205] = new_line206

with open(html_path, 'w', encoding='utf-8') as f:
    f.writelines(lines)
