/** 
 *           File:  main.js
 *           Path:  BigPipe/javascript
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-25 16:59:51  
 *    Description: 
 *
 *    __ignore__ 
 */
/* @cmd false */
(function(global, window, document, undefined) {
    //@import 'util/util.js'
    //@import 'Arbiter.js'
    //@import 'Resource.js'
    //@import 'Pagelet.js'
    //@import 'BigPipe.js'
    //@import 'CSSLoader.js'
    //@import 'JSLoader.js'
    //@import 'Emulator.js'
    //@import 'Requestor.js'
    //@import 'Controller.js'
    var _BigPipe = global["BigPipe"],
        BigPipe = require("BigPipe");

    global["BigPipe"] = new BigPipe();
})(this, window, document);
