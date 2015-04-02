(function() {
    this.fileReader = function() {
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
    this.Ajax = function() {
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
    this.fullScreen = {
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
    this.Tone = {
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
    /*-----------------------Animations---------------------*/
    this.fadeOut = function(el) {
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
    this.fadeIn = function(el, display) {
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
    this.scrollTo = function(element, to, duration) {
        if (duration < 0) return;
        var difference = to - element.scrollTop;
        var perTick = difference / duration * 10;
        setTimeout(function() {
            element.scrollTop = element.scrollTop + perTick;
            if (element.scrollTop === to) return;
            scrollTo(element, to, duration - 10);
        }, 10);
    };
    this.movable = function() {
        var mouseDown = false,
            dialog, parentElement, header;

        function mouseDownEvent(e) {
            e.preventDefault();
            var button = e.button || e.which;
            if (button == 1) mouseDown = true;
        }

        function mouseUpEvent(e) {
            e.preventDefault();
            var button = e.button || e.which;
            if (button == 1) mouseDown = false;
        }

        function mouseMoveEvent(e) {
            e.preventDefault();
            if (mouseDown) {
                e.movementX = e.movementX || e.mozMovementX || e.webkitMovementX || 0;
                e.movementY = e.movementY || e.mozMovementY || e.webkitMovementY || 0;
                var prop = {
                    position: 'absolute',
                    top: (dialog.offsetTop + e.movementY).toString() + 'px',
                    left: (dialog.offsetLeft + e.movementX).toString() + 'px'
                };
                for (var i in prop) {
                    if (prop.hasOwnProperty(i)) {
                        dialog.style[i] = prop[i];
                    }
                }
            }
        }
        var init = function(item, parent, drag) {
            dialog = document.querySelector(item);
            parentElement = document.querySelector(parent);
            header = document.querySelector(drag);
            header.addEventListener('mousedown', mouseDownEvent);
            parentElement.addEventListener('mouseup', mouseUpEvent);
            parentElement.addEventListener('mousemove', mouseMoveEvent, true);
        };
        var destroy = function() {
            header.removeEventListener('mousedown', mouseDownEvent);
            parentElement.removeEventListener('mouseup', mouseUpEvent);
            parentElement.removeEventListener('mousemove', mouseMoveEvent);
        };
        return {
            Init: function(item, parent, drag) {
                init(item, parent, drag);
            },
            Destroy: function() {
                destroy();
            }
        };
    };
}).call(window.Util = window.Util || {});