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
    var user = null;
    if (process.argv.length > 5) {
        user = {
            Name: process.argv[4],
            Password: process.argv[5],
            CookieKey: "wsv_login",
        };
    }
    const http = require("http");
    const path = require("path");
    const fs = require("fs");
    const { execSync } = require("child_process");
    var mime = {
        ".html": "text/html",
        ".htm": "text/html",
        ".cgi": "text/html",
        ".pl": "text/html",
        ".php": "text/html",
        ".ejs": "text/html",
        ".css": "text/css",
        ".js": "application/javascript",
        ".png": "image/png",
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".svg": "image/svg+xml",
        ".gif": "image/gif",
        ".txt": "text/plain",
        ".xml": "text/xml",
        ".json": "application/json"
    };
    var index_list = [
        "/index.html",
        "/index.htm",
        ".html",
        ".htm",
        "/index.js",
        "/index.cgi",
        "/index.pl",
        "/index.php",
        "/index.ejs",
    ];
    var cgi_bin_re = /^\/cgi-bin\//;
    const set_header = (res, ext_mime = ".html", code = 200, add_str = "") => {
        if (res !== undefined) {
            var mime_str = ext_mime.match(/^\./)
                ? mime[ext_mime] || "text/plain"
                : ext_mime;
            if (mime_str.match(/text|javascript/)) {
                mime_str += "; charset=utf-8";
            }
            res.writeHead(code, {
                "Content-Type": mime_str,
            });
        } else {
            console.error("need http.res");
        }
    };
    const cookie_get = (key = "", req_ck = "") => {
        var cookie =
            typeof req_ck === "object"
                ? req_ck.headers.cookie
                : req_ck.toString();
        if (cookie === undefined) cookie = "";
        var m = cookie.match(eval(`/(^|;\\s*)(${key}=)([^;]*)/`));
        return m ? m[3] : "";
    };
    const cookie_str_login = (user = null, password = "") => {
        var name;
        if (typeof user === "object") {
            if (user === null) return "";
            name = user.Name.toString();
            password = user.Password.toString();
        } else {
            name = user.toString();
        }
        var result = "";
        var len = name.length > password.length ? name.length : password.length;
        for (var i = 0; i < len; i++) {
            var n = i < name.length ? name[i].charCodeAt() : 0;
            var p = i < password.length ? password[i].charCodeAt() : 0;
            result += Math.abs(n - p).toString(16);
        }
        return result;
    };
    if (user !== null) {
        user.CookieValue = cookie_str_login(user);
    }
    const get_requests_str = (req) => {
        return new Promise((resolve) => {
            var requests;
            const func = () => {
                resolve(
                    requests
                        .filter((v) => {
                            return v !== "";
                        })
                        .join("&")
                );
            };
            requests = req.url.replace(/^[^\?]*\??/, "").split("&");
            if (req.method === "POST") {
                var post_data = "";
                req.on("data", (chunk) => {
                    post_data += chunk;
                }).on("end", async () => {
                    requests.push(post_data);
                    func();
                });
            } else {
                func();
            }
        });
    };
    const get_requests = async (req) => {
        var request_str = await get_requests_str(req);
        var dic = {};
        if (request_str !== "") {
            request_str.split("&").forEach((v) => {
                var m = v.match(/(^[^=]+)=?([\d\D]*)$/);
                if (m) {
                    dic[m[1]] = m[2];
                }
            });
        }
        return dic;
    };
    var server = http
        .createServer(async (req, res) => {
            var err_func = function (err, url = "") {
                if (err.code === "ENOENT") {
                    if (url.match("404.html")) {
                        set_header(res, ".html", 404);
                        res.end("404 not found");
                    } else {
                        htmlGenerate("/404.html");
                    }
                } else {
                    set_header(res, ".html");
                    res.end(JSON.stringify(err));
                }
            };
            if (user !== null) {
                var cookie_login_value = cookie_get(user.CookieKey, req);
                if (cookie_login_value !== user.CookieValue) {
                    if (!req.url.match(/\.ico$/)) {
                        var html_spl = [
                            "<html><head>" +
                            '<meta name="viewport" content="width=device-width,initial-scale=1">' +
                            "<style>form,input{margin:0 4px} @media(prefers-color-scheme:dark){*{color:white;background:#111}}</style>" +
                            "</head><body><h4>Login Form</h4>" +
                            '<form method="post"><input name="user" placeholder="user"><input name="password" type="password" placeholder="password"><input type="submit"></form>',
                            "</body></html>",
                        ];
                        var html_add = "";
                        var requests = await get_requests(req);
                        if (
                            requests.user !== undefined &&
                            requests.password !== undefined
                        ) {
                            if (
                                user.Name === requests.user &&
                                user.Password === requests.password
                            ) {
                                var age = 60 * 60 * 24 * 30 * 3;
                                res.setHeader("Set-Cookie", [
                                    `${user.CookieKey}=${user.CookieValue};max-age=${age};path=/`,
                                ]);
                                res.writeHead(302, {
                                    Location: req.url,
                                });
                                res.end();
                                return;
                            } else {
                                html_add =
                                    '<p style="color:#e14438">Incorrect username or password</p>';
                            }
                        }
                        set_header(res, ".html");
                        res.write(html_spl.join(html_add));
                    }
                    res.end();
                    return;
                }
            }
            const htmlGenerate = async (url) => {
                var pathSplit = url.split("?");
                var pathName = decodeURI(pathSplit.shift());
                let filePath = path.resolve(option.DocumentRoot + pathName);
                let lst, isDir;
                try {
                    lst = fs.lstatSync(filePath);
                    isDir = lst.isDirectory();
                } catch {
                    isDir = true;
                }
                if (isDir) {
                    index_list.some((index) => {
                        const indexFilePath = filePath + index;
                        if (fs.existsSync(indexFilePath)) {
                            filePath = indexFilePath;
                            lst = fs.lstatSync(filePath);
                            isDir = lst.isDirectory();
                            return true;
                        }
                    });
                }
                if (isDir) {
                    var html = "";
                    if (fs.existsSync(filePath)) {
                        set_header(res, ".html");
                        files = fs.readdirSync(`${filePath}`);
                        if (files) {
                            files.forEach((file) => {
                                html += `<a href="${file}">${file}</a>\n`;
                            });
                        }
                        res.end(html);
                    } else {
                        err_func({ code: "ENOENT" }, url);
                    }
                } else {
                    var ext = path.extname(filePath);
                    if (fs.existsSync(filePath)) {
                        var exe = "";
                        var exe_force = false;
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
                                if (pathName.endsWith("index.js") || filePath.match(cgi_bin_re)) {
                                    exe_force = true;
                                    exe = "node";
                                }
                                break;
                        }
                        if (exe !== "" && (exe_force || fs.existsSync(exe))) {
                            var request_str = ` "${await get_requests_str(
                                req
                            )}"`;
                            var cookie_str = ` "${req.headers.cookie !== undefined
                                ? req.headers.cookie
                                : ""
                                }"`;
                            var exec_str =
                                exe + " " + filePath + request_str + cookie_str;
                            try {
                                var stdout = execSync(exec_str);
                                set_header(res, ".html");
                                res.end(stdout);
                            } catch (error) {
                                err_func(error);
                            }
                            return;
                        }
                        fs.readFile(filePath, (err, data) => {
                            set_header(res, ext);
                            if (err === null) {
                                res.end(data);
                            } else {
                                err_func(err);
                            }
                        });
                    } else {
                        err_func({ code: "ENOENT" }, url);
                    }
                }
            };
            htmlGenerate(req.url);
        })
        .listen(option.Port);
    console.log(server);
    var os = require("os");
    var intf = os.networkInterfaces();
    Object.keys(intf).forEach((e) => {
        intf[e].forEach((d) => {
            if (d.family === "IPv4" && d.address !== "127.0.0.1")
                option.Address = d.address;
            option.URL = `http://${option.Address}:${option.Port}`;
        });
    });
    console.log(option);
}
