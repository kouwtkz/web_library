if (process.argv.length < 4) {
    console.error("Need path & port argument!");
} else {
    if (typeof server !== "undefined") {
        {
            var a = server.close();
        }
    }
    var option = {
        DocumentRoot: process.argv[2],
        Port: Number(process.argv[3]),
    };
    const http = require("http");
    const path = require("path");
    const fs = require("fs");
    const { execSync } = require("child_process");
    var mime = {
        ".html": "text/html",
        ".htm": "text/html",
        ".php": "text/html",
        ".pl": "text/html",
        ".cgi": "text/html",
        ".css": "text/css",
        ".js": "application/javascript",
        ".png": "image/png",
        ".jpg": "image/jpeg",
        ".gif": "image/gif",
        ".txt": "text/plain",
    };

    var server = http
        .createServer((req, res) => {
            var pathSplit = req.url.split("?");
            var pathName = pathSplit.shift();
            var dirName = path.dirname(pathName);
            var pageName = path.basename(pathName);
            if (!pageName.match(/\./) && !pathName.match(/\/$/)) {
                var quaryStr =
                    pathSplit.length > 0 ? "?" + pathSplit.join("?") : "";
                res.writeHead(302, {
                    Location: pathName + "/" + quaryStr,
                });
                res.end();
                return;
            }

            var m = pageName.match(/^([^\.]*)(.*)$/);
            if (m[2] === "") {
                dirName = dirName.replace(/\/?$/, "/") + pageName;
                pageName = "";
            }
            dirName = dirName.replace(/\/?$/, "/");
            var filePath;
            if (pageName === "") {
                ["index.html", "index.htm", "index.php"].forEach((ix) => {
                    if (pageName === "") {
                        var tmpPath = option.DocumentRoot + dirName + ix;
                        if (fs.existsSync(tmpPath)) pageName = ix;
                    }
                });
            }
            filePath = option.DocumentRoot + dirName + pageName;
            if (pageName === "") {
                var html = "";
                var mime_str = "text/html; charset=utf-8";
                res.writeHead(200, {
                    "Content-Type": mime_str,
                });
                if (fs.existsSync(filePath)) {
                    files = fs.readdirSync(filePath + ".");
                    if (files) {
                        files.forEach((file) => {
                            html +=
                                '<a href="' +
                                file +
                                '">' +
                                file +
                                "</a>" +
                                "\n";
                        });
                    }
                }
                res.end(html);
            } else {
                fs.readFile(filePath, (err, data) => {
                    var ext = path.extname(filePath);
                    if (err === null) {
                        var mime_str = mime[ext] || "text/plain";
                        if (mime_str.match(/text|javascript/)) {
                            mime_str += "; charset=utf-8";
                        }
                        res.writeHead(200, {
                            "Content-Type": mime_str,
                        });
                        var exe = "";
                        switch (ext) {
                            case ".pl":
                                exe = "/usr/bin/perl";
                                break;
                            case ".cgi":
                                var m = data.toString().match(/^#!([\S]+)/);
                                if (m) exe = m[1];
                                break;
                        }
                        if (exe !== "" && fs.existsSync(exe)) {
                            var stdout = execSync(exe + " " + filePath);
                            res.end(stdout);
                        } else {
                            res.end(data);
                        }
                    } else {
                        err_func(err);
                    }
                });
            }
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
}
