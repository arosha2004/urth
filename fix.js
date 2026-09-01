const fs = require('fs');

function processFile(file) {
  let content = fs.readFileSync(file, 'utf8');

  // Replace in t-af0ddb22
  content = content.replace(
    '{id:"ta-f7b81775",targets:[["wf:inst",["6a1c3d9d7686060838b279f9","ca255160-7d44-adf6-6977-cf67932046ee"],{relationship:"none",firstMatchOnly:!1}]],timing:{duration:.1,position:.35,ease:0},tt:2,properties:{"wf:transform":{height:["50vh","80vh"]}}}',
    '{id:"ta-f7b81775",targets:[["wf:inst",["6a1c3d9d7686060838b279f9","ca255160-7d44-adf6-6977-cf67932046ee"],{relationship:"none",firstMatchOnly:!1}]],timing:{duration:.1,position:.35,ease:0},tt:2,properties:{"wf:transform":{height:["50vh","80vh"]}}},{id:"ta-5thimg",targets:[["wf:inst",["6a1c3d9d7686060838b279f9","da366271-8e55-bef7-7a88-df78043157ff"],{relationship:"none",firstMatchOnly:!1}]],timing:{duration:.1,position:.5,ease:0},tt:2,properties:{"wf:transform":{height:["50vh","80vh"]}}}'
  );

  content = content.replace('x:["0%","-80%"]', 'x:["0%","-100%"]');

  // Replace in t-fa95e15d
  content = content.replace(
    '{id:"ta-17abd92d",targets:[["wf:inst",["6a1c3d9d7686060838b279f9","ca255160-7d44-adf6-6977-cf67932046ee"],{relationship:"none",firstMatchOnly:!1}]],timing:{duration:.1,position:.35,ease:0},tt:2,properties:{"wf:transform":{height:["50vh","80vh"]}}}',
    '{id:"ta-17abd92d",targets:[["wf:inst",["6a1c3d9d7686060838b279f9","ca255160-7d44-adf6-6977-cf67932046ee"],{relationship:"none",firstMatchOnly:!1}]],timing:{duration:.1,position:.35,ease:0},tt:2,properties:{"wf:transform":{height:["50vh","80vh"]}}},{id:"ta-5thimg2",targets:[["wf:inst",["6a1c3d9d7686060838b279f9","da366271-8e55-bef7-7a88-df78043157ff"],{relationship:"none",firstMatchOnly:!1}]],timing:{duration:.1,position:.5,ease:0},tt:2,properties:{"wf:transform":{height:["50vh","80vh"]}}}'
  );

  content = content.replace('x:["0%","-160%"]', 'x:["0%","-200%"]');

  fs.writeFileSync(file, content);
}

processFile('urth_clone/js/webflow.df406d78.c0de89fbab670a49.js');
processFile('urth_clone/js/webflow.88a3dcf7.39697af9d018a2ff.js');
console.log("Done");
