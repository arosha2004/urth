const fs = require('fs');

function processFile(file) {
  let content = fs.readFileSync(file, 'utf8');
  
  // Apply desktop translation for 5 items
  content = content.replace(
    /x:\["0%","-80%"\]/,
    'x:["0%","-106.66%"]'
  );

  // Apply mobile translation for 5 items
  content = content.replace(
    /x:\["0%","-160%"\]/,
    'x:["0%","-213.33%"]'
  );

  fs.writeFileSync(file, content);
}

processFile('urth_clone/js/webflow.df406d78.c0de89fbab670a49.js');
processFile('urth_clone/js/webflow.88a3dcf7.39697af9d018a2ff.js');

let phpContent = fs.readFileSync('projects.php', 'utf8');
phpContent = phpContent.replace('v=3', 'v=4');
phpContent = phpContent.replace('v=3', 'v=4');
fs.writeFileSync('projects.php', phpContent);

console.log("Applied -106.66% and v=4");
