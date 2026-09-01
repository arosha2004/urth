const fs = require('fs');

function processFile(file) {
  let content = fs.readFileSync(file, 'utf8');
  
  // Revert desktop translation
  content = content.replace(
    /x:\["0%","-106\.66%"\]/,
    'x:["0%","-80%"]'
  );

  // Revert mobile translation
  content = content.replace(
    /x:\["0%","-213\.33%"\]/,
    'x:["0%","-160%"]'
  );

  fs.writeFileSync(file, content);
}

processFile('urth_clone/js/webflow.df406d78.c0de89fbab670a49.js');
processFile('urth_clone/js/webflow.88a3dcf7.39697af9d018a2ff.js');
console.log("Reverted x translation perfectly");
