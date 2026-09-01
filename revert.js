const fs = require('fs');

function revertFile(file) {
  let content = fs.readFileSync(file, 'utf8');

  content = content.replace('x:["0%","-100%"]', 'x:["0%","-80%"]');
  content = content.replace('x:["0%","-200%"]', 'x:["0%","-160%"]');

  fs.writeFileSync(file, content);
}

revertFile('urth_clone/js/webflow.df406d78.c0de89fbab670a49.js');
revertFile('urth_clone/js/webflow.88a3dcf7.39697af9d018a2ff.js');
console.log("Reverted x translations");
