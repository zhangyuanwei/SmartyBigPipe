__d("Pagelet",["Arbiter","Resource"],function(global, require, module, exports){

    var Arbiter = require("Arbiter"),
        Resource = require("Resource"),
        EVENT_TYPES = [
                "arrive",
                "beforeload",
                "cssresolved",
                "jsresolved",
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

            this.on("cssresolved", onCSSResolved, this);
            this._resolve(this.css, "cssresolved");

            function onCSSResolved() {
                if (this.emit("beforedisplay")) {
                    this.display();
                }
            }

            //this._resolve(this.js, "jsresolved");
        },
        _resolve: function(list, eventType) {
            var listCount, count, index, res;

            listCount = list.length;
            count = listCount;
            index = -1;

            if (count) {
                while (++index < count) {
                    res = Resource(list[index]);
                    res.on("resolve", onItemResolved, this);
                    res.load();
                }
            } else {
                this.done(eventType);
            }

            function onItemResolved() {
                if (!(--listCount)) {
                    this.done(eventType);
                }
            }
        },
        display: function() {
            this.state = STAT_DISPLAYED;
            getElementById(this.id).innerHTML = this.html;
            this.done("display");
            this.on("jsresolved", this.done, this, "load");
        }
    });

    return Pagelet;
});
/* __wrapped__ */
/* @wrap false */
