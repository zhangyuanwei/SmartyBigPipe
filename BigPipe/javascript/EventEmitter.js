__d("EventEmitter",["global"],function(global, require, module, exports){var global = require("global"),
    ALWAYS = 1,
    ONCE = 2,
    EVENT_TYPES = [
            "beforeadd"
    ];

function EventEmitter(types) {
    this.init(EVENT_TYPES.concat(types));
}

copyProperties(EventEmitter.prototype, {
    init: function(types) {

        var count = types.length,
            listenerMap = {},
            onceMap = {},
            type;

        while (count--) {
            type = types[count];
            listenerMap[type] = [];
            onceMap[type] = [];
        }

        this._listenerMap = listenerMap;
        this._onceMap = onceMap;
        this._types = types;
    },
    emit: function(type, args) {
        var listenerList = this._listenerMap[type],
            onceList = this._onceMap[type],
            list, count, index, callback, fn, context, args, ret = true;

        if (!listenerList && !onceList) return ret;

        this._onceMap[type] = [];

        args = slice(arguments, 1);
        list = listenerList.concat(onceList);
        count = list.length;
        index = -1;

        while (++index < count) {
            callback = list[index];
            ret = ( !! this.call.apply(this, [callback].concat(args))) && ret;
        }
        return ret;
    },
    call: function(callback, args) {
        var fn = callback[0],
            context = callback[1],
            args = callback.slice(2).concat(slice(arguments, 1));
        return fn.apply(context, args);
    },
    on: function(type, fn, context, args) {
        var listenerList = this._listenerMap[type],
            callback;
        if (!listenerList) return false;
        context = context || global;
        callback = [fn, context].concat(slice(arguments, 3));
        if (this.emit("beforeadd", type, ALWAYS, callback)) {
            listenerList.push(callback);
        }
    },
    once: function(type, fn, context, args) {
        var onceList = this._onceMap[type],
            callback;
        if (!onceList) return false;
        context = context || global;
        callback = [fn, context].concat(slice(arguments, 3));
        if (this.emit("beforeadd", type, ONCE, callback)) {
            onceList.push(callback);
        }
    },
    removeListener: function(type, fn, context) {
        //TODO
    },
    removeAllListener: function(type) {
        if (type === undefined) {
            this.init(this._types);
        } else {
            this._listenerMap[type] = [];
            this._onceMap[type] = [];
        }
    }
});

copyProperties(EventEmitter, {
    ALWAYS: ALWAYS,
    ONCE: ONCE
});

return EventEmitter;
});
/* __wrapped__ */