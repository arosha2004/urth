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
            if (file.endsWith('.html') || file.endsWith('.txt')) {
                results.push(file);
            }
        }
    });
    return results;
}

const htmlFiles = walk(path.join(__dirname, '..'));
htmlFiles.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;
    
    // Replace height: 90px; with height: 28px; in the logo img tags
    const regex = /(<img src="[^"]*URTH \(1\)\.png" alt="Urth Logo"[^>]*style="[^"]*height:\s*)90px;/g;
    if (regex.test(content)) {
        content = content.replace(regex, '$128px;');
        changed = true;
    }
    
    if (changed) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated', file);
    }
});
