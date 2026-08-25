const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory() && !file.includes('node_modules') && !file.includes('.git')) {
            results = results.concat(walk(file));
        } else {
            if (file.endsWith('.html')) {
                results.push(file);
            }
        }
    });
    return results;
}

const htmlFiles = walk(path.join(__dirname, '..'));
htmlFiles.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    
    const rootPath = path.resolve(__dirname, '..');
    const relativePath = path.relative(path.dirname(file), rootPath);
    let imgPath = 'img/URTH (1).png';
    if (relativePath) {
        imgPath = relativePath.replace(/\\/g, '/') + '/img/URTH (1).png';
    }
    // if file is in root, relativePath is empty, so imgPath remains 'img/URTH (1).png'
    
    let changed = false;
    const regex = /<h2\s+class="navbar-brand-text"[^>]*>urth\.?<\/h2>/gi;
    if (regex.test(content)) {
        content = content.replace(regex, `<img src="${imgPath}" alt="Urth Logo" class="navbar-brand-image" style="height: 40px; margin: 0; padding: 0; object-fit: contain;">`);
        changed = true;
    }
    
    if (changed) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated', file);
    }
});
