(function(undefined) {
    'use strict';
    WDE.Version = {
        Author: 'Tóth András',
        Name: 'Web Dev Editor - WDE',
        Version: '2.0.0',
        Licence: 'MIT'
    };
    if (!window.location.origin) {
        window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
    }
    var origin = window.location.origin;
    var keys = (function() {
        return {
            MODIFIER_KEY_ALLOW: 'altKey',
            MODIFIER_KEY_DENY: 'ctrlKey',
            COPY_FILE: 'C'.charCodeAt(0),
            PASTE_FILE: 'V'.charCodeAt(0),
            SAVE_FILE: 'S'.charCodeAt(0),
            TOGGLE_BROWSE_DIALOG: 'O'.charCodeAt(0),
            NEXT_TAB: 'D'.charCodeAt(0),
            PREV_TAB: 'A'.charCodeAt(0),
            CLOSE_TAB: 'Q'.charCodeAt(0),
            ADD_NEW_TAB: 'N'.charCodeAt(0),
            TOGGLE_HELP_WINDOW: 'H'.charCodeAt(0),
            EXECUTE_SQL: 'R'.charCodeAt(0)
        };
    })();
    var viewableFiles = 'wde-file-word,wde-file-excel,wde-file-image,wde-file-pdf,wde-file-zip,wde-file-music,wde-file-video',
        viewableAndEditable = 'svg';
    var txt = ('innerText' in HTMLElement.prototype) ? 'innerText' : 'textContent';
    var browserWindow, sqlWindow, currentPath, theme, modelist, helpWindow,
        dialogUpload, tabHolder,
        tempFile = {
            path: '',
            file: '',
            data: ''
        };
    var tabs = {
        editors: {},
        insert: function(target, path, name, content) {
            var tab = document.createElement('li');
            var editor = document.createElement('pre');
            var key = new Date().getTime().toString();
            tab[txt] = name;
            tab.setAttribute('data-saved', false);
            tab.setAttribute('data-path', path ? path : '');
            tab.setAttribute('data-file', name);
            tab.setAttribute('data-key', key);
            tabs.editors[key] = editor;
            editor.id = key;
            target.parentElement.insertBefore(tab, target);
            document.querySelector('body').insertBefore(editor, document.querySelector('pre'));
            createEditor(key, name, content ? content : '');
            this.changeSelected(tab);
        },
        items: function() {
            var tb = tabHolder.querySelectorAll('li');
            return [].slice.call(tb, 0, tb.length - 1);
        },
        eq: function(ind) {
            return this.items()[ind];
        },
        changeSelected: function(item) {
            var sel = this.getSelected();
            if (sel) {
                var id = sel.getAttribute('data-key');
                sel.classList.remove('selected');
            }
            item.classList.add('selected');
            this.hideEditors();
            tabs.editors[item.getAttribute('data-key')].style.display = 'block';
            setCurrent(item.getAttribute('data-path'), '');
        },
        get: function(item) {
            var sel = this.items().filter(function(b) {
                return b == item;
            });
            return sel.length !== 0 ? sel[0] : false;
        },
        getSelected: function() {
            var sel = this.items().filter(function(b) {
                return b.classList.contains('selected');
            });
            return sel.length !== 0 ? sel[0] : false;
        },
        getByPathAndFile: function(path, file) {
            var sel = this.items().filter(function(b) {
                return b.getAttribute('data-file') == file && b.getAttribute('data-path') == path;
            });
            return sel.length !== 0 ? sel[0] : false;
        },
        hideEditors: function() {
            var pre = document.querySelectorAll('pre');
            [].forEach.call(pre, function(element, index) {
                element.style.display = 'none';
            });
            if (pre.length == 1) pre[0].style.display = 'block';
        },
        selectedIndex: function() {
            return this.items().indexOf(this.getSelected());
        },
        selectNext: function() {
            var index = this.selectedIndex();
            if (index != -1) {
                index = this.items().length - 1 != index ? index : -1;
                this.changeSelected(this.eq(++index));
            }
        },
        selectPrev: function() {
            var index = this.selectedIndex();
            if (index != -1) {
                index = index !== 0 ? index : this.items().length;
                this.changeSelected(this.eq(--index));
            }
        },
        isSaved: function(item) {
            return (eval(this.get(item).getAttribute('data-saved')) && WDE.isFileSaved(this.get(item).getAttribute('data-path'), this.get(item).getAttribute('data-file')));
        }
    };

    function removeElement(el) {
        el && el.parentNode && el.parentNode.removeChild(el);
    }

    function tabHolderHandler(e) {
        e.preventDefault();
        var target = e.target || e.srcElement;
        if (target.className == 'tab-holder') return;
        if (target.className == 'add-new') {
            addNewTab(target);
            return;
        }
        if (target.className == 'selected' && (target.offsetLeft + target.clientWidth - 25) <= e.pageX) {
            closeTab(target);
            return;
        }
        if (target.className != 'selected' && target.className != 'add-new') {
            tabs.changeSelected(target);
        }
    }

    function addNewTab(target) {
        if (!target) {
            target = tabs.getSelected() || tabHolder.querySelector('li');
        }
        var name = prompt('Enter file name: ', 'untitled.js');
        if (name) tabs.insert(target, currentPath[txt].replace(origin, ''), name, '');
    }

    function closeTab(target) {
        if (!target) {
            target = tabs.getSelected();
            if (!target) return;
        }
        if (!tabs.isSaved(target)) {
            if (!confirm('Are you sure ?\nFile: ' + target.getAttribute('data-file') + ' not saved!')) return;
            removeTab(target);
        } else {
            removeTab(target);
        }
    }

    function removeTab(target) {
        var key = target.getAttribute('data-key');
        ace.edit(key).destroy();
        removeElement(target);
        removeElement(tabs.editors[key]);
        delete tabs.editors[key];
        var fi = tabs.eq(0);
        if (fi) {
            tabs.changeSelected(fi);
        } else {
            setCurrent('', '');
            tabs.hideEditors();
        }
    }

    function pathsLoaded(req) {
        var data = eval(req.responseText);
        var ul = document.createElement('ul');
        [].forEach.call(data, function(item) {
            var str = '';
            var li = document.createElement('li');
            if (item.type != 'wde-folder-move') str = '<span class="wde-delete" onclick="WDE.deleteFile(event);"></span><span class="wde-zip-box" onclick="WDE.zipFile(event);"></span>';
            if (item.type != 'wde-folder-move' && viewableFiles.split(',').indexOf(item.type) !== -1){
                str += '<span class="wde-navigation" onclick="WDE.fileExecute(event);"></span>';
            }
            li.innerHTML = '<a data-path="' + item.path + '"><span class="' + item.type + '"></span>' + item.name + str + '</a>';
            ul.appendChild(li);
        });
        browserWindow.children[1].innerHTML = '';
        browserWindow.children[1].appendChild(ul);
        browserWindow.classList.remove('hidden');
        Util.bringToFront(browserWindow, '.dialog');
    }

    function contentLoaded(req, txt) {
        var params = {};
        if (typeof req == 'object') {
            var file = eval(decodeURIComponent(req.data)).file;
            var path = eval(decodeURIComponent(req.data)).path;
            tabs.insert(tabHolder.querySelector('li:last-child'), path, file, req.responseText);
        } else {
            initEditors(req, txt);
        }
    }

    function uploadFile(e) {
        var params = {};
        new Util.Ajax().POST('com.php', {
            params: {
                order: 'save_file_binary',
                path: currentPath[txt].replace(origin, ''),
                file: e.name,
                data: e.data
            }
        }, showHideDialog, true);
    }

    function createEditor(id, file, txt) {
        var editor = ace.edit(id);
        editor.setTheme(theme);
        ace.require("ace/ext/language_tools");
        modelist = ace.require('ace/ext/modelist');
        initEditors(editor, file, txt);
    }

    function initEditors(editor, file, txt) {
        var mode = modelist.getModeForPath(file).mode;
        editor.session.setMode(mode);
        editor.$blockScrolling = Infinity;
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableSnippets: true,
            enableLiveAutocompletion: true
        });
        editor.setShowPrintMargin(false);
        editor.getSession().setUseWrapMode(true);
        editor.setValue(txt);
        editor.selection.clearSelection();
        editor.gotoLine(1);
        editor.focus();
    }

    function setCurrent(path, file) {
        currentPath.href = currentPath[txt] = origin + path;
    }

    function showHideDialog(e, req) {
        var statusState = e.statusState || e.statusText;
        var params = {};
        if (!req) req = ' upload ';
        var file = eval(decodeURIComponent(e.data)).file;
        if (statusState == 'OK') {
            dialogUpload.classList.remove('upload-error');
            dialogUpload.classList.add('upload-ok');
        } else {
            dialogUpload.classList.remove('upload-ok');
            dialogUpload.classList.add('upload-error');
        }
        dialogUpload.classList.remove('hidden');
        dialogUpload[txt] = file + req + statusState + ' !';
        setTimeout(function() {
            dialogUpload.classList.add('hidden');
        }, 5000);
    }

    function buildHelperWindow() {
        var ul = createAndAppendTo('ul', undefined, helpWindow.querySelector('.dialog-content'));
        var list = document.querySelectorAll('span');
        createAndAppendTo('li', '<a style="color:white;">About:</a>', ul);
        for (var i in WDE.Version) {
            createAndAppendTo('li', '<a>' + i + '<span style="float:right;margin-right:10px;">' + WDE.Version[i] + '</span></a>', ul);
        }
        createAndAppendTo('li', '<a style="color:white;">Icons:</a>', ul);
        for (var i = 0; i < list.length; i++) {
            var repeat = Array.prototype.filter.call(ul.children, function(b) {
                return b.innerHTML.indexOf(list[i].className.replace('icon ', '')) !== -1 ? true : false;
            });
            if (repeat.length === 0 && list[i].className != 'separator') {
                createAndAppendTo('li', '<a>' + list[i].className.replace('icon wde-', '') + '<span style="float:right;margin-right:3px;" class="' + list[i].className.replace('icon ', '') + '"></span></a>', ul);
            }
        }
        createAndAppendTo('li', '<a style="color:white;">Keymap:</a>', ul);
        for (i in keys) {
            var val = isNaN(Number(keys[i])) ? keys[i] : String.fromCharCode(keys[i]);
            createAndAppendTo('li', '<a>' + i + '<span style="float:right;margin-right:10px;">' + val + '</span></a>', ul);
        }
    }

    function createAndAppendTo(type, innerhtml, appendTo) {
        var item = document.createElement(type);
        if (innerhtml) item.innerHTML = innerhtml;
        if (appendTo) appendTo.appendChild(item);
        return item;
    }

    function testSQLQuery() {
        if (!tabs.getSelected()) return;
        var isSQL = tabs.getSelected().getAttribute('data-file').indexOf('sql');
        if (isSQL != -1) {
            var params = {};
            new Util.Ajax().POST('com.php', {
                params: {
                    order: 'test_sql',
                    path: currentPath[txt].replace(origin, ''),
                    file: tabs.getSelected().getAttribute('data-file'),
                    data: ace.edit(tabs.getSelected().getAttribute('data-key')).getValue()
                }
            }, function(e) {
                var content = sqlWindow.querySelector('.dialog-content');
                content.innerHTML = e.responseText;
                content.scrollTop = 0;
                sqlWindow.classList.remove('hidden');
                Util.bringToFront(sqlWindow, '.dialog');
            }, true);
        }
    }
    WDE.toggleFullScreen = function(el) {
        Util.fullScreen.toggleFullScreen('body');
        if (!Util.fullScreen.isFullScreen) {
            el.className = 'icon wde-fullscreen';
        } else {
            el.className = 'icon wde-fullscreen-exit';
        }
    };
    WDE.toggleBrowserDialog = function(path, enableHide) {
        if (typeof path === 'undefined') path = currentPath[txt].replace(origin, '');
        if (!browserWindow.classList.contains('hidden') && enableHide) {
            browserWindow.classList.add('hidden');
            if (tabs.getSelected()) setCurrent(tabs.getSelected().getAttribute('data-path'), '');
            return;
        }
        setCurrent(path, '');
        var params = {};
        new Util.Ajax().POST('com.php', {
            params: {
                order: 'get_dir',
                path: path,
                file: ''
            }
        }, pathsLoaded, true);
    };
    WDE.uploadFile = function() {
        new Util.fileReader().Init('', 'dataURL', uploadFile, true);
    };
    WDE.createNewFolder = function() {
        var file = prompt('Enter folder name: ', 'new_folder');
        if (file) {
            var params = {};
            new Util.Ajax().POST('com.php', {
                params: {
                    order: 'new_dir',
                    path: currentPath[txt].replace(origin, ''),
                    file: file
                }
            }, pathsLoaded, true);
        }
    };
    WDE.createNewFile = function() {
        var file = prompt('Enter file name: ', 'new_file.html');
        if (file) {
            var params = {};
            new Util.Ajax().POST('com.php', {
                params: {
                    order: 'new_file',
                    path: currentPath[txt].replace(origin, ''),
                    file: file
                }
            }, pathsLoaded, true);
        }
    };
    WDE.isFileSaved = function(path, file) {
        var ret = false;
        var params = {};
        var tab = tabs.getSelected();
        new Util.Ajax().POST('com.php', {
            params: {
                order: 'is_saved_file',
                path: tab.getAttribute('data-path').replace(origin, ''),
                file: tab.getAttribute('data-file'),
                data: ace.edit(tab.getAttribute('data-key')).getValue()
            }
        }, function(e) {
            ret = eval(e.responseText);
        }, false);
        return ret;
    };
    WDE.saveFile = function() {
        if (!tabs.getSelected()) return;
        var params = {};
        new Util.Ajax().POST('com.php', {
            params: {
                order: 'save_file',
                path: currentPath[txt].replace(origin, ''),
                file: tabs.getSelected().getAttribute('data-file'),
                data: ace.edit(tabs.getSelected().getAttribute('data-key')).getValue()
            }
        }, function(e) {
            if (e.statusText == 'OK' && !isNaN(Number(e.responseText))) {
                tabs.getSelected().setAttribute('data-saved', true);
                tabs.getSelected().setAttribute('data-path', currentPath[txt].replace(origin, ''));
                e.statusState = 'OK';
                showHideDialog(e, ' save ');
            } else {
                e.statusState = 'Failed';
                showHideDialog(e, ' save ');
            }
        }, true);
    };
    WDE.deleteFile = function(event) {
        event.preventDefault();
        event.stopPropagation();
        var file = confirm('Are you sure delete ' + event.target.parentElement[txt] + ' ?');
        if (file) {
            var params = {};
            new Util.Ajax().POST('com.php', {
                params: {
                    order: 'delete',
                    path: event.target.parentElement.getAttribute('data-path'),
                    file: event.target.parentElement[txt]
                }
            }, pathsLoaded, true);
        }
    };
    WDE.fileExecute = function(e) {
        e.preventDefault();
        e.stopPropagation();
        var el = e.target.parentElement;
        if (viewableFiles.split(',').indexOf(el.children[0].className) !== -1) {
            window.open(origin + [el.getAttribute('data-path'), el[txt]].join('/'));
        }
    };
    WDE.zipFile = function(e) {
        e.preventDefault();
        e.stopPropagation();
        var el = e.target.parentElement;
        new Util.Ajax().POST('com.php', {
            params: {
                order: 'create_zip',
                path: el.getAttribute('data-path'),
                file: el[txt]
            }
        }, function(e) {
            if (e.statusText == 'OK' && !isNaN(Number(e.responseText))) {
                e.statusState = 'OK';
                showHideDialog(e, ' zipped ');
            } else {
                e.statusState = 'Failed';
                showHideDialog(e, ' zipped ');
            }
        }, true);
    };
    WDE.copyFile = function() {
        if (!tabs.getSelected()) return;
        var tmp = tabs.getSelected();
        tempFile = {
            path: tmp.getAttribute('data-path'),
            file: tmp.getAttribute('data-file'),
            data: ace.edit(tmp.getAttribute('data-key')).getValue()
        };
    };
    WDE.pasteFile = function() {
        if (tempFile.file === '' || currentPath[txt] === '') return;
        var params = {};
        new Util.Ajax().POST('com.php', {
            params: {
                order: 'save_file',
                path: currentPath[txt].replace(origin, ''),
                file: tempFile.file,
                data: tempFile.data
            }
        }, function(e) {
            showHideDialog(e, ' paste ');
        }, true);
        tempFile.file = '';
    };
    WDE.toggleHelperWindow = function() {
        if (helpWindow.classList.contains('hidden')) {
            helpWindow.classList.remove('hidden');
            Util.bringToFront(helpWindow, '.dialog');
        } else {
            helpWindow.classList.add('hidden');
        }
    };
    WDE.Init = function(_theme, base) {
        theme = _theme;
        if (base) origin = origin + '/' + base;
        tabHolder = document.querySelector('.tab-holder');
        tabHolder.addEventListener('click', tabHolderHandler, false);
        dialogUpload = document.querySelector('.dialog-upload');
        browserWindow = document.querySelector('#browser-window');
        helpWindow = document.querySelector('#help-window');
        sqlWindow = document.querySelector('#sql-window');
        currentPath = document.querySelector('#current-path');
        new Util.movable().Init('#browser-window', 'body', '#browser-window h3', '#browser-window .grip');
        new Util.movable().Init('#help-window', 'body', '#help-window h3', '#help-window .grip');
        new Util.movable().Init('#sql-window', 'body', '#sql-window h3', '#sql-window .grip');
        browserWindow.querySelector('.dialog-content').addEventListener('click', function(e) {
            var target = e.target.children[0] ? e.target : e.target.parentElement;
            switch (target.children[0].className) {
                case 'wde-folder-move':
                    var paths = target.getAttribute('data-path').split('/');
                    var path = target[txt] == '..' ? '' : paths.slice(0, paths.length - 1).join('/');
                    WDE.toggleBrowserDialog(path, false);
                    break;
                case 'wde-folder':
                    WDE.toggleBrowserDialog(target.getAttribute('data-path') + '/' + target[txt], false);
                    break;
                case '':
                    break;
                default:
                    if (viewableFiles.split(',').indexOf(target.children[0].className) !== -1 && viewableAndEditable.split(',').indexOf(target[txt].split('.')[1]) == -1) {
                        break;
                    }
                    var exists = tabs.getByPathAndFile(target.getAttribute('data-path'), target[txt]);
                    if (exists) {
                        tabs.changeSelected(exists);
                        break;
                    }
                    var params = {};
                    new Util.Ajax().POST('com.php', {
                        params: {
                            order: 'load_file',
                            path: target.getAttribute('data-path'),
                            file: target[txt]
                        }
                    }, contentLoaded, true);
            }
        }, false);
        window.addEventListener('keydown', function(e) {
            var x = e.which || e.keyCode;
            if (e[keys.MODIFIER_KEY_ALLOW] && !e[keys.MODIFIER_KEY_DENY]) {
                switch (x) {
                    case keys.COPY_FILE:
                        e.preventDefault();
                        WDE.copyFile();
                        break;
                    case keys.PASTE_FILE:
                        e.preventDefault();
                        WDE.pasteFile();
                        break;
                    case keys.SAVE_FILE:
                        e.preventDefault();
                        WDE.saveFile();
                        break;
                    case keys.ADD_NEW_TAB:
                        e.preventDefault();
                        addNewTab();
                        break;
                    case keys.CLOSE_TAB:
                        e.preventDefault();
                        closeTab();
                        break;
                    case keys.TOGGLE_BROWSE_DIALOG:
                        e.preventDefault();
                        WDE.toggleBrowserDialog(undefined, true);
                        break;
                    case keys.NEXT_TAB:
                        e.preventDefault();
                        tabs.selectNext();
                        break;
                    case keys.PREV_TAB:
                        e.preventDefault();
                        tabs.selectPrev();
                        break;
                    case keys.TOGGLE_HELP_WINDOW:
                        e.preventDefault();
                        WDE.toggleHelperWindow();
                        break;
                    case keys.EXECUTE_SQL:
                        e.preventDefault();
                        testSQLQuery();
                        break;
                }
            }
        }, false);
        buildHelperWindow();
    };
}).call(window.WDE = window.WDE || {});