#!/usr/bin/node

/* __ignore__ */
var fs = require("fs");

(function(__node__, __file__, files) {
    var wrap = true;

    files = [].slice.call(arguments, 2);
    //console.log(files);

    files.forEach(function(file) {
        if (file === "-w") {
            wrap = true;
            return;
        } else if (file === "-u") {
            wrap = false;
            return;
        }

        var code = fs.readFileSync(file, "utf-8"),
            reg = new RegExp("\\brequire\\b\\s*\\(\\s*(\\\'|\\\")([^\\1]+?)\\1\\s*\\)", "g"),
            deps = [],
            output = [],
            module = file.substring((function(i) {
                return i < 0 ? 0 : i + 1
            })(file.lastIndexOf("/")), (function(i) {
                return i < 0 ? file.length : i
            })(file.lastIndexOf(".js"))),
            match;



        if (code.match(/__ignore__/)) {
            console.log("ignore \"" + file + "\"");
            return;
        }

        console.log((wrap ? "wrap" : "unwrap") + " \"" + file + "\"");
        if (code.match(/__wrapped__/)) {
            eval(code);
        }

        if (!wrap) {
            output = [code];
        } else {
            while (match = reg.exec(code)) {
                deps.push(match[2]);
            }
            //output.push("/**");
            //output.push(" * @" + "depend " + "\"amd.js\"");
            //deps.forEach(function(mod) {
            //    output.push(" * @" + "depend " + "\"" + mod + ".js\"");
            //});
            //output.push(" */");
            output.push("__d(\"", module, "\",", JSON.stringify(deps), ",function(global, require, module, exports){");
            output.push(code);
            output.push("});\n");
            output.push("/* __wrapped__ */");
        }
        fs.writeFileSync(file, output.join(""));


        function __d(name, deps, factory) {
            factory = factory.toString();
            code = factory.slice(factory.indexOf('{') + 1, -1);
        }
    });
}).apply(this, process.argv);
