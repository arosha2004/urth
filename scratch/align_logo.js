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
    
    // Replace height: 28px; with height: 34px; and add vertical alignment fixes
    const regex = /(<img src="[^"]*URTH \(1\)\.png" alt="Urth Logo"[^>]*style="[^"]*height:\s*)28px;(\s*margin:\s*0;\s*padding:\s*0;\s*object-fit:\s*contain;)/g;
    if (regex.test(content)) {
        content = content.replace(regex, '$134px;$2 vertical-align: middle; transform: translateY(-4px);');
        changed = true;
    }
    
    if (changed) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated', file);
    }
});
