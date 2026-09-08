import os
import glob

files = ['amman-rotana-hotel.html', 'cultural-complex-centre.html', 'dalbourne-villa.html', 'european-lard-station.html', 'poolscape-villa.html', 'yabroudi-villa.html']

for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    if 'Location: Canada' in content:
        content = content.replace('Location: Canada', 'Location: Sri Lanka')
        
        with open(f, 'w', encoding='utf-8') as file:
            file.write(content)
        print(f'Modified {f}')
