var http = require('http');
var url = require('url');
var path = require("path");
var fs = require("fs");
var mime = {
".html": "text/html",
".php": "text/html",
".css": "text/css",
".js": "application/javascript",
".png": "image/png",
".jpg": "image/jpeg",
".gif": "image/gif",
".txt": "text/plain"
};
var DocumentRoot = "/xampp/htdocs";
var Port = 3000;

if(typeof(server)!=='undefined'){{var a = server.close();}}
var server = http.createServer(
function(req, res) {
var pathps = url.parse(req.url);
var pathName = pathps.pathname;
var dirName = path.dirname(pathName);
var pageName = path.basename(pathName);
var m = pageName.match(/^([^\.]*)(.*)$/);
if (m[2]==='') {
dirName += "/" + pageName;
pageName = "index.html";
}
if (dirName!=="/") {dirName += "/"};
var filePath = DocumentRoot + dirName + pageName;
filePath = filePath.replace(/\//g, '\\');
console.log(filePath);
fs.readFile(filePath,function(err,data) {
if (err===null) {
res.writeHead(200, {'Content-Type': mime[path.extname(filePath)] || "text/plain"});
res.end(data);
} else {
err_func(err);
}
});
var err_func = function(err) {
res.writeHead(200, {'Content-Type': "text/plain"});
res.end(JSON.stringify(err));
};
}
).listen(Port);
