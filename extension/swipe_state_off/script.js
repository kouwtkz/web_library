var urlListKey = "SwipeSpaceOffUrlList";
var sso_load = () => {
    var key = urlListKey;
    chrome.storage.local.get(key, (o) => {
        var str = o[key] !== undefined ? o[key] : "";
        var list = str.split("\n");
        if (
            list.some((url) => {
                if (url !== "") {
                    return location.href.match(url);
                }
            })
        ) {
            insert_link_element("css", "/assets/sso.css", key);
        } else {
            var elm = document.getElementById(key);
            if (elm) {
                elm.remove();
            }
        }
    });
};
sso_load();

chrome.runtime.onMessage.addListener(
    (request, sender, sendResponse = () => {}) => {
        var request_s = String(request).split(",", 5);
        switch (request_s[0]) {
            case "urlSetForm":
                var urlList = request_s[1];
                chrome.storage.local.set({
                    [urlListKey]: urlList,
                });
                sso_load();
                break;
        }
        sendResponse();
        return;
    }
);

function insert_link_element(tag, insert_path, id = "") {
    if (id !== "") {
        if (document.getElementById(id) !== null) return;
    }
    var url,
        elt = null;
    switch (tag.toLowerCase()) {
        case "css":
            url = chrome.extension.getURL(insert_path);
            elt = document.createElement("link");
            elt.href = url;
            elt.rel = "stylesheet";
            break;
        case "script":
            url = chrome.extension.getURL(insert_path);
            elt = document.createElement("script");
            elt.src = url;
            elt.type = "text/javascript";
            break;
    }
    if (elt !== null) {
        if (id !== "") elt.id = id;
        document.querySelector("head").appendChild(elt);
    }
}
