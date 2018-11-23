var cws = {};
cws.php_path = '';
cws.defaultAnsynch = true;
cws.result = null;
cws.resultstr = "";
cws.conmode = false;
cws.querys = '';
cws.domain = location.host;
cws.basehost = location.protocol + "//" + cws.domain;

cws.get = {};
cws.get.domain = function(url){
    const base = url.match(/\/\/.*?\//);
    return (base===null)?"":base[0].slice(2, -1);
}
cws.get.basehost = function(url){
    const base = url.match(/^.*?\/\/.*?\//);
    return (base===null)?"":base[0].slice(0, -1);
}
cws.get.dir = function(url){
    return (url + "/.").match(/^.*?\./)[0].match(/^.*\//)[0].replace(/\/+$/,"/");
}
cws.dir = cws.get.dir(cws.basehost + location.pathname);
cws.get.key = function(ary = {}, key = "", nullval = '') {
    return (key in ary) ? ary[key] : nullval;
}
cws.get.object = function(obj, nullval = null){
    if (typeof(obj) !== "object" || obj === null) {
        return nullval;
    } else {
        return obj;
    }
}
cws.get.str = function(item, nullval = '', defaultval = '') {
    switch (typeof(item)) {
        case "undefined": case "object":
            { item = nullval; break; }
        default:
            {
                item = String(item);
                if (item === "") { item = defaultval; }
            }
    }
    return item;
}
cws.get.link = function(link){
    link = String(link);
    if (link.match(/\//)) {
        if (link.match(/^\//)) {
            return cws.basehost + link;
        } else {
            return link;
        }
    } else {
        return cws.dir + link;
    }
}
// URLの?以降を取得する関数、更に取得したものを定義する
cws.get.query = function(href = location.href) {
    let arg = new Object;
    const spl = location.href.split('?')
    const qry = spl[spl.length - 1];
    const pair = qry.split('&');
    for (let i = 0; pair[i]; i++) {
        let kv = pair[i].split('=');
        arg[kv[0]] = kv[1];
    }
    return arg;
}
cws.querys = cws.get.query();
// 最初にキャッシュを適用させつつ、更新後は読み込ませるプログラム
cws.get.ref = function(args = []) {
    if (typeof(args) !== 'object') {
        args = [args]
    }
    cws.runpostjson({
        request: {
            m: 6
        },
        json: args,
        ansynch: false
    });
    args = JSON.parse(cws.result);
    for (let i = 0; i < args.length; i++) {
        let item = args[i];
        if (typeof(item) !== 'object') {
            item = {
                path: item
            }
        };
        let rpath = cws.get.key(item, "path");
        if (rpath !== "") {
            let id = cws.get.key(item, "id");
            let cls = cws.get.key(item, "class");
            let ext = rpath.split('.');
            ext = ext[ext.length - 1].toLowerCase();
            let writetxt = "";
            switch (ext) {
                case 'css':
                    {
                        writetxt = '<link rel="stylesheet" id="' + id + '" class="' + cls + '" href="' + rpath + '" type="text/css" media="all">"';
                        break;
                    }
                default:
                    {
                        writetxt = '<script type="text/javascript" id="' + id + '" class="' + cls + '" src="' + rpath + '"></script>';
                    }
            }
            document.write(writetxt);
        }
    }
}
// 常に重複しない36進数
cws.get.date36 = function(){
    const d = new Date;
    return Number('' + d.getFullYear() + d.getMonth() + 0 + d.getDay() + d.getHours() + d.getMinutes() + d.getSeconds() + d.getMilliseconds()).toString(36);
}
cws.json = {};
cws.json.tostr = function(json_arg) {
    switch (typeof(json_arg)) {
        case "string":
            {
                return json_arg;
                break;
            }
        case "object":
            {
                try {
                    return JSON.stringify(json_arg)
                } catch (e) {
                    console.log(e);
                }
            }
    }
    return null;
}
cws.to = {}
cws.to.request_array = function(request_ary = null, path = cws.php_path){
    let rq = cws.get.object(request_ary, {});
    const query_str = (path + "?").replace(/^.*?\?/,"").replace(/.$/, "");
    const spl = query_str.split("&");
    const keys = Object.keys(spl);
    for (let i = 0; i < keys.length; i++) {
        let spl2 = (spl[i] + "=").split("=");
        rq[spl2[0]] = spl2[1];
    }
    return rq;
}
cws.to.geturl = function(array_list = null, path = cws.php_path) {
    const rq = cws.to.request_array(array_list, path);
    path = path.replace(/\?.*$/, "");
    let list = [];
    const keys = Object.keys(request);
    if (keys.length > 0) {
        for (let i = 0; i < keys.length; i++) {
            list.push(keys[i] + "=" + request[keys[i]]);
        }
        return path + "?" + list.join("&");
    } else {
        return path;
    }
}
cws.to.form = function(array_list = null, filename_list = null, formdata_obj = null, path = cws.php_path){
    const rq = cws.to.request_array(array_list, path);
    formdata_obj = cws.get.object(formdata_obj, new FormData());
    filename_list = cws.get.object(filename_list, {});
    keys = Object.keys(rq);
    for (let i = 0; i < keys.length; i++) {
        let key = keys[i];
        let jadge = key;
        let val = rq[keys[i]];
        if (typeof(val)!=="object") {jadge = key + val;}
        if (jadge !== "") {
            let filename = cws.get.key(filename_list, key, null);
            if (filename === null) {
                formdata_obj.append(key, val);
            } else {
                formdata_obj.append(key, val, filename);
            }
        }
    }
    return formdata_obj;
}
cws.get.parelm = function(elem, childuse = "URL"){
    const prt = elem.parentNode;
    if (prt === undefined || prt === null) { return elem; }
    if (prt[childuse] === undefined) {
        return cws.get.parelm(prt, childuse);
    } else {
        return prt;
    }
}
cws.get.partag = function(elem, tagname = "html") {
    if (elem !== undefined) {
        if (elem.tagName.toLowerCase() === tagname) { return elem; }
        return cws.get.partag(elem.parentNode, tagname);
    } else {
        return elem;
    }
}

// Element書き込み関数、FormDataから書き出すこともできます（主にデータ送信に使用）
cws.write = {};
cws.write.onload = function(){};
cws.write.form = function(fm, insertobj = document, action = "", id = "", otherelm = "", inputtype = "hidden", opt = 0){
    if (fm.entries === undefined) {
        if (typeof(fm) === "object") {
            fm = cws.to.form(fm, null, null);
        } else {
            fm = String(fm);
            fm = cws.to.form(null, null, null, (fm.match(/\?/)?"":"?") + fm);
        }
    }
    const temp_fm_id = "__temp__fm__" + Math.floor(Math.random() * 9999 + 1);
    if (insertobj.document !== undefined) { insertobj = insertobj.document; }
    const docm = cws.get.parelm(insertobj);
    cws.write.elem("form", "\n", insertobj, temp_fm_id, otherelm, opt);

    const temp_fm = docm.getElementById(temp_fm_id);
    const rd = new FileReader();
    let eof = false;
    let stocklist = {};
    if (action !== "") { temp_fm.setAttribute("action", action); }
    if (id !== "") { temp_fm.setAttribute("id", id); }
    temp_fm.setAttribute("method", "POST");
    if (fm === null || typeof(fm) !== "object") {fm = [];}
    let inline_value = '';
    for(item of fm){
        inline_value = ''
        if ((typeof(item[1]))!=="object"){inline_value = item[1];}
        else if (item[1] === null) {inline_value = '';}
        else {
            inline_value = item[1]["name"];
            rd.onload = function(){
                temp_fm.innerHTML += "<input type='" + inputtype + "' name='" + item[0] + "_base64' value='" + this.result + "' />\n";
                delete stocklist[item[0]];
                if (eof && Object.keys(stocklist).length === 0) {cws.write.onload(temp_fm); cws.write.onload = function(){};}
            }
            stocklist[item[0]] = true;
            rd.readAsDataURL(item[1]);
        }
        temp_fm.innerHTML += "<input type='" + inputtype + "' name='" + item[0] + "' value='" + inline_value + "' />\n";
    }
    eof = true;
    if (Object.keys(stocklist).length === 0) {cws.write.onload(temp_fm); cws.write.onload = function(){};}
    return temp_fm.id;
};
cws.write.script = function(inline = "", insertobj = document, id = "", otherelm = "", opt = 0){
    if (insertobj.document !== undefined) { insertobj = insertobj.document; }
    const docm = cws.get.parelm(insertobj);
    return cws.write.elem("script", "\n" + inline + "\n", docm, id, otherelm, opt)
};
cws.write.elem = function(element = "div", inline = "", insertobj = document, id = "", otherelm = "", opt = 0){
    insertstr = "<" + element + " id = '" + id + "' " + otherelm + ">" + inline + "</script>\n";
    if (insertobj.document !== undefined) {
        insertobj.document.write(insertstr);
    } else if(insertobj.getElementById !== undefined) {
        insertobj.lastElementChild.innerHTML += insertstr;
    } else if(insertobj.getElementsByName !== undefined) {
        insertobj.innerHTML += insertstr;
    }
}

cws.ajax = {};
cws.ajax.onload = function(){};
// argsの引数は "ansynch", "onload", "method", "form", "request", "catch", "type"を検知、取得する
// デフォルトではserver.phpを呼び出します、特に指定なければPOSTで送信します
cws.ajax.run = function(target = cws.php_path, args = {}, filename_list = {}, opt = 0) {
    try {
        if (typeof(args) !== "object" || args === null) {args = {}};
        let ansynch = cws.get.key(args, "ansynch", true);
        if (ansynch === null) {
            ansynch = cws.defaultAnsynch;
        }
        const catchfunc = cws.get.key(args, "catch", null);
        const retfunc = cws.get.key(args, "onload", function(){});
        let mtd = cws.get.key(args, "method", "POST").toUpperCase();
        let rq = cws.get.key(args, "request", {});
        if (opt & 1) {
            rq["refpath"] = cws.get.str(cws.get.key(args, "refpath", location.pathname))
        }
        let fm = cws.get.key(args, "form", null);
        if (mtd !== "GET") {
            fm = cws.to.form(rq, filename_list, fm, target);
            target = target.replace(/\?.*$/, "");
        } else {
            fm = null;
            target = cws.to.geturl(rq, target);
        }
        const xr = new XMLHttpRequest();
        let restype = cws.get.key(args, "type");
        switch (restype.toLowerCase()) {
            case "blob": restype = "blob"; break;
            case "arraybuffer": case "buf": case "bin": restype = "arraybuffer"; break;
            case "document": case "html": restype = "document"; break;
            case "json": restype = "json"; break;
            case "text": restype = "text"; break;
            default: restype = "";
        }
        xr.responseType = restype;
        xr.open(mtd, target, ansynch);
        xr.send(fm);
        const localrun = function(lxr) {
            cws.result = lxr.response;
            cws.resultstr = lxr.responseText;
            cws.conmode = true;
            retfunc(lxr);
            cws.onload(lxr);
        }
        if (ansynch) {
            xr.onload = function() {
                localrun(this);
            }
            cws.conmode = false;
        } else {
            localrun(xr);
        }
        return true;
    } catch (e) {
        if (typeof(catchfunc) !== "function") {
            console.log(e);
        } else {
            catchfunc(e);
        }
        return false;
    }
}
// runajaxにjsonを含み、Jsonを渡すプログラム
cws.ajax.json = function(target = cws.php_path, json = "", args = {}, opt = 0) {
    let fm = this.get.key(args, "form");
    if (typeof(fm) !== "object") {
        fm = new FormData();
    };
    args["method"] = "POST";
    if (typeof(json) === "string") {
        fm.append("json", json);
    }
    args["method"] = "POST";
    args["form"] = fm;
    return cws.ajax.run(target, args, opt);
}
cws.post = {};
cws.post.child = window;
cws.post.onload = function(){}
cws.post.open = function(VALorELEM = {}, url = null, target = "", formdata_obj = null, opt = 3){
    let felem = false;
    if ((typeof(VALorELEM)==="object")&&(VALorELEM.$cws_dir!==undefined)){
        VALorELEM = cws.get.partag(VALorELEM, "form");
        felem = true;
        if (opt & 1) {VALorELEM.setAttribute("method", "POST");}
        if (opt & 2) {VALorELEM.setAttribute("enctype", "multipart/form-data");}
        const act = VALorELEM.getAttribute("action");
        if (url===null) {
            if (act!==null) {
                url = act; VALorELEM.setAttribute("action", url);
            }
            else {return false;}
        }
        if (target==="") {target = cws.get.str(VALorELEM.getAttribute("target"));}
    } else {
        url = cws.get.str(url);
    }
    const cntname = "cws_" + cws.get.date36()
    if (target===undefined || typeof(target)==="object" || target==="_blank") {
        cws.post.child = window.open("",cntname);
    } else if (target==="") {
        cws.post.child = window;
    } else {
        cws.post.child = window.open("",target);
    }
    const sendtgt = cws.post.child.name
    if (felem) { VALorELEM.setAttribute("target", sendtgt); }
    let fm = null;
    loadfunc = function(e){
        e.submit();
        if (opt & 4) { VALorELEM.setAttribute("target", target); }
        if (cws.domain === cws.get.domain(url)) {
            setTimeout(()=>{
                cws.post.child.addEventListener("load", cws.post.onload(), false);
            }, 5);
        }
    }
    if (felem) {
        fm = cws.to.form(VALorELEM, null, formdata_obj, url);
    } else {
        fm = VALorELEM;
    }
    url = cws.get.link(url).replace(/\?.*$/, "");
    if (felem) {
        loadfunc(VALorELEM);
    } else {
        cws.write.onload = loadfunc;
        cws.write.form(fm, cws.post.child, url);
    }
    return true;
}

cws.storage = {};
cws.storage.out = function(key, value) {
    const storage = sessionStorage;
    storage.removeItem(key);
    storage.setItem(key, value);
}
cws.storage.get = function(key) {
    const storage = sessionStorage;
    const getstr = storage[key];
    if (typeof(getstr) === "undefined") getstr = "";
    return getstr;
}