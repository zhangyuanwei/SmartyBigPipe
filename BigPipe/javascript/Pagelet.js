__d("Pagelet", ["Arbiter", "Resource"], function(global, require, module, exports) {

    var Arbiter = require("Arbiter"),
        Resource = require("Resource"),
        EVENT_TYPES = [
                "arrive",
                "beforeload",
                "beforedisplay",
                "display",
                "load",
                "beforeunload",
                "unload"
        ],
        STAT_UNINITIALIZED = 0,
        STAT_INITIALIZED = 1,
        STAT_LOADING = 2,
        STAT_DISPLAYED = 3,
        pageletSet = {};

    function Pagelet(id) {
        if (this instanceof Pagelet) {
            Arbiter.call(this, EVENT_TYPES);
            this.id = id;
            this.state = STAT_UNINITIALIZED;
        } else {
            var pagelet = pageletSet[id];
            if (!pagelet) {
                pagelet = new Pagelet(id);
                pageletSet[id] = pagelet;
            }
            return pagelet;
        }
    }

    inherits(Pagelet, Arbiter, {
        arrive: function(config) {
            copyProperties(this, {
                html: config.html || "",
                css: config.css || [],
                js: config.js || [],
                parent: config.parent || null,
                children: config.children || [],
                state: STAT_INITIALIZED
            });
            this.done("arrive");
            if (this.emit("beforeload")) {
                this.load();
            }
        },
        load: function() {
            if (this.state >= STAT_LOADING) return false;
            this.state = STAT_LOADING;

            var css, cssCount, js, jsCount, count, index, res,
                displayed = false;

            css = this.css;
            cssCount = css.length;

            js = this.js;
            jsCount = js.length;

            count = cssCount;
            index = -1;

            if (count) {
                while (++index < count) {
                    res = Resource(css[index]);
                    res.on("resolve", onCssLoaded, this);
                    res.load();
                }
            } else {
                display.call(this);
            }

            function onCssLoaded() {
                if (!(--cssCount) && !displayed) {
                    display.call(this);
                }
            }

            function display() {
                displayed = true;
                if (this.emit("beforedisplay")) {
                    this.display();
                }
            }
        },
        display: function() {
            this.state = STAT_DISPLAYED;
            getElementById(this.id).innerHTML = this.html;
            this.done("display");
        }
    });

    return Pagelet;
    /*
    EVENT_TYPES = [
            "arrive",
            "beforeload",
            "beforedisplay",
            "display",
            "load",
            "beforeunload",
            "unload"
    ],
    pageletSet = {};

function Pagelet(id) {
    Arbiter.call(this, EVENT_TYPES);
    this.id = id;
    this.state = STAT_UNINITIALIZED;
}

inherits(Pagelet, Arbiter, {
    arrive: function() {
        this.done("arrive");
        this.load();
    },
    load: function() {
        if (!this.done("beforeload")) return;
        this.state = STAT_LOADING;
        var css = this.css,
            cssCount = css.length,
            js = this.js,
            jsCount = js.length,
            parentDisplayed = true,
            parentNode, count, index, res;

        index = -1;
        count = cssCount;

        while (++index < count) {
            res = Resource.getResource(css[index]);
            if (res.loaded) {
                cssCount--;
            } else {
                res.on("resolve", cssResolved, this);
                res.load();
            }
        }
//            if (this.parent) {
//                parentNode = getPagelet(this.parent);
//                if (parentNode.state < STAT_DISPLAYED) {
//                    parentDisplayed = false;
//                    parentNode.once("display", parentLoadCallback, this);
//                }
//            }
        tryDisplay.call(this);

        function cssResolved() {
            cssCount--;
            tryDisplay.call(this);
        }

        function parentLoadCallback() {
            parentDisplayed = true;
            tryDisplay.call(this);
        }

        function tryDisplay() {
            if (cssCount == 0 && parentDisplayed == true) {
                this.display();
            }
        }
    },
    display: function() {
        if (!this.done("beforedisplay")) return;
        this.state = STAT_DISPLAYED;
        getElementById(this.id).innerHTML = this.html;
        this.done("display");
    },
    destory: function() {

    }
});

function getPagelet(id) {
    var pagelet = pageletSet[id];
    if (!pagelet) {
        pagelet = new Pagelet(id);
        pageletSet[id] = pagelet;
    }
    return pagelet;
}

function createPagelet(id, parent, content, children, css, js) {
    var pagelet = getPagelet(id);
    pagelet.parent = parent;
    pagelet.html = content;
    pagelet.children = children;
    pagelet.css = css;
    pagelet.js = js;
    pagelet.state = STAT_INITIALIZED;
    return pagelet;
}

copyProperties(Pagelet, {
    createPagelet: createPagelet,
    getPagelet: getPagelet
});

return Pagelet;
*/
});
/* __wrapped__ */
