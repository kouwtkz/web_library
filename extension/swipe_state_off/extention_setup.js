if (typeof extension_parent_path === "undefined") {
    var extension_parent_path = document.currentScript.src.replace(
        /[^\/]+$/,
        ""
    );
}
function insert_element(
    tag,
    innerHTML = "",
    attr = "",
    parent_elm = null,
    rewrite = false
) {
    if (typeof attr === "object") {
        if (typeof attr.id === "undefined") {
            attr.id = "";
        }
    } else {
        attr = { id: attr };
    }
    var elt = null;
    if (attr.id !== "") {
        elt = document.getElementById(attr.id);
        if (elt !== null) {
            if (rewrite) {
                elt.remove();
                elt = null;
            } else {
                return null;
            }
        }
    }
    switch (tag.toLowerCase()) {
        case "css":
        case "style":
            elt = document.createElement("style");
            elt.innerHTML = innerHTML;
            document.head.appendChild(elt);
            break;
        case "js":
        case "script":
            elt = document.createElement("script");
            elt.innerHTML = innerHTML;
            elt.type = "text/javascript";
            document.head.appendChild(elt);
            break;
        default:
            if (parent_elm === null) {
                parent_elm = document.body;
            }
            if (tag === "") {
                elt = parent_elm;
                elt.innerHTML += innerHTML;
            } else {
                elt = document.createElement(tag);
                parent_elm.appendChild(elt);
                elt.innerHTML = innerHTML;
            }
            break;
    }
    if (elt !== null) {
        Object.keys(attr).forEach((k) => {
            switch (k) {
                case "id":
                    if (attr[k] !== "") elt.id = attr[k];
                    break;
                case "class":
                    attr[k].split(" ").forEach((e) => {
                        elt.classList.add(e);
                    });
                    break;
                default:
                    elt.setAttribute(k, attr[k]);
                    break;
            }
        });
    }
    return elt;
}
function insert_rewrite_element(
    tag,
    innerHTML = "",
    attr = "",
    parent_elm = null
) {
    return insert_element(tag, innerHTML, attr, parent_elm, true);
}
function insert_link_element(tag, insert_path, attr_id = "") {
    if (attr_id !== "") {
        if (document.getElementById(attr_id) !== null) return;
    }
    var elt = null, type = "text/plain";
    var url = insert_path;
    switch (tag.toLowerCase()) {
        case "css":
        case "style":
            elt = document.createElement("link");
            elt.href = url;
            elt.rel = "stylesheet";
            break;
        case "js":
        case "script":
            elt = document.createElement("script");
            elt.src = url;
            elt.type = "text/javascript";
            break;
        case "json":
            type = "application/json";
            break;
    }
    if (elt === null) {
        elt = document.createElement("object");
        elt.type = type;
        elt.data = url;
    }
    if (attr_id !== "") elt.id = attr_id;
    document.head.appendChild(elt);
    return elt;
}
