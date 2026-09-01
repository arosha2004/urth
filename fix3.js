const fs = require('fs');

function processFile(file) {
  let content = fs.readFileSync(file, 'utf8');
  
  // Find the exact ta-18eeb71a
  let ta18 = content.indexOf('{id:"ta-18eeb71a"');
  if (ta18 !== -1) {
    let nextBraces = content.indexOf('}}}', ta18);
    let chunk = content.substring(ta18, nextBraces + 3);
    let newChunk = chunk.replace(/x:\["0%","-[^"]+"\]/, 'x:["0%","-106.66%"]');
    content = content.substring(0, ta18) + newChunk + content.substring(nextBraces + 3);
  }

  // Find the exact ta-d8f2117b
  let tad8 = content.indexOf('{id:"ta-d8f2117b"');
  if (tad8 !== -1) {
    let nextBraces = content.indexOf('}}}', tad8);
    let chunk = content.substring(tad8, nextBraces + 3);
    let newChunk = chunk.replace(/x:\["0%","-[^"]+"\]/, 'x:["0%","-213.33%"]');
    content = content.substring(0, tad8) + newChunk + content.substring(nextBraces + 3);
  }

  fs.writeFileSync(file, content);
}

processFile('urth_clone/js/webflow.df406d78.c0de89fbab670a49.js');
processFile('urth_clone/js/webflow.88a3dcf7.39697af9d018a2ff.js');
console.log("Fixed manually via indexOf");
