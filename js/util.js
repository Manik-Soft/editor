(function() {
    'use strict';
    Util.fileReader = function() {
        var init = function(types, readAs, callBack, multy) {
            multy = typeof multy == 'undefined' ? false : multy;
            var input = document.createElement('input');
            input.style.cssText = 'display: none;';
            input.type = "file";
            if (multy) input.multiple = true;
            document.querySelector('body').appendChild(input);
            input.addEventListener('change', selectFiles, false);
            input.click();

            function selectFiles(evt) {
                var files = (evt.target || evt.sourceElement).files;
                removeElement(input);
                for (var i = 0, f; f = files[i]; i++) {
                    if (!f.name.toLowerCase().match('(.)(.*(' + types.toLowerCase() + '))')) {
                        continue;
                    }
                    var reader = new FileReader();
                    reader.onload = (function(theFile) {
                        return function(e) {
                            callBack({
                                data: e.target.result,
                                name: theFile.name,
                                size: theFile.size
                            });
                        };
                    })(f);
                    switch (readAs) {
                        case 'dataURL':
                            reader.readAsDataURL(f);
                            break;
                        case 'binary':
                            reader.readAsBinaryString(f);
                            break;
                        case 'array':
                            reader.readAsArrayBuffer(f);
                            break;
                        default:
                            reader.readAsText(f);
                    }
                }
            }
        };

        function removeElement(el) {
            el && el.parentNode && el.parentNode.removeChild(el);
        }
        return {
            Init: function(types, readAs, callBack, multy) {
                init(types, readAs, callBack, multy);
            }
        };
    };
    Util.Ajax = function() {
        var xr = function() {
            if (typeof XMLHttpRequest !== 'undefined') {
                return new XMLHttpRequest();
            }
            var versions = ["MSXML2.XmlHttp.5.0", "MSXML2.XmlHttp.4.0", "MSXML2.XmlHttp.3.0", "MSXML2.XmlHttp.2.0", "Microsoft.XmlHttp"];
            var xhr;
            for (var i = 0; i < versions.length; i++) {
                try {
                    xhr = new ActiveXObject(versions[i]);
                    break;
                } catch (e) {}
            }
            return xhr;
        };
        var send = function(url, callBack, method, data, sync, callBackProgress) {
            var x = xr();
            x.open(method, url, sync);
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    this.data = data;
                    callBack(this);
                }
            };
            x.onprogress = function(e) {
                if (e.lengthComputable && typeof callBackProgress == 'function') {
                    callBackProgress(e);
                }
            };
            if (method == 'POST') {
                x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            }
            x.send(data);
        };
        var run = function(type, url, data, callBack, sync, callBackProgress) {
            var query = [];
            for (var key in data) {
                if (typeof data[key] === 'object' && type == 'POST') data[key] = JSON.stringify(data[key]);
                query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            if (type == 'POST') {
                send(url, callBack, 'POST', query.join('&'), sync, callBackProgress);
            } else {
                send(url + '?' + query.join('&'), callBack, 'GET', null, sync, callBackProgress);
            }
        };
        return {
            GET: function(url, data, callBack, sync, callBackProgress) {
                run('GET', url, data, callBack, sync, callBackProgress);
            },
            POST: function(url, data, callBack, sync, callBackProgress) {
                run('POST', url, data, callBack, sync, callBackProgress);
            }
        };
    };
    Util.fullScreen = {
        isFullScreen: false,
        toggleFullScreen: function(selector) {
            if (!this.isFullScreen) {
                this.goFullScreen(selector);
            } else {
                this.exitFullScreen();
            }
            return this.isFullScreen;
        },
        goFullScreen: function(selector) {
            var element;
            if (typeof selector != 'object') {
                element = document.querySelector(selector);
            } else {
                element = selector;
            }
            if (element.requestFullScreen) {
                element.requestFullScreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullScreen) {
                element.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
            this.isFullScreen = true;
        },
        exitFullScreen: function() {
            if (document.exitFullScreen) {
                document.exitFullScreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            this.isFullScreen = false;
        }
    };
    /* ---------------------  Audio ---------------------------*/
    Util.Tone = {
        inTone: new Audio(),
        outTone: new Audio(),
        interval: null,
        init: function() {
            this.inTone.src = "snd/ring.mp3";
            this.inTone.loop = true;
            this.outTone.src = "snd/Ringing.mp3";
        },
        inTonePlay: function() {
            this.init();
            this.inTone.play();
        },
        inToneStop: function() {
            this.inTone.pause();
        },
        outTonePlay: function() {
            this.init();
            var _tone = this;
            this.outTone.play();
            this.interval = setInterval(function() {
                _tone.outTone.play();
            }, 4000);
        },
        outToneStop: function() {
            this.outTone.pause();
            clearInterval(this.interval);
        }
    };
    /*-------------------Helper Extensions------------------*/
    Util.bringToFront = function(item, groupSelector) {
        var elems = document.querySelectorAll(groupSelector);
        var highest = 0;
        for (var i = 0; i < elems.length; i++) {
            var zindex = document.defaultView.getComputedStyle(elems[i], null).zIndex;
            if (!isNaN(parseInt(zindex)) && parseInt(zindex) > highest) {
                highest = zindex;
            }
        }
        item.style.zIndex = parseFloat(highest) + 1;
    };
    Util.extend = function(obj, prop) {
        for (var i in prop) {
            if (prop.hasOwnProperty(i)) {
                obj[i] = prop[i];
            }
        }
    };
    /*-----------------------Animations---------------------*/
    Util.fadeOut = function(el) {
        el.style.opacity = 1;
        (function fade() {
            if ((el.style.opacity -= 0.1) < 0) {
                el.style.display = "none";
            } else {
                requestAnimationFrame(fade);
            }
        })();
    };
    // fade in
    Util.fadeIn = function(el, display) {
        el.style.opacity = 0;
        el.style.display = display || "block";
        (function fade() {
            var val = parseFloat(el.style.opacity);
            if (!((val += 0.1) > 1)) {
                el.style.opacity = val;
                requestAnimationFrame(fade);
            }
        })();
    };
    Util.scrollTo = function(element, to, duration) {
        if (duration < 0) return;
        var difference = to - element.scrollTop;
        var perTick = difference / duration * 10;
        setTimeout(function() {
            element.scrollTop = element.scrollTop + perTick;
            if (element.scrollTop === to) return;
            scrollTo(element, to, duration - 10);
        }, 10);
    };
    Util.movable = function() {
        var mouseDownForMove = false,
            mouseDownForResize = false,
            lastMovementX = 0,
            lastMovementY = 0,
            dialog, parentElement, header, grip;

        function mouseDownForMoveEvent(e) {
            e.preventDefault();
            var button = e.button || e.which;
            if (button == 1) mouseDownForMove = true;
            Util.bringToFront(dialog, '.dialog');
        }

        function mouseDownEventResize(e) {
            e.preventDefault();
            var button = e.button || e.which;
            if (button == 1) mouseDownForResize = true;
            Util.bringToFront(dialog, '.dialog');
        }

        function mouseUpEvent(e) {
            e.preventDefault();
            var button = e.button || e.which;
            if (button == 1) {
                mouseDownForMove = false;
                mouseDownForResize = false;
            }
        }

        function mouseMoveEvent(e) {
            e.preventDefault();
            if (mouseDownForMove) {
                e = defineEvent(e);
                var prop = {
                    top: (dialog.offsetTop + e.movementY).toString() + 'px',
                    left: (dialog.offsetLeft + e.movementX).toString() + 'px'
                };
                Util.extend(dialog.style, prop);
            }
            if (mouseDownForResize) {
                e = defineEvent(e);
                var padding = parseFloat(getComputedStyle(dialog).paddingLeft) + parseFloat(getComputedStyle(dialog).paddingRight);
                var prop = {
                    width: (dialog.offsetWidth - padding + e.movementX).toString() + 'px',
                    height: (dialog.offsetHeight - padding + e.movementY).toString() + 'px'
                };
                Util.extend(dialog.style, prop);
            }
            lastMovementX = e.pageX;
            lastMovementY = e.pageY;
        }

        function defineEvent(e) {
            e.movementX = e.movementX || e.mozMovementX || e.webkitMovementX || e.pageX - lastMovementX;
            e.movementY = e.movementY || e.mozMovementY || e.webkitMovementY || e.pageY - lastMovementY;
            return e;
        }
        var init = function(item, parent, drag, resizeGrip) {
            dialog = document.querySelector(item);
            parentElement = document.querySelector(parent);
            header = dialog.querySelector(drag);
            grip = dialog.querySelector(resizeGrip);
            header.addEventListener('mousedown', mouseDownForMoveEvent, false);
            parentElement.addEventListener('mouseup', mouseUpEvent, false);
            parentElement.addEventListener('mousemove', mouseMoveEvent, false);
            if (grip) grip.addEventListener('mousedown', mouseDownEventResize, false);
        };
        var destroy = function() {
            header.removeEventListener('mousedown', mouseDownForMoveEvent);
            parentElement.removeEventListener('mouseup', mouseUpEvent);
            parentElement.removeEventListener('mousemove', mouseMoveEvent);
            if (grip) grip.removeEventListener('mousedown', mouseDownEventResize);
        };
        return {
            Init: function(item, parent, drag, resizeGrip) {
                init(item, parent, drag, resizeGrip);
            },
            Destroy: function() {
                destroy();
            }
        };
    };
}).call(window.Util = window.Util || {});