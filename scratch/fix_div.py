import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Fix missing closing tag
old_end = """        </div>
    </div>
    <button class="filter-arrow filter-arrow-right hidden" aria-label="Scroll right">"""

new_end = """        </div>
    </div>
    </div>
    <button class="filter-arrow filter-arrow-right hidden" aria-label="Scroll right">"""

content = content.replace(old_end, new_end)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed unclosed div.")
