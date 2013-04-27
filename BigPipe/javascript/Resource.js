__d("Resource", ["Arbiter", "CSSLoader"], function(global, require, module, exports) {
    var Arbiter = require("Arbiter"),
        resourceMap = {},
        resourceLoader = {},
        EVENT_TYPES = ["load", "resolve"],
        STAT_INITIALIZED = 1,
        STAT_LOADING = 2,
        STAT_LOADED = 3,
        STAT_RESOLVED = 4;

    function Resource(id, deps, serial) {
        if (this instanceof Resource) {
            Arbiter.call(this, EVENT_TYPES);
            this.id = id;
            this.deps = deps;
            this.serial = serial;

            this.state = STAT_INITIALIZED;
        } else {
            return getResource(id);
        }
    }

    function getResource(id) {
        var item, res, type;
        if (!(item = resourceMap[id])) throw new Error("resource \"" + id + "\" unknow.");
        if (!(res = item._handler)) {
            res = item._handler = new Resource(id, item.deps || [], item.serial || true);
        }
        return res;
    }

    inherits(Resource, Arbiter, {
        load: function() {
            var deps, depcount, count, index, dep,
                loading, //当前是否在加载状态
                loaded, //当前是否加载完成
                resolved; // 依赖是否已经解决
            if (this.state >= STAT_LOADING) return false;
            this.state = STAT_LOADING;



            loading = false;
            loaded = false;
            resolved = true;

            deps = this.deps;
            depcount = deps.length;
            count = depcount;

            if (count > 0) { // 有依赖的资源
                resolved = false;
                index = -1;
                while (++index < count) {
                    dep = Resource(deps[index]);
                    dep.on("resolve", onresolve, this);
                    dep.load();
                }
            }
            if (depcount === 0 // 依赖资源已经全部加载完成
            || !this.serial) { // 或者可以并行
                doload.call(this);
            }

            function onresolve(id) {
                if (!(--depcount)) { //依赖资源加载完成
                    resolved = true;
                    if (loaded) {
                        this.done("resolve");
                    } else {
                        doload.call(this);
                    }
                }
            }

            function doload() {
                var loader;
                if (!loaded && !loading) {
                    loading = true;
                    loader = getResourceLoader(this.id);
                    loader.on("load", onload, this);
                    loader.load();
                }
            }

            function onload() {
                loading = false;
                loaded = true;
                this.done("load");
                if (resolved) {
                    this.done("resolve");
                }
            }
        }
    });

    function registerLoader(type, loader) {
        resourceLoader[type] = loader;
    }

    function getResourceLoader(id) {
        var item, type, loader;
        item = resourceMap[id];
        type = item.type;
        if (!(loader = resourceLoader[type])) throw new Error("unknow type \"" + type + "\"");
        return new loader(id, item);
    }


    function setResourceMap(obj) {
        for (var id in obj) {
            resourceMap[id] = obj[id];
        }
    }


    copyProperties(Resource, {
        setResourceMap: setResourceMap
    });

    registerLoader("css", require("CSSLoader"));
    return Resource;
});
/* __wrapped__ */
