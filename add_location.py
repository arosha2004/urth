import os

# 1. Update project_add.php
fpath = r"c:\xampp\htdocs\Urth - Copy\urth\admin\project_add.php"
with open(fpath, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    "$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));",
    "$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));\n    $location = $conn->real_escape_string(trim($_POST['location'] ?? ''));"
)
content = content.replace(
    "INSERT INTO projects (title, category, description, link_url, image1, image2, image3)",
    "INSERT INTO projects (title, category, location, description, link_url, image1, image2, image3)"
)
content = content.replace(
    "VALUES ('$title', '$category', '$description', '', '$image1_path', '', '')",
    "VALUES ('$title', '$category', '$location', '$description', '', '$image1_path', '', '')"
)
with open(fpath, 'w', encoding='utf-8') as f:
    f.write(content)


# 2. Update project_edit.php
fpath = r"c:\xampp\htdocs\Urth - Copy\urth\admin\project_edit.php"
with open(fpath, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    "$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));",
    "$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));\n    $location = $conn->real_escape_string(trim($_POST['location'] ?? ''));"
)
content = content.replace(
    "UPDATE projects SET title = '$title', category = '$category', description = '$description' WHERE id = $id",
    "UPDATE projects SET title = '$title', category = '$category', location = '$location', description = '$description' WHERE id = $id"
)
with open(fpath, 'w', encoding='utf-8') as f:
    f.write(content)


# 3. Update admin.js
fpath = r"c:\xampp\htdocs\Urth - Copy\urth\admin\assets\js\admin.js"
with open(fpath, 'r', encoding='utf-8') as f:
    content = f.read()

if "const location = btn.getAttribute('data-location');" not in content:
    content = content.replace(
        "const description = btn.getAttribute('data-description');",
        "const description = btn.getAttribute('data-description');\n      const location = btn.getAttribute('data-location');"
    )
    content = content.replace(
        "const descField = document.getElementById('edit_description');",
        "const locField = document.getElementById('edit_location');\n      if (locField) locField.value = location || '';\n\n      const descField = document.getElementById('edit_description');"
    )
with open(fpath, 'w', encoding='utf-8') as f:
    f.write(content)


# 4. Update index.php
fpath = r"c:\xampp\htdocs\Urth - Copy\urth\admin\index.php"
with open(fpath, 'r', encoding='utf-8') as f:
    content = f.read()

if 'data-location=' not in content:
    content = content.replace(
        'data-description="<?= htmlspecialchars($project[\'description\'] ?? \'\', ENT_QUOTES) ?>" \n',
        'data-location="<?= htmlspecialchars($project[\'location\'] ?? \'\', ENT_QUOTES) ?>"\n                                                    data-description="<?= htmlspecialchars($project[\'description\'] ?? \'\', ENT_QUOTES) ?>"\n'
    )
    content = content.replace(
        'data-description="<?= htmlspecialchars($project[\'description\'] ?? \'\', ENT_QUOTES) ?>" \r\n',
        'data-location="<?= htmlspecialchars($project[\'location\'] ?? \'\', ENT_QUOTES) ?>"\r\n                                                    data-description="<?= htmlspecialchars($project[\'description\'] ?? \'\', ENT_QUOTES) ?>"\r\n'
    )
    # Just in case whitespace is different:
    content = content.replace(
        'data-description="<?= htmlspecialchars($project[\'description\'] ?? \'\', ENT_QUOTES) ?>"',
        'data-location="<?= htmlspecialchars($project[\'location\'] ?? \'\', ENT_QUOTES) ?>"\n                                                    data-description="<?= htmlspecialchars($project[\'description\'] ?? \'\', ENT_QUOTES) ?>"'
    )

add_loc_html = """
                <div class="form-group">
                    <label for="add_location" class="form-label">Location</label>
                    <input type="text" id="add_location" name="location" class="form-control" placeholder="e.g. Sri Lanka">
                </div>
"""
if "add_location" not in content:
    content = content.replace(
        '<div class="form-group">\n                    <label for="add_description" class="form-label">Description</label>',
        add_loc_html + '\n                <div class="form-group">\n                    <label for="add_description" class="form-label">Description</label>'
    )
    
edit_loc_html = """
                <div class="form-group">
                    <label for="edit_location" class="form-label">Location</label>
                    <input type="text" id="edit_location" name="location" class="form-control" placeholder="e.g. Sri Lanka">
                </div>
"""
if "edit_location" not in content:
    content = content.replace(
        '<div class="form-group">\n                    <label for="edit_description" class="form-label">Description</label>',
        edit_loc_html + '\n                <div class="form-group">\n                    <label for="edit_description" class="form-label">Description</label>'
    )

with open(fpath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Done")
