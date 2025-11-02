window.Flames = (window.Flames || {});

window.Flames.onRun = function() {
    window.Flames.onReadyAsync = function () {
        if (window.Flames.onReady !== undefined && window.Flames.onReady !== null) {
            window.Flames.onReady();
            return;
        }
        window.setTimeout(function() { window.Flames.onReadyAsync(); }, 1);
    }
    window.Flames.onReadyAsync();

    var autoBuildHashElement = document.querySelector('flames-autobuild');
    if (autoBuildHashElement !== null) {
        window.Flames.Internal.autoBuildHash = autoBuildHashElement.innerHTML;
        autoBuildHashElement.remove();

        window.Flames.Internal.runAutoBuild = function() {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.open('POST', '/flames/auto/build');
            xmlhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xmlhttp.onreadystatechange = function() {
                if ((xmlhttp.status == 200) && (xmlhttp.readyState == 4)) {
                    var data = JSON.parse(xmlhttp.responseText);
                    if (data.changed === false) {
                        window.setTimeout(function() { window.Flames.Internal.runAutoBuild(); }, 250);
                    } else {
                        var alert = document.body.insertAdjacentHTML('beforeend', '<div id="flames-changed-alert" style="position: fixed;  bottom: 0; right: 0; background-color: #202020; z-index: 999999999999; padding: 5px; border-radius: 5px; margin: 10px; color: #ffffff; opacity: 0; transition: opacity .25s ease-in-out">File changed detected. Reloading!</div>');
                        window.setTimeout(function() {
                            var flamesChangedAlertElement = document.getElementById('flames-changed-alert');
                            flamesChangedAlertElement.style.opacity = 1;
                        }, 1);
                        window.setTimeout(function() { window.location.reload(); }, 1000);
                    }
                }
            };
            xmlhttp.send(JSON.stringify({"hash": window.Flames.Internal.autoBuildHash}));
        }
        window.Flames.Internal.runAutoBuild();
    }
}

window.Flames.onBoot = function() {
    window.Flames.Internal.mockLog = {};
    window.Flames.Internal.mockLog.log = function(a,b,c) {console.log('[native]');console.log(a,b,c,e)};
    window.Flames.Internal.mockLog.error = function(a,b,c) {console.log('[native]');console.log(a,b,c)};
    window.Flames.Internal.mockLog.warn = function(a,b,c) {console.log('[native]');console.log(a,b,c)};
    window.Flames.Internal.log = false;
    window.Flames.Internal.environment = decodeURIComponent('{{ environment }}');
    window.Flames.Internal.dumpLocalPath = decodeURIComponent('{{ dumpLocalPath }}');
    window.Flames.Internal.dateTimeZone = decodeURIComponent('{{ dateTimeZone }}');
    window.Flames.Internal.asyncRedirect = '{{ asyncRedirect }}';
    window.Flames.Internal.composer = '{{ composer }}';
    window.Flames.Internal.swfExtension = '{{ swfExtension }}';
    window.Flames.Internal.appNativeKey = '{{ appNativeKey }}';
    window.Flames.Internal.onErrorListener = function(error, line, file, code, traceString, trace) {
        console.error(error);
        console.error(line);
        console.error(file);
        console.error(code);
        console.error(traceString);
        console.error(Flames.Internal.unserialize(trace));
    };

    window.Flames.Internal.onSuccessListener = function(data) {
        if (Flames.Internal.log === false) {
            return;
        }
        console.log(data);
    };

    window.Flames.Internal.dump = function(param1, param2) {
        if (window.Flames.Internal.dumpLocalPath === undefined) {
            param2 = param2.replace('{DUMP_LOCAL_PATH}', '');
            dump(param1, param2);
        } else {
            param2 = param2.replace('{DUMP_LOCAL_PATH}', Flames.Internal.dumpLocalPath);
            dump(param1, param2);
        }
    };

    window.dump = console.log;
    window.Flames.Internal.char = 'ロ';
    window.Flames.Internal.uid = 0;
    window.Flames.Internal.hashidfy = new Flames.Internal.Hashid('', 14,'abcdefghijklmnopqrstuvwxyz0123456789','');
    window.Flames.Internal.modules = [];
    window.Flames.Internal.tags = [];
    window.Flames.Internal.generateUid = (function() {
        window.Flames.Internal.uid += 1;
        return Flames.Internal.hashidfy.encode([Flames.Internal.uid]);
    });

    Array.prototype.toPhpSerialize = function() { return Flames.Internal.serialize(this); }
    DOMTokenList.prototype.toArray = function() { return this.value.split(' '); }
    NodeList.prototype.toPhpSerializeUids = function() {
        var array = [];
        for (var key in this) {
            if (this.hasOwnProperty(key)) {
                var element = this[key];
                var uid = element.getAttribute(Flames.Internal.char + 'uid');
                if (uid === null) {
                    uid = Flames.Internal.generateUid();
                    element.setAttribute(Flames.Internal.char + 'uid', uid);
                }
                array[array.length] = uid;
            }
        }
        return array.toPhpSerialize();
    }

    window.Flames.Internal.importModule = function(uri, hash) {
        if (window.Flames.Internal.modules[hash] !== undefined && window.Flames.Internal.modules[hash] !== null) {
            Flames.Internal.evalBase64(btoa('\\Flames\\Js\\Module::onLoad(\'' + hash + '\');'));
            return;
        }

        import(uri).then(function(module) {
            window.Flames.Internal.modules[hash] = module;
            Flames.Internal.evalBase64(btoa('\\Flames\\Js\\Module::onLoad(\'' + hash + '\');'));
        });
    }

    window.Flames.Internal.getModuleByHash = function(hash) {
        return window.Flames.Internal.modules[hash];
    }

    window.Flames.onRun();
}

window.Flames.onUnsupported = function() {
    '{{ unsupported }}';
};