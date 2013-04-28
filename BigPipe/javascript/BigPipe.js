__d("BigPipe",["Resource","Pagelet"],function(global, require, module, exports){
    var Resource = require("Resource"),
        Pagelet = require("Pagelet"),
        inited = false;

    function getContentFromContainer(id, doc) {
        var elem = getElementById(id, doc),
            child, html;
        if (!(child = elem.firstChild)) return null; //TODO
        if (child.nodeType !== 8) return null; //TODO
        html = child.nodeValue;
        elem.parentNode.removeChild(elem);
        html = html.slice(1, -1);
        return html.replace(/--\\>/g, "-->");
    }

    function getContent(obj) {
        if (obj.content) return obj.content;
        if (obj.container_id) return getContentFromContainer(obj.container_id, obj.doc);
        return null;
    }

    function init() {
        if (inited) throw new Error("BigPipe has been initialized.");
        inited = true;
    }

    /**
     * onPageletArrive 当页面区块到达时处理函数 {{{
     * 
     * @param obj {Object} Pagelet 信息
     * @access public
     * @return void
     */
    function onPageletArrive(obj) {
        var id, parent, content, children, css, js, pagelet, hook, type, list, i, count;
        if (!inited) init();
        id = obj.id;
        Resource.setResourceMap(obj.resource_map);
        obj.html = getContent(obj);
        pagelet = Pagelet(id);
        if (hook = obj.hook) {
            for (type in hook) {
                list = hook[type];
                count = list.length;
                i = -1;
                while (++i < count) {
                    pagelet.on(type, new Function(list[i]), pagelet);
                }
            }
        }
        pagelet.arrive(obj);
    } // }}}

    return {
        init: init,
        onPageletArrive: onPageletArrive
    };
});
/* __wrapped__ */
/* @wrap false */