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
    var index_list = [
        "index.html",
        "index.htm",
        "index.cgi",
        "index.pl",
        "index.php",
    ];
    var cgi_bin_re = /^\/cgi-bin\//;

    var server = http
        .createServer((req, res) => {
            var err_func = function (err) {
                if (err.code === "ENOENT") {
                    res.writeHead(404, { "Content-Type": "text/plain" });
                    res.end("404 not found");
                } else {
                    res.writeHead(200, { "Content-Type": "text/plain" });
                    res.end(JSON.stringify(err));
                }
            };
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
                index_list.some((ix) => {
                    if (pageName === "") {
                        var tmpPath = option.DocumentRoot + dirName + ix;
                        if (fs.existsSync(tmpPath)) {
                            pageName = ix;
                            return true;
                        }
                    }
                });
            }
            filePath = option.DocumentRoot + dirName + pageName;
            if (pageName === "") {
                var html = "";
                var mime_str = "text/html; charset=utf-8";
                if (fs.existsSync(filePath)) {
                    res.writeHead(200, {
                        "Content-Type": mime_str,
                    });
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
                } else {
                    err_func({ code: "ENOENT" });
                }
                res.end(html);
            } else {
                var ext = path.extname(filePath);
                var exe = "";
                var exe_force = false;
                var exe_arg = "";
                switch (ext) {
                    case ".pl":
                        exe = "/usr/bin/perl";
                        break;
                    case ".cgi":
                        data = fs.readFileSync(filePath);
                        var m = data.toString().match(/^#!([\S]+)/);
                        if (m) exe = m[1];
                        break;
                    case ".js":
                        if (dirName.match(cgi_bin_re)) {
                            exe_force = true;
                            exe = "node";
                        }
                        break;
                }
                if (exe !== "" && (exe_force || fs.existsSync(exe))) {
                    var requests;
                    if (pathSplit.length > 0) {
                        requests = pathSplit.join("?").split("&");
                    } else {
                        requests = [];
                    }
                    const run = () => {
                        if (fs.existsSync(filePath)) {
                            var request_str =
                                requests.length > 0
                                    ? ' "' + requests.join("&") + '"'
                                    : "";
                            var exec_str = exe + " " + filePath + request_str;
                            var stdout = execSync(exec_str);
                            res.end(stdout);
                        } else {
                            err_func({ code: "ENOENT" });
                        }
                    };
                    if (req.method === "POST") {
                        var post_data = "";
                        req.on("data", (chunk) => {
                            post_data += chunk;
                        }).on("end", () => {
                            requests.push(post_data);
                            run();
                        });
                    } else {
                        run();
                    }
                    return;
                }
                fs.readFile(filePath, (err, data) => {
                    if (err === null) {
                        var mime_str = mime[ext] || "text/plain";
                        if (mime_str.match(/text|javascript/)) {
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
            }
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
