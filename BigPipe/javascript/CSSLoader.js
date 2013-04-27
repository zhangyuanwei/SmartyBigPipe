__d("CSSLoader",["Arbiter"],function(global, require, module, exports){
var Arbiter = require("Arbiter"),
    EVENT_TYPES = ["load"],
    STAT_INITIALIZED = 1,
    STAT_LOADING = 2,
    STAT_LOADED = 3,
    STAT_TIMEOUT = 4,
    pulling = false,
    hardpoint = null,
    cssMap = {},
    pullMap = {};

function CSSLoader(id, config) {
    Arbiter.call(this, EVENT_TYPES);
    this.id = id;
    this.url = config.src;
    this.state = STAT_INITIALIZED;
}

function getHardpoint() {
    if (!hardpoint) {
        var heads = document.getElementsByTagName('head');
        hardpoint = heads.length && heads[0] || document.body;
    }
    return hardpoint;
}

function doPullStyleSheet() {
    var id, last, now, list, element, style, loaded, count, index, item;
    last = 0;
    now = +new Date;
    for (id in pullMap) {
        last++;
        loaded = false;
        list = pullMap[id];
        element = list[0];
        style = window.getComputedStyle ? getComputedStyle(element, null) : element.currentStyle;
        if (style && parseInt(style.height, 10) > 1) {
            loaded = true;
        }

        index = 0;
        count = list.length;
        while (++index < count) {
            item = list[index];
            if (loaded) {
                item[1].call(item[2], true);
            } else if (item[0] < now) {
                item[1].call(item[2], false);
                list.splice(index, 1);
                index--;
                count--;
            }
        }

        if (loaded || count == 1) {
            element.parentNode.removeChild(element);
            delete pullMap[id];
            last--;
        }
    }

    if (last) {
        setTimeout(doPullStyleSheet, 20);
    } else {
        pulling = false;
    }
}

function startPull() {
    if (!pulling) {
        pulling = true;
        doPullStyleSheet();
    }
}

function pullStyleSheet(id, timeout, callback, context) {
    var callbackList, element;
    if (!(callbackList = pullMap[id])) {
        element = document.createElement("meta");
        element.id = "css_" + id;
        getHardpoint().appendChild(element);
        callbackList = [element];
        pullMap[id] = callbackList;
    }
    timeout = (+new Date) + timeout;
    callbackList.push([timeout, callback, context]);
    startPull();
}

function loadByCreateElement() {
    var id = this.id,
        url = this.url,
        link = document.createElement("link");
    link.rel = "stylesheet";
    link.type = "text/css";
    link.href = url;
    getHardpoint().appendChild(link);
    pullStyleSheet(id, 3000, pullStyleSheetCallback, this);
}

function pullStyleSheetCallback(success) {
    this.state = success ? STAT_LOADED : STAT_TIMEOUT;
    this.done("load", success);
}

function loadByCreateStyleSheet() {
	//TODO loadByCreateStyleSheet
}

inherits(CSSLoader, Arbiter, {
    load: function() {
        if (this.state < STAT_LOADING) {
            this.state = STAT_LOADING;
            this._load();
        }
    },
    //_load: document.createStyleSheet ? loadByCreateStyleSheet : loadByCreateElement
    _load: loadByCreateElement
});


return CSSLoader;
});
/* __wrapped__ */
