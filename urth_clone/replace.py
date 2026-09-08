import os

count = 0
target1 = '<a href="#contact" id="w-node-_89d72580-7e45-cfb2-d4a1-09ccf56f9d20-f56f9d20" data-wf--button--variant="white" class="button w-variant-feb84848-cb71-7044-c8e5-41611aacb4e0 w-inline-block"><div class="button-text-wrapper"><div class="button-text-inner"><div class="button-text-item"><div button-text="" class="button-text">Book a Consultation</div></div><div class="button-text-item"><div button-text="" class="button-text">Book a Consultation</div></div></div></div><div class="button-hover-bg w-variant-feb84848-cb71-7044-c8e5-41611aacb4e0"></div></a>'

replacement1 = '<a href="../contact.html" id="w-node-_89d72580-7e45-cfb2-d4a1-09ccf56f9d20-f56f9d20" data-wf--button--variant="white" class="button w-variant-feb84848-cb71-7044-c8e5-41611aacb4e0 w-inline-block"><div class="button-text-wrapper"><div class="button-text-inner"><div class="button-text-item"><div button-text="" class="button-text">Book a Consultation</div></div><div class="button-text-item"><div button-text="" class="button-text">Book a Consultation</div></div></div></div><div class="button-hover-bg w-variant-feb84848-cb71-7044-c8e5-41611aacb4e0"></div></a>'

target2 = '<a href="#service" id="w-node-_89d72580-7e45-cfb2-d4a1-09ccf56f9d20-f56f9d20" data-wf--button--variant="transparent-white" class="button w-variant-158d11ee-49ed-ad84-d1c0-3417d23ac927 w-inline-block"><div class="button-text-wrapper"><div class="button-text-inner"><div class="button-text-item"><div button-text="" class="button-text">Explore Service</div></div><div class="button-text-item"><div button-text="" class="button-text">Explore Service</div></div></div></div><div class="button-hover-bg w-variant-158d11ee-49ed-ad84-d1c0-3417d23ac927"></div></a>'

replacement2 = '<a href="../index.html#service" id="w-node-_89d72580-7e45-cfb2-d4a1-09ccf56f9d20-f56f9d20" data-wf--button--variant="transparent-white" class="button w-variant-158d11ee-49ed-ad84-d1c0-3417d23ac927 w-inline-block"><div class="button-text-wrapper"><div class="button-text-inner"><div class="button-text-item"><div button-text="" class="button-text">Explore Service</div></div><div class="button-text-item"><div button-text="" class="button-text">Explore Service</div></div></div></div><div class="button-hover-bg w-variant-158d11ee-49ed-ad84-d1c0-3417d23ac927"></div></a>'

for r, d, files in os.walk('.'):
    for f in files:
        if f.endswith('.html'):
            filepath = os.path.join(r, f)
            try:
                with open(filepath, 'r', encoding='utf-8') as file:
                    content = file.read()
            except Exception:
                continue
            
            original_content = content
            content = content.replace(target1, replacement1)
            content = content.replace(target2, replacement2)
            
            if content != original_content:
                with open(filepath, 'w', encoding='utf-8') as file:
                    file.write(content)
                count += 1
                print(f'Updated {filepath}')
print(f'Total: {count}')
