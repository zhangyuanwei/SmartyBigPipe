__d("JSLoader",["Arbiter"],function(global, require, module, exports){
    var Arbiter = require("Arbiter"),
        EVENT_TYPES = ["load"],
        STAT_INITIALIZED = 1,
        STAT_LOADING = 2,
        STAT_LOADED = 3;

    function JSLoader(id, config) {
        Arbiter.call(this, EVENT_TYPES);
        this.id = id;
        this.url = config.src;
        this.state = STAT_INITIALIZED;
    }

    inherits(JSLoader, Arbiter, {
        load: function() {
            var self = this;
            if (this.state >= STAT_LOADING) return;
            this.state = STAT_LOADING;
            var element = document.createElement('script');
            element.src = this.url;
            element.async = true;
            element.onload = element.onerror = callback;
            element.onreadystatechange = function() {
                if (this.readyState in {
                    loaded: 1,
                    complete: 1
                }) {
                    callback();
                }
            };

            appendToHead(element);

            function callback() {
                self.state = STAT_LOADED;
                self.done("load");
            }
        }
    });

    return JSLoader;
});
/* __wrapped__ */
/* @wrap false */
