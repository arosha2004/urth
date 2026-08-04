import re
from bs4 import BeautifulSoup

with open('projects.html', 'r', encoding='utf-8') as f:
    html = f.read()

soup = BeautifulSoup(html, 'html.parser')

container = soup.find('div', class_='timeline-container')
if container:
    rows = container.find_all('div', class_='timeline-row')
    
    left_col = soup.new_tag('div', attrs={'class': 'timeline-left'})
    right_col = soup.new_tag('div', attrs={'class': 'timeline-right'})
    
    for i, row in enumerate(rows):
        item = row.find('div', class_='timeline-item')
        images = row.find('div', class_='timeline-images')
        
        if item:
            item['data-index'] = str(i)
            left_col.append(item)
        if images:
            images['data-index'] = str(i)
            right_col.append(images)
            
    container.clear()
    container.append(left_col)
    container.append(right_col)
    
    with open('projects.html', 'w', encoding='utf-8') as f:
        f.write(str(soup))
    print("Successfully restructured HTML.")
else:
    print("Could not find timeline-container.")
