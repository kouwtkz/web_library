if (typeof server !== "undefined" || process.argv.length < 4) {
    {
        var a = server.close();
    }
}
var option = { DocumentRoot: process.argv[2], Port: Number(process.argv[3]) };

var http = require("http");
var url = require("url");
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
    ".txt": "text/plain",
};

var server = http
    .createServer(function (req, res) {
        var pathps = url.parse(req.url);
        var pathName = pathps.pathname;
        var dirName = path.dirname(pathName);
        var pageName = path.basename(pathName);
        var m = pageName.match(/^([^\.]*)(.*)$/);
        if (m[2] === "") {
            dirName += "/" + pageName;
            pageName = "index.html";
        }
        if (dirName !== "/") {
            dirName += "/";
        }
        var filePath = option.DocumentRoot + dirName + pageName;
        filePath = filePath.replace(/\//g, "\\");
        // console.log(filePath);
        fs.readFile(filePath, function (err, data) {
            if (err === null) {
                var mime_str = mime[path.extname(filePath)] || "text/plain";
                if (mime_str.match(/text||javascript/)) {
                    mime_str += "; charset=utf-8";
                }
                res.writeHead(200, {
                    "Content-Type": mime_str,
                });
                res.end(data);
            } else {
                err_func(err);
            }
        });
        var err_func = function (err) {
            res.writeHead(200, { "Content-Type": "text/plain" });
            res.end(JSON.stringify(err));
        };
    })
    .listen(option.Port);
console.log(server);
var os = require("os");
var intf = os.networkInterfaces();
Object.keys(intf).forEach((e) => {
    intf[e].forEach((d) => {
        if (d.family === "IPv4" && d.address !== "127.0.0.1")
            option.Address = d.address;
        option.URL = option.Address + ":" + option.Port;
    });
});
console.log(option);
