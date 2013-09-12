/*cmd业务框架实现*/
/*@cmd false*/
//@depend 'main.js'
(function(global, BigPipe) {

    var _module_map = {},
        _waiting_map = {},
        _lazy_modules = [],
        FLG_NONE = 0x00,
        FLG_AUTORUN = 0x01 << 0;

    function define(name, dependencies, factory, flag) {
        var mod, depCount, index, dep, depMod, waiting, refs;

        depCount = dependencies.length;

        mod = _waiting_map[name] || {};
        mod.name = name;
        mod.factory = factory;
        mod.deps = dependencies;
        mod.flag = flag || FLG_NONE;
        //等待模块个数
        mod.waiting = depCount;
        _module_map[name] = mod;
        delete _waiting_map[name];

        for (index = 0; index < depCount; index++) {
            dep = dependencies[index];
            depMod = _module_map[dep];
            if (!depMod) {
                depMod = _waiting_map[dep] = _waiting_map[dep] || {};
                waiting = true;
            } else {
                waiting = depMod.waiting;
            }

            if (!waiting) {
                mod.waiting--;
            } else {
                refs = depMod.refs = depMod.refs || [];
                refs.push(name);
            }
        }


        if (!mod.waiting) {
            resolve(mod);
        }
    }

    function resolve(mod) {
        var flag = mod.flag,
            refs = mod.refs,
            name = mod.name,
            index, count, mod;

        if (flag & FLG_AUTORUN) {
            require(name);
            delete _module_map[name];
        }

        if (refs) {
            for (index = 0, count = refs.length; index < count; index++) {
                mod = _module_map[refs[index]];
                if (mod && mod.waiting) {
                    if (!--mod.waiting)
                        resolve(mod);
                }
            }
        }
    }

    function require(name) {
        var module, exports, factory, deps, dep, args, index, count, ret;
        module = _module_map[name];

        if (!module) {
            throw new Error('Requiring unknown module "' + name + '"');
        }

        if (module.error) {
            throw new Error('Requiring module "' + name + '" which threw an exception');
        }

        if (module.waiting) {
            throw new Error('Requiring module "' + name + '" with unresolved dependencies');
        }

        if (module.hasOwnProperty("exports")) return module.exports;

        module.exports = exports = {};
        deps = module.deps;
        count = deps.length;
        index = -1;
        args = [];
        while (++index < count) {
            dep = deps[index];
            args.push(dep === "module" ? module : (dep === "exports" ? exports : require(dep)));
        }

        ret = module.factory.apply(this, args);

        if (module.exports !== exports) {
            exports = module.exports;
        } else if (ret !== undefined && ret !== exports) {
            module.exports = exports = ret;
        }

        return exports;
    }

    function requireAsync(dependencies, callback) {
        var count, depCount, index, dep;
        count = dependencies.length;
        depCount = count;
        if (count) {
            for (index = 0; index < count; index++) {
                dep = dependencies[index];
                //if (_module_map[dep]) {
                //    onModuleLoaded();
                //} else {
                BigPipe.loadModule(dep, onModuleLoaded);
                //}
            }
        } else {
            allReqiureLoaded();
        }

        function onModuleLoaded() {
            if (!--depCount) {
                allReqiureLoaded();
            }
        }

        function allReqiureLoaded() {
            var exports = [],
                index;
            for (index = 0; index < count; index++) {
                exports.push(require(dependencies[index]));
            }
            callback && callback.apply(this, exports);
            exports = null;
        }
    }

    var anonymousModuleId = 0;

    function anonymousModule() {
        return "__mod_" + anonymousModuleId++;
    }

    function requireLazy(dependencies, callback) {
        if (isReady) {
            define(anonymousModule(), dependencies, callback, FLG_AUTORUN);
            requireAsync(dependencies);
        } else {
            _lazy_modules.push([dependencies, callback]);
        }
    }

    function loadAllLazyModules() {
        var index, count, mod, deps, callback;
        for (index = 0, count = _lazy_modules.length; index < count; index++) {
            mod = _lazy_modules[index];
            deps = mod[0];
            callback = mod[1];
            define(anonymousModule(), deps, callback, FLG_AUTORUN);
            requireAsync(deps);
        }
        _lazy_modules = null;
    }

    var DOMContentLoaded,
        isReady = false;

    function ready() {
        if (!isReady) {
            isReady = true;
            loadAllLazyModules();
        }
    }

    // Cleanup functions for the document ready method
    if (document.addEventListener) {
        DOMContentLoaded = function() {
            document.removeEventListener("DOMContentLoaded", DOMContentLoaded, false);
            ready();
        };

    } else if (document.attachEvent) {
        DOMContentLoaded = function() {
            // Make sure body exists, at least, in case IE gets a little overzealous (ticket #5443).
            if (document.readyState === "complete") {
                document.detachEvent("onreadystatechange", DOMContentLoaded);
                ready();
            }
        };
    }

    function bindReady() {
        // Mozilla, Opera and webkit nightlies currently support this event
        if (document.addEventListener) {
            // Use the handy event callback
            document.addEventListener("DOMContentLoaded", DOMContentLoaded, false);

            // A fallback to window.onload, that will always work
            window.addEventListener("load", ready, false);

            // If IE event model is used
        } else if (document.attachEvent) {
            // ensure firing before onload,
            // maybe late but safe also for iframes
            document.attachEvent("onreadystatechange", DOMContentLoaded);

            // A fallback to window.onload, that will always work
            window.attachEvent("onload", ready);
        }
    }

    function __b(name, exports) {
        _module_map[name] = {
            exports: exports,
            waiting: 0
        };
    }

    function __d(name, dependencies, factory) {
        return define(name, ['global', 'module', 'exports', 'require', 'requireAsync', 'requireLazy'].concat(dependencies), factory);
    }

    require.__debug__ = _module_map;
    require.__waiting__ = _waiting_map;
    bindReady();
    __b("global", global);
    __b("module", 1);
    __b("exports", 1);
    __b("require", require);
    __b("requireAsync", requireAsync);
    __b("requireLazy", requireLazy);
    global.define = define;
    global.require = require;
    global.requireAsync = requireAsync;
    global.requireLazy = requireLazy;
    global.__d = __d;

    // for debug

    function bigpipeConsole() {
        BigPipe.log.apply(BigPipe, arguments);
    }
    if (!global.console) {
        global.console = {
            log: bigpipeConsole,
            error: bigpipeConsole,
            dir: bigpipeConsole
        };
    }
})(window, BigPipe);
