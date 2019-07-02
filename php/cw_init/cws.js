var cws = {};
cws.to = {}
// 配列の要素と内容を入れ替える
cws.to.turnover = function(obj){
    var retv = {};
    Object.keys(obj).filter(
    (value) => {retv[obj[value]] = value; return false;});
    return retv;
}

cws.var = {};
cws.var.php_path = '';
cws.var.defaultAnsynch = true;
cws.var.result = null;
cws.var.resultstr = "";
cws.var.conmode = false;
cws.var.querys = {};
cws.var.domain = location.host;
cws.var.basehost = location.protocol + "//" + cws.var.domain;
cws.var.re = {};
cws.var.date_default = 'Y-m-d';
cws.var.braceDelimiters = {'(':')', '{':'}', '[':']', '<':'>'};
cws.var.re.time = /\d+[\-\/\:]\d+/;

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
cws.dir = cws.get.dir(cws.var.basehost + location.pathname);
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
            return cws.var.basehost + link;
        } else {
            return link;
        }
    } else {
        return cws.dir + link;
    }
}
cws.get.ext = function(link){
    link = String(link);
    var m = link.match(/\.([^\.]*)$/)
    if (m) {
        m = m[1].match(/^\w*/);
        if (m)
            return m[0];
        else
            return '';
    } else {
        return '';
    }
}
// デフォルトで今日の日付
cws.get.date = function(format_str = '', date = new Date()){
    var d = date;
    switch (typeof(d)){
        case 'string':
            d = new Date(d);
            if (String(d) === "Invalid Date"){
                console.log(String(date) + ' <日付形式じゃないです>');
                return date;
            }
            break;
    }
    switch (typeof(format_str)){
        case 'string':
            if (format_str == '') format_str = cws.var.date_default;
            break;
        default:
            format_str = 'Y-m-d';
            break;
    }
    var rp = format_str;
    var year = d.getFullYear();
    rp = rp.replace(/Y/, year);
    rp = rp.replace(/y/, year.toString().slice(-2));
    var month = d.getMonth() + 1;
    rp = rp.replace(/n/, month);
    rp = rp.replace(/m/, ("0" + month).slice(-2));
    var day = d.getDate();
    rp = rp.replace(/j/, day);
    rp = rp.replace(/d/, ("0" + day).slice(-2));
    var week = d.getDay();
    rp = rp.replace(/w/, week);
    rp = rp.replace(/WW/, [ "日", "月", "火", "水", "木", "金", "土" ][week]);
    var hour = d.getHours();
    var hour2 = hour % 12;
    var hour2i = (hour / 12 < 1) ? 0 : 1;
    rp = rp.replace(/G/, hour);
    rp = rp.replace(/g/, hour2);
    rp = rp.replace(/H/, ("0" + hour).slice(-2));
    rp = rp.replace(/h/, ("0" + hour2).slice(-2));
    rp = rp.replace(/AA/, ["午前", "午後"][hour2i]);
    var minute = d.getMinutes();
    rp = rp.replace(/I/, minute);
    rp = rp.replace(/i/, ("0" + minute).slice(-2));
    var second = d.getSeconds();
    rp = rp.replace(/S/, second);
    rp = rp.replace(/s/, ("0" + second).slice(-2));

    rp = rp.replace(/A/, ["AM", "PM"][hour2i]);
    rp = rp.replace(/a/, ["am", "pm"][hour2i]);
    rp = rp.replace(/W/, [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ][week]);
    return rp;
}
cws.get.date_until = function(date = new Date()){
    d_until = new Date(cws.get.date('Y-m-dT00:00:00', date));
    d_until.setDate(date.getDate() + 1);
    d_until.setMilliseconds(d_until.getMilliseconds() - 1);
    return d_until;
}

// URLの?以降を取得する関数、更に取得したものを定義する
cws.get.query = function(href = location.href, auto_newDate = true) {
    let arg = new Object;
    const spl = href.split('?')
    if (spl.length === 1) return {};
    const qry = spl[spl.length - 1];
    const pair = qry.split('&');
    for (let i = 0; pair[i]; i++) {
        let kv = pair[i].split('=');
        let value = decodeURI(kv[1]);
        if (typeof(value) === 'string' && auto_newDate) {
            if (value === '') {}
            else if (value.match(cws.var.re.time)){
                try{
                    value = new Date(value);
                } catch(e) {}
            } else if (!isNaN(value)){
                value = Number(value);
            } else {
                try{
                    var e = eval(value);
                    switch (typeof(e)){
                        case 'number':
                        case 'string':
                        case 'undefined':
                            break;
                        default:
                            value = e;
                            break;
                    }
                } catch(e) {}
            }
        }
        arg[kv[0]] = value;
    }
    return arg;
}
cws.var.querys = cws.get.query();
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
    args = JSON.parse(cws.var.result);
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
                        writetxt = '<script type="text/javascript" id="' + id + '" class="' + cls + '" src="' + rpath + '"><\/script>';
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
cws.array = {};
cws.array.concat = function(array_a = {}, array_b = {}){
    Object.keys(array_b).forEach(function(value){
        array_a[value] = array_b[value];
    });
    return array_a;
}
// キーが存在するかどうかのチェック
cws.array.exists = function(key, obj = this){
    if (key === undefined)
        return false;
    else {
        return Object.keys(obj).indexOf(key) >= 0;
    }
}

cws.array.max_page = function(array, max = 200, reverse = false){
    var current = -1;
    var recursion = function(arg_array){
        if (reverse) arg_array = arg_array.reverse();
        return arg_array.filter((value) => {
            if (Array.isArray(value)){
                return recursion(value)
            } else {
                current++;
                return false;
            }
        });
    }
    recursion(array);
    return Math.floor(current / max) + 1;
}
cws.array.from_page = function(array = [], page = 1, max = 200){
    var r_array = [];
    var current = -1;
    var min_current = max * (page - 1);
    var max_current = max * page - 1;
    var recursion = function(arg_array){
        return arg_array.filter((value) => {
            if (Array.isArray(value)){
                return recursion(value)
            } else {
                current++;
                var r_bool = (min_current <= current) && (current <= max_current) ;
                if (r_bool){
                    r_array.push(value);
                }
                return r_bool;
            }
        });
    }
    recursion(array);
    return r_array;
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
cws.to.request_array = function(request_ary = null, path = cws.var.php_path){
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
cws.to.geturl = function(array_list = null, path = cws.var.php_path) {
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
cws.to.form = function(array_list = null, filename_list = null, formdata_obj = null, path = cws.var.php_path){
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
cws.to.herfWidth = function(strVal, other_replace = true){
    // 半角変換
    var halfVal = strVal.replace(/[！-～]/g,
    function( tmpStr ) {
        // 文字コードをシフト
        return String.fromCharCode( tmpStr.charCodeAt(0) - 0xFEE0 );
    }
    );
    if (other_replace) {
        // 文字コードシフトで対応できない文字の変換
        return halfVal.replace(/”/g, "\"")
        .replace(/’/g, "'")
        .replace(/‘/g, "`")
        .replace(/￥/g, "\\")
        .replace(/　/g, " ")
        .replace(/〜/g, "~");
    } else {
        return halfVal;
    }
}
cws.to.fullWidth = function(strVal, other_replace = true){
    // 半角変換
    var fullVal = strVal.replace(/[!-~]/g,
    function( tmpStr ) {
        // 文字コードをシフト
        return String.fromCharCode( tmpStr.charCodeAt(0) + 0xFEE0 );
    }
    );
    if (other_replace) {
        // 文字コードシフトで対応できない文字の変換
        return fullVal.replace(/”/g, "\"")
        .replace(/'/g, "’")
        .replace(/`/g, "‘")
        .replace(/\\/g, "￥")
        .replace(/ /g, "　")
        .replace(/~/g, "〜");
    } else {
        return fullVal;
    }
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
cws.get.location = function(href) {
    var l = document.createElement("a");
    l.href = href;
    return l;
};
cws.get.delimiter = function(re) {
    var delimiter = null;
    var braceDelimiters = cws.var.braceDelimiters;
    if (ret = re.match(/^([^a-zA-Z0-9\\]).*([^a-zA-Z0-9\\])[a-zA-Z]*$/)) {
        // デリミタが正しい組み合わせになっているかをチェック
        var [dummy, leftDlmt, rightDlmt] = ret;
        if (braceDelimiters[leftDlmt] && rightDlmt === braceDelimiters[leftDlmt] ||
            leftDlmt === rightDlmt
        ) {
            delimiter = leftDlmt;
        }
    }
    return delimiter;
}
cws.get.split_space = function(str = ''){
    return str.split(/\s+/).filter((value) => {return value !== ''});
}
cws.get.hook_search = function(keyword, w_mode = true){
    var hook_class = function(value = '', mode = '', mode_not = false){
        this.value = value;
        this.mode = String(mode);
        this.mode_not = Boolean(mode_not);
    }
    var hook_list = [];
    var hook_mode = '';
    var hook_not = false;
    var hook_value;
    var keywords = cws.get.split_space(keyword).map((v) => {
        this.escape = function(v){
            return (' ' + v).replace(/\\\\/g,"\\\?")
                .replace(/\\\|/g, "\\\:").replace(/\\\&/g, "\\\;").replace(/\\\ /g, "\\\_")
                .replace(/\|\|/g," OR ").replace(/\&\&/g," AND ").replace(/(\s+)\-/, ' NOT ')
                .replace(/^\s+/, "");
        }
        this.revive = function(v){
            return v.replace(/\\\:/g,"|").replace(/\\\;/g,"&")
                .replace(/\\\_/g," ").replace(/\\\?/g,"\\")
        }
        var ret = this.escape(v);
        var ret = cws.get.split_space(ret+' ').map((re) => {
        switch(re){
            case "OR":
                hook_mode = '|';
                return null;
            case "AND":
                hook_mode = '&';
                return null;
            case "NOT":
                hook_not = true;
                return null;
            default:
                var delimiter = cws.get.delimiter(re);
                var add_val = this.revive(re);
                // 正規表現だった場合の変形
                if (delimiter === '/'){
                    var v;
                    try {
                        add_val = eval(add_val);
                    } catch(e) {}
                }

                if (typeof(add_val) === 'string' && w_mode) {
                    var hw = cws.to.herfWidth(add_val);
                    var fw = cws.to.fullWidth(add_val);
                    if (hw !== fw) {
                        add_val = [new hook_class(hw, ''), new hook_class(fw, '|')];
                    }
                }
                if (hook_mode === '') {
                    // 追加
                    if (add_val !== ''){
                        hook_value = [new hook_class(add_val, '', hook_not)];
                        hook_list.push(hook_value);
                        hook_not = false;
                        return hook_value;
                    } else {
                        hook_not = false;
                        return null;
                    }
                } else {
                    // 前のリスト増分
                    if (add_val !== ''){
                        hook_value.push(new hook_class(add_val, hook_mode, hook_not));
                    }
                    hook_mode = '';
                    hook_not = false;
                    return null;
                }
                }
            }
        ).filter((v) => {return v !== null;});
        return ret;
    }).filter((v) => {return v.length > 0;});
    return keywords;
}
cws.get.search = function(keyword, str, w_mode = true) {
    var result = true;
    var keywords = keyword;
    if (!Array.isArray(keywords)) keywords = cws.get.hook_search(keywords, w_mode);
    
    var s_search = function(value){
        var or_trigger = false;
        var l_result = true, m_result;
        value.filter((hook) => {
            if (Array.isArray(hook)) {
                m_result = s_search(hook);
            } else {
                or_trigger = hook.mode === '|';
                if (Array.isArray(hook.value)) {
                    m_result = s_search(hook.value);
                } else {
                    m_result = str.match(hook.value);
                }
            }
            m_result = Boolean(m_result);
            m_result = (function(a, b){return Boolean((a & !b) | (!a & b));})(m_result, hook.mode_not);
            if (or_trigger) {
                l_result = l_result || Boolean(m_result);
                or_trigger = false;
            } else {
                l_result = l_result && Boolean(m_result);
            }
        });
        return l_result;
    }
    keywords.filter((v) => {
        result = result && s_search(v);
    });
    return result;
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
    insertstr = "<" + element + " id = '" + id + "' " + otherelm + ">" + inline + "<\/script>\n";
    if (insertobj.document !== undefined) {
        insertobj.document.write(insertstr);
    } else if(insertobj.getElementById !== undefined) {
        insertobj.lastElementChild.innerHTML += insertstr;
    } else if(insertobj.getElementsByName !== undefined) {
        insertobj.innerHTML += insertstr;
    }
}
// URLにクエリを書き出す、ページ移動はしない
cws.write.query = function(query, date_format = cws.var.date_default){
    var path = location.pathname;
    if (typeof(query)==='undefined') query = {};
    function query_equal(obj){
        return Object.keys(obj).map(function(value){
            var obj_value = obj[value];
            switch(typeof(obj_value)){
                case 'undefined':
                    obj_value = null;
                    break;
            }
            switch (toString.call(obj_value)){
                case '[object Date]':
                    if (String(obj_value) === "Invalid Date")
                        obj_value = null;
                    else
                        obj_value = cws.get.date(date_format, obj_value);
                    break;
            }
            if(obj_value !== null)
                return String(value) + '=' + obj_value;
            else
                return null;
        });
    }
    var query_str = query_equal(query)
        .filter((value) => {return value !== null}).join('&');
    if (query_str != '') query_str = '?' + query_str;
    var state_url = path + query_str;
    window.history.pushState(null, null, state_url);
    cws.var.querys = cws.get.query();
    return state_url;
}
cws.write.back = function(){
    window.history.back(-1);
    return false;
}
cws.write.style = function(css = '', id_or_object){
    var head = document.firstElementChild.firstElementChild;
    var css_link = Boolean(css.match(/\.css$|\.css\?/i));
    var elem;
    if (typeof(id_or_object) === 'string') {
        var getelem = document.getElementById(id_or_object);
        if (getelem === null) {
            if (css_link) {
                elem = document.createElement('link');
            } else {
                elem = document.createElement('style');
            }
            head.appendChild(elem);
            elem.id = id_or_object;
        } else {
            elem = getelem;
        }
    } else if (toString.call(elem) === "[object HTMLStyleElement]") {
        if (css_link) {
            if (elem.tagName === "LINK") {
                elem = id_or_object;
            } else {
                elem = document.createElement('link');
                head.appendChild(elem);
            }
        } else {
            if (elem.tagName === "STYLE") {
                elem = id_or_object;
            } else {
                elem = document.createElement('style');
                head.appendChild(elem);
            }
        }
    } else {
        if (css_link) {
            elem = document.createElement('link');
        } else {
            elem = document.createElement('style');
        }
        head.appendChild(elem);
    }
    if (css_link) {
        elem.setAttribute('src', css)
    } else {
        elem.innerHTML = css;
    }
    return elem;
}
cws.ajax = {};
cws.ajax.onload = function(){};
cws.ajax.result = {};
// argsの引数は "ansynch", "method", "form", "request", "catch", "type", "filelist"を検知、取得する
cws.ajax.run = function(target, onload, args = {}, opt = 0) {
    if (typeof(args) !== "object" || args === null) {args = {}};
    const catchfunc = cws.get.key(args, "catch", null);
    try {
        if (typeof(target) !== "string") target = cws.var.php_path;
        let ansynch = cws.get.key(args, "ansynch", true);
        if (ansynch === null) {
            ansynch = cws.var.defaultAnsynch;
        }
        let mtd = cws.get.key(args, "method", "POST").toUpperCase();
        let rq = cws.get.key(args, "request", {});
        let filename_list = cws.get.key(args, "filelist", {});
        if (toString.call(onload) !== "[object Function]") onload = cws.ajax.onload
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
            cws.ajax.result = {
                status: lxr.status,
                text: lxr.responseText,
                conmode: true,
            }
            if (lxr.status == 200 || lxr.status == 304) {
                onload(lxr.response, lxr);
            }
        }
        if (ansynch) {
            xr.onload = function() {
                localrun(this);
            }
            cws.var.conmode = false;
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
cws.ajax.json = function(target = cws.var.php_path, json = "", args = {}, opt = 0) {
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
        if (cws.var.domain === cws.get.domain(url)) {
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
    var getstr = storage[key];
    if (typeof(getstr) === "undefined") getstr = "";
    return getstr;
}

function obj2array(obj){
    return Object.keys(obj).map(function (key) {return obj[key]});
}
