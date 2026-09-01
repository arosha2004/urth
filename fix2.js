const fs = require('fs');

function processFile(file) {
  let content = fs.readFileSync(file, 'utf8');
  
  // Find the exact location of the animation for service-image-inner
  // Desktop animation is in t-af0ddb22
  let target1 = '{id:"t-af0ddb22"';
  let target1End = content.indexOf('}', content.indexOf(target1) + 200);
  
  // Actually, we can use a regex that matches ta-18eeb71a block
  content = content.replace(
    /\{id:"ta-18eeb71a"[^}]+x:\["0%","-[^"]+"\]\}\}\}/,
    '{id:"ta-18eeb71a",targets:[["wf:class",["service-image-inner"],{relationship:"none",firstMatchOnly:!1}]],timing:{ease:0},tt:2,properties:{"wf:transform":{x:["0%","-106.66%"]}}}'
  );

  // Mobile animation is in t-fa95e15d
  content = content.replace(
    /\{id:"ta-d8f2117b"[^}]+x:\["0%","-[^"]+"\]\}\}\}/,
    '{id:"ta-d8f2117b",targets:[["wf:class",["service-image-inner"],{relationship:"none",firstMatchOnly:!1}]],timing:{ease:0},tt:2,properties:{"wf:transform":{x:["0%","-213.33%"]}}}'
  );

  fs.writeFileSync(file, content);
}

processFile('urth_clone/js/webflow.df406d78.c0de89fbab670a49.js');
processFile('urth_clone/js/webflow.88a3dcf7.39697af9d018a2ff.js');
console.log("Updated x values");
