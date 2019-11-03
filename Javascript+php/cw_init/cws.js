if (typeof(cws) === 'undefined') var cws = {};
cws.to = {}
// 配列の要素と内容を入れ替える
cws.to.turnover = function(obj){
    var retv = {};
    Object.keys(obj).filter(
    (value) => {retv[obj[value]] = value; return false;});
    return retv;
}
if (typeof(cws.var) === 'undefined') cws.var = {};
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
cws.var.use_cookie = false;
cws.var.input_list = {
    'hidden': 3, 'text': 3, 'search': 5, 'tel': 5, 'url': 5, 'email': 5, 'password': 3,
    'datetime': 5, 'date':5, 'month': 5, 'week': 5, 'time': 5, 'datetime-local': 5,
    'number': 5, 'range': 5, 'range': 5, 'color': 5, 'checkbox': 3,
    'radio': 3, 'file': 3, 'submit': 3, 'image': 3, 'reset': 3, 'button': 1};

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
    const spl = href.split('?');
    if (spl.length === 1) return {};
    const qry = spl[spl.length - 1];
    const pair = qry.split('&');
    for (let i = 0; pair[i]; i++) {
        let kv = pair[i].split('=');
        let value = decodeURI(kv[1]);
        if (typeof(value) === 'string' && auto_newDate) {
            if (value === '') {}
            else if (value.match(cws.var.re.time)){
                let newDate = new Date(value);
                if (newDate.toString() !== "Invalid Date") value = newDate;
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
// PHPのstrtotimeの再現
cws.to.strtotime = function(time = ''){
    let second = 0, minute = 0, hour = 0;
    let day = 0, week= 0, month = 0, year = 0;
    let re, m;
    re = /([\+\-]?[\d]+)\s*seconds?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        second = Number(m[1]);
    }
    re = /([\+\-]?[\d]+)\s*minutes?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        minute = Number(m[1]);
    }
    re = /([\+\-]?[\d]+)\s*hours?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        hour = Number(m[1]);
    }
    re = /([\+\-]?[\d]+)\s*days?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        day = Number(m[1]);
    }
    re = /([\+\-]?[\d]+)\s*weeks?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        week = Number(m[1]);
    }
    re = /([\+\-]?[\d]+)\s*months?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        month = Number(m[1]);
    }
    re = /([\+\-]?[\d]+)\s*years?/; m = time.match(re);
    if (m) {
        time = time.replace(re, '');
        year = Number(m[1]);
    }
    time = new Date(time);
    if (time.toString() === "Invalid Date") {
        time = new Date();
    }
    time.setFullYear(time.getFullYear() + year, time.getMonth() + month, time.getDate() + day + 7 * week);
    time.setHours(time.getHours() + hour, time.getMinutes() + minute, time.getSeconds() + second);
    return time;
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
cws.get.hook_search = function(keyword, tag_mode = false, w_mode = false){
    var hook_class = function(value = '', mode = '', mode_not = false, mode_tag = tag_mode){
        this.value = value;
        this.mode = String(mode);
        this.mode_not = Boolean(mode_not);
        this.mode_tag = Boolean(mode_tag);
    }
    var hook_list = [];
    var hook_mode = '';
    var hook_not = false;
    var hook_tag = tag_mode;
    var hook_value;
    var keywords = cws.get.split_space(keyword).map((v) => {
        this.escape = function(v){
            return (' ' + v).replace(/\\\\/g,"\\\?")
                .replace(/\\\|/g, "\\\:").replace(/\\\&/g, "\\\;").replace(/\\\ /g, "\\\_")
                .replace(/\|\|/g," OR ").replace(/\&\&/g," AND ").replace(/(\s+)\-/g, ' NOT ').replace(/(\s+)\#/g, ' TAG ')
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
            case "TAG":
                hook_tag = true;
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
                        add_val = [new hook_class(hw, '', false, hook_tag), new hook_class(fw, '|', false, hook_tag)];
                    }
                }
                if (hook_mode === '') {
                    // 追加
                    if (add_val !== ''){
                        hook_value = [new hook_class(add_val, '', hook_not, hook_tag)];
                        hook_list.push(hook_value);
                        hook_not = false;
                        hook_tag = false;
                        return hook_value;
                    } else {
                        hook_not = false;
                        hook_tag = false;
                        return null;
                    }
                } else {
                    // 前のリスト増分
                    if (add_val !== ''){
                        hook_value.push(new hook_class(add_val, hook_mode, hook_not, hook_tag));
                    }
                    hook_mode = '';
                    hook_not = false;
                    hook_tag = false;
                    return null;
                }
                }
            }
        ).filter((v) => {return v !== null;});
        return ret;
    }).filter((v) => {return v.length > 0;});
    return keywords;
}
cws.get.search = function(subject, keyword) {
    var str = " " + subject.replace(/[\[](.*)[\]]/, " [ $1 ] ").replace("/[\#\s]+/g"," ") + " ";
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
                    var hook_val = hook.value;
                    if (typeof(hook_val) === "string") {
                        if (hook.mode_tag) {
                            hook_val = " " + hook_val + " ";
                        }
                    }
                    m_result = str.match(hook_val);
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
// form = null              form、名前の場合は名前検索を行う、なければ作る
// attribute = {}           formタグの要素配列、自動的に入れる
// action = ''              formの実行先
// method = 'POST'          formの実行方式
// parent = body            挿入先、指定なしでbodyタグに入れる
// hidden = false           新たに挿入するformを隠す
// hidden_new_input = false 新たに挿入するform内の要素を隠す
// document = document      documentオブジェクト、変えることはあまりないかも
// data [[value], [name, value], [type or tag, name, value]]
// data [{0:value}, {0:name, 1:value}, {0:type or tag, 1:name, 2:value}]
cws.write.form = function(data = {}, args = {}){
    var str, m;
    var new_instance = false;
    if (typeof(data) !== "object" || data === null) {data = {}};
    if (typeof(args) !== "object" || args === null) {args = {}};
    var doc = document;
    if (typeof(args.document) === 'object') { doc = args.document; }
    var hidden = (typeof(args.hidden) === 'boolean') ? args.hidden : false;
    var hidden_new_input = (typeof(args.hidden_new_input) === 'boolean') ? args.hidden_new_input : false;
    var id = null;
    var local_form = null;
    var form_typeof = typeof(args.form);
    if (form_typeof === 'object') {
        form_typeof = Object.prototype.toString.call(args.form);
        if (form_typeof === '[object HTMLFormElement]') {
            if (args.form.tagName === 'FORM') {
                local_form = args.form;
            }
        }
    } else if (form_typeof !== 'undefined') {
        local_form = 'form';
        if (form_typeof === 'string'){
            if (args.form !== '') local_form = args.form;
            str = local_form;
            local_form = doc.querySelector(str);
            if (local_form === null) {
                m = str.match(/([^\#]*)$/);
                if (m !== null) m = m[1].match(/^([^\.\s]*)/);
                if (m !== null) id = m[1];
            }
        } else if (form_typeof === 'number') {
            local_form = cws.get.key(doc.querySelectorAll(local_form), args.form, null);
        } else {
            local_form = null;
        }
    }
    if (local_form === null) {
        local_form = doc.createElement('form');
        new_instance = true;
        if (id !== null) {
            local_form.id = id
        }
        else {
            id = '';
        }
    } else {
        id = local_form.id;
    }
    if (hidden) {
        local_form.style.display = 'none';    
    } else {
        if (local_form.style.display === 'none') local_form.style.display = '';
    }
    if (typeof(args.action) === 'string') {
        local_form.action = args.action;
    }
    if (typeof(args.method) === 'string') {
        local_form.method = args.method;
    } else {
        local_form.method = 'POST';
    }

    var local_parent = null;
    var parent_typeof = typeof(args.parent);
    if (parent_typeof === 'object') {
        parent_typeof = Object.prototype.toString.call(args.parent);
        if (parent_typeof === '[object HTMLFormElement]') {
            local_parent = args.parent;
        }
    } else {
        if (parent_typeof === 'string'){
            if (args.parent !== '') {
                local_parent = doc.querySelector(args.parent);
            } else {
                local_parent = null;
            }
        }
    }
    if (local_parent === null) local_parent = doc.querySelector('body');
    if (new_instance) {
        local_parent.append(local_form);
    }
    var attribute = (typeof(args.attribute) !== "object" || args.attribute === null) ? {} : args.attribute;
    Object.keys(attribute).forEach((k)=>{
        local_form.setAttribute(k, attribute[k]);
    });
    Object.keys(data).forEach((k)=>{
        var wait = 0;
        var args = data[k];
        var local_input = null;
        var input_typeof = typeof(args);
        if (input_typeof === 'object') {
            input_typeof = Object.prototype.toString.call(local_input);
            if (input_typeof === '[object HTMLDocument]') {
                local_input = args;
            } else {
                if (args)
                var create_tag = 'input';
                var do_setattr_type = '';
                var keys = Object.keys(args);
                var arg0_enable = typeof(args[0]) !== 'undefined';
                var arg1_enable = typeof(args[1]) !== 'undefined';
                var arg2_enable = typeof(args[2]) !== 'undefined';
                var arg02_enable = arg0_enable && arg1_enable && arg2_enable;

                if (arg02_enable) {
                    var input_type_num = cws.get.key(cws.var.input_list, args[0], 0);
                    if (input_type_num > 2) {
                        do_setattr_type = args[0].toString();
                    } else {
                        create_tag = args[0];
                    }
                }
                if (typeof(args['tag']) !== 'undefined') {
                    create_tag = args['tag'].toString();
                }
                local_input = doc.createElement(create_tag);
                if (do_setattr_type !== '') local_input.setAttribute('type', do_setattr_type);
                if (arg0_enable) {
                    if (!arg1_enable) {
                        local_input.setAttribute('value', args[0]);
                    } else if (!arg2_enable) {
                        local_input.setAttribute('name', args[0]);
                        local_input.setAttribute('value', args[1]);
                    } else {
                        local_input.setAttribute('name', args[1]);
                        local_input.setAttribute('value', args[2]);
                    }
                }
                keys.forEach((k)=>{
                    if (isNaN(Number(k))) {
                        local_input.setAttribute(k, args[k]);
                    }
                });
            }
        } else {
            local_input = doc.createElement('input');
            if (isNaN(Number(k))) {
                local_input.setAttribute(k, args);
            } else {
                local_input.setAttribute('value', args);    
            }
        }
        if (hidden_new_input) {
            local_input.style.display = 'none';    
        } else {
            if (local_input.style.display === 'none') local_input.style.display = '';
        }
        local_form.append(local_input);
    });
}
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
// do_overwriteは既存の値に書き出す
cws.write.query = function(query, do_pushstate = true, do_overwrite = false,
        href = location.href, date_format = cws.var.date_default){
    var path = href.match(/([^\?]*)/)[1];
    if (typeof(query)==='undefined') query = {};
    if (do_overwrite) query = cws.array.concat(cws.get.query(href), query);
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
    if (do_pushstate) window.history.pushState(null, null, state_url);
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
        elem.setAttribute('rel', 'stylesheet')
        elem.setAttribute('src', css)
        elem.setAttribute('type', 'text/css')
    } else {
        elem.innerHTML = css;
    }
    return elem;
}
cws.ajax = {};
cws.ajax.onload = function(){};
cws.ajax.result = {};
// targetにフォームエレメントを指定した時、Actionとフォームのデータを自動的に取得する
// argsの引数は主に"request"を取る、他に"ansynch", "method", "form", "catch", "type", "filelist"を取得する
cws.ajax.run = function(target, onload = null, args = {}, opt = 0) {
    let target_check = null, fm = null;
    if (typeof(args) !== "object" || args === null) {args = {}};
    const catchfunc = cws.get.key(args, "catch", null);
    try {
        target_check = cws.ajax.form(target);
        if (target_check === null) {
            if (typeof(target) !== "string") {
                target = cws.var.php_path;
            }
        }
        target_check = cws.ajax.form(cws.get.key(args, "form", target_check));
        if (target_check !== null) {
            fm = target_check;
            if (typeof(target) !== "string") target = fm.action;
        }
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
cws.ajax.form = function(data){
    const data_callname = Object.prototype.toString.call(data);
    if (data_callname === '[object HTMLFormElement]') {
        const _formdata = new FormData(data);
        _formdata.action = data.action;
        return _formdata;
    } else if(data_callname === '[object FormData]') {
        if (typeof(data.action) === 'undefined') data.action = '';
        return data;
    } else {
        return null;
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

cws.storage = {};
cws.storage.out = function(key, value = null) {
    const storage = sessionStorage;
    storage.removeItem(key);
    if (value !== null) {
        storage.setItem(key, value);
    }
}
cws.storage.remove = function(key) {
    cws.storage.out(key);
}
cws.storage.get = function(key) {
    const storage = sessionStorage;
    var getstr = storage[key];
    if (typeof(getstr) === "undefined") getstr = "";
    return getstr;
}

// Cookieの書き出しは制限、読み込みは制限しない
if (typeof(cws.cookie) === 'undefined') cws.cookie = {};
cws.cookie.out = function(key, value = 0, time = '') {
    if (cws.var.use_cookie) {
        let setDate = '';
        if (value === null) {
            value = 0;
            setDate = ';max-age=0';
        } else if (time === '' || time === null) {
            setDate = ';max-age=999999999';
        } else if (time.match(/^[\+\-]?[\d]+$/)) {
            setDate = ';max-age=' + time;
        } else {
            setDate = ';expires=' + cws.to.strtotime(time).toGMTString();
        }
        document.cookie = key + '=' + value + setDate;
        return document.cookie;
    }
}
cws.cookie.remove = function(key) {
    return cws.cookie.out(key, null);
}
cws.cookie.get = function(key = null) {
    if (key === null) {
        return document.cookie;
    }
    const cookie = ' ' + document.cookie + ';';
    const re_key = new RegExp(' ' + key + '=([^;]+)');
    var m = cookie.match(re_key);
    if (m) {
        return m[1];
    } else {
        return null;
    }
}

function obj2array(obj){
    return Object.keys(obj).map(function (key) {return obj[key]});
}


cws.var.global_init = function() {
    if (typeof(cws_cookie_use) === 'boolean') cws.cookie_use = cws_cookie_use;
}
