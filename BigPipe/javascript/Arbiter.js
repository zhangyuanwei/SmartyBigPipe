__d("Arbiter",["global"],function(global, require, module, exports){
    var global = require("global");

    function Arbiter(types) {
        this._listenerMap = {};
        this._setup(types);
    }

    function call(callback, args) {
        var fn = callback[0],
            context = callback[1],
            args = callback[2].concat(args);
        return fn.apply(context, args);
    }

    copyProperties(Arbiter.prototype, {
        _setup: function(types) {
            var count = types.length,
                listenerMap = this._listenerMap;
            while (count--) {
                listenerMap[types[count]] = {
                    args: null,
                    cbs: []
                };
            }
        },
        on: function(type, fn, context) {
            var listenerList = this._listenerMap[type],
                callback, args;
            if (!listenerList) return false;
            context = context || global;
            callback = [fn, context, slice(arguments, 3)];
            if (args = listenerList.args) {
                call(callback, args);
            } else {
                listenerList.cbs.push(callback);
            }
            return true;
        },
        done: function(type, args) {
            var listenerList = this._listenerMap[type],
                ret, cbs, count;
            if (!listenerList) return true;
            cbs = listenerList.cbs;
            count = cbs.length;
            args = slice(arguments, 1);
            ret = this.emit.apply(this, slice(arguments, 0));
            listenerList.args = args;
            listenerList.cbs = cbs.slice(count);
            return ret;
        },
        emit: function(type, args) {
            var listenerList = this._listenerMap[type],
                cbs, count, index, ret;
            if (!listenerList) return true;
            args = slice(arguments, 1);
            cbs = listenerList.cbs;
            count = cbs.length;
            index = -1;
            ret = true;
            while (++index < count) {
                ret = call(cbs[index], args) && ret;
            }
            return !!ret;
        },
        undo: function(type) {
            var listenerList = this._listenerMap[type];
            if (!listenerList) return false;
            listenerList.args = null;
        }
    });

    return Arbiter;
});
/* __wrapped__ */
