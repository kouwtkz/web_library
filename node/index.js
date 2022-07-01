var request = (() => {
    var r = {};
    var Dec = (s) => {
        return decodeURIComponent(s.replace(/\+/g, " "));
    };
    Object.entries(
        JSON.parse(
            `{${(process.argv[2] || "")
                .replace(/([^=&]+)\=?([^&]*)/g, `"$1":"$2"`)
                .replace("&", ",")}}`
        )
    ).forEach((e) => {
        r[Dec(e[0])] = Dec(e[1]);
    });
    return r;
})();