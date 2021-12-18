var urlList = document.getElementById("urlList");
var urlSetForm = document.getElementById("urlSetForm");
var urlListKey = "SwipeSpaceOffUrlList";
chrome.storage.local.get(urlListKey, (o) => {
    if (o[urlListKey] !== undefined) {
        urlList.value = o[urlListKey];
    }
});
urlList.onkeydown = (e) => {
    switch (e.code) {
        case "Enter":
        case "KeyS":
            if (e.ctrlKey) {
                urlSetForm.onsubmit();
                return false;
            }
            break;
    }
};
document.getElementById("addUrlList").onclick = () => {
    chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
        const list = document.getElementById("urlList");
        list.value = list.value.replace(/.?$/, (m0) => {
            var add = tabs[0].url + "\n";
            switch (m0) {
                case "":
                    return add;
                case "\n":
                    return m0 + add;
                default:
                    return m0 + "\n" + add;
            }
        });
    });
    return false;
};
document.getElementById("urlSetForm").onsubmit = () => {
    chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
        var msgadd = "urlSetForm," + urlList.value.replace(",", "\n");
        chrome.tabs.sendMessage(tabs[0].id, msgadd);
    });
    return false;
};
