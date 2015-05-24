<?php
@session_start();
require_once ('config.php');
$error = "Please Sign in";
//echo password_hash('demo', PASSWORD_DEFAULT);exit;
if (isset($_POST['name'])) {
    $u = Users::get_instance($_POST['name'], $_POST['pass']);
    if ($u->valid()) {
        $_SESSION['user']['path'] = $u->get_path();
        $_SESSION['user']['full_path'] = $u->get_full_path();
        $_SESSION['user']['name'] = $u->get_name();
        $_SESSION['user']['SQL'] = $u->get_SQL();
        $_SESSION['ace-theme'] = $_POST['ace-theme'];
    } else {
        $error = 'Wrong name or password!';
    }
}
if (isset($_POST['logout'])) {
    destSession();
}

function destSession() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <link rel="apple-touch-icon" sizes="57x57" href="css/icons/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="css/icons/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="css/icons/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="css/icons/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="css/icons/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="css/icons/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="css/icons/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="css/icons/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="css/icons/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="css/icons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="css/icons/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="css/icons/favicon-16x16.png">
        <link rel="manifest" href="manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="css/css/icons/ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>WDE</title>
        <link rel="stylesheet" href="css/reset-stylesheet.css">
        <link rel="stylesheet" href="css/fonts.css" media="all" type="text/css">
        <link id="base-theme" rel="stylesheet" href="css/style.css">
    </head>
<?php
if (isset($_SESSION['user'])) {
?>
    <body>
        <div class="header no-select sh-1">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" class="animated pulse" version="1.1" width="48" height="100%" viewBox="0 0 64 48" preserveAspectRatio="xMinYMin meet">
                    <g id="svg_3">
                        <ellipse stroke-width="2" ry="19.58425" rx="30.85339" id="svg_1" cy="23.99853" cx="32" stroke="orange" fill="blue"></ellipse>
                        <text stroke-width="0" font-weight="100" transform="matrix(0.9047371652196257,0,0,1.099962933590827,-3.8440308998477652,-3.9874281088872903) " xml:space="preserve" text-anchor="middle" font-family="Fantasy" font-size="32" id="svg_2" y="38.21217" x="39.56242" stroke-linecap="null" stroke-linejoin="null" stroke="#000000" fill="yellow">WDE</text>
                    </g>
                </svg>
            </h3>
            <ul class="tab-holder">
                <li class="add-new"></li>
            </ul>
            <span class="icon wde-logout" onclick="document.querySelector('button').click()" title="logout"></span>
            <span class="icon wde-fullscreen" onclick="WDE.toggleFullScreen(this);" title="full screen"></span>
            <span class="icon wde-folder-open" onclick="WDE.toggleBrowserDialog(undefined,true);" title="browser window"></span>
            <span class="separator"></span>
            <form action="." method="post"><button type="submit" name="logout"></button></form>
        </div>
        <pre></pre>
        <div class="footer no-select sh-1">
            <a id="current-path" target="_blank"></a>
            <span class="icon wde-save" onclick="WDE.saveFile();" title="save file"></span>
            <span class="icon wde-copy" onclick="WDE.copyFile();" title="copy file"></span>
            <span class="icon wde-paste" onclick="WDE.pasteFile();" title="paste file"></span>
            <span class="separator"></span>
        </div>
        <div class="dialog sh-2 no-select hidden" id="browser-window">
            <h3>Browser Window
                <span class="icon wde-close" onclick="WDE.toggleBrowserDialog(undefined, true);" title="close"></span>
                <span class="separator"></span>
                <span class="icon wde-file-add" onclick="WDE.createNewFile();" title="new file"></span>
                <span class="icon wde-folder-add" onclick="WDE.createNewFolder();" title="new folder"></span>
                <span class="icon wde-upload" onclick="setTimeout(WDE.uploadFile());" title="upload"></span>
            </h3>
            <div class="dialog-content"></div>
            <div class="grip"></div>
        </div>
        <div class="dialog sh-2 no-select hidden" id="help-window">
            <h3>Help Window
                <span class="icon wde-close" onclick="WDE.toggleHelperWindow();" title="close"></span>
                <span class="separator"></span>
            </h3>
            <div class="dialog-content"></div>
            <div class="grip"></div>
        </div>
        <div class="dialog sh-2 no-select hidden" id="sql-window">
            <h3>SQL Result Window
                <span class="icon wde-close" onclick="this.parentElement.parentElement.classList.add('hidden');" title="close"></span>
                <span class="separator"></span>
            </h3>
            <div class="dialog-content"></div>
            <div class="grip"></div>
        </div>
        <div class="dialog-upload hidden sh-2"></div>
        <script src="js/src-min-noconflict/ace.js" type="text/javascript"></script>
        <script src="js/src-min-noconflict/ext-language_tools.js"></script>
        <script src="js/src-min-noconflict/ext-modelist.js"></script>
        <script src="js/util.js" type="text/javascript"></script>
        <script src="js/editor.js" type="text/javascript"></script>
        <script type="text/javascript">
            WDE.Init("<?php echo $_SESSION['ace-theme']; ?>", "<?php echo $_SESSION['user']['path']; ?>");
            setTimeout(function(){
                WDE.toggleHelperWindow();
            }, 1000);
        </script>
<?php
} 
else {
?>
    <link rel="stylesheet" href="css/loader.css" id="loader-style">
    <body>
        <div id="loader-div"> 
            <svg class="animated pulse" xmlns="http://www.w3.org/2000/svg" version="1.1" width="640" height="480" viewBox="0 0 64 48" preserveAspectRatio="xMinYMin meet">
                <g id="svg_3">
                    <ellipse stroke-width="2" ry="19.58425" rx="30.85339" id="svg_1" cy="23.99853" cx="32" stroke="orange" fill="blue"></ellipse>
                    <text stroke-width="0" font-weight="100" transform="matrix(0.9047371652196257,0,0,1.099962933590827,-3.8440308998477652,-3.9874281088872903) " xml:space="preserve" text-anchor="middle" font-family="Fantasy" font-size="32" id="svg_2" y="38.21217" x="39.56242" stroke-linecap="null" stroke-linejoin="null" stroke="#000000" fill="yellow">WDE</text>
                </g>
            </svg>
        </div>
        <form class="dialog login sh-5" action="." method="post">
            <h3>Web Dev Editor 
                <small class="login-link">Powered by 
                    <a href="http://ace.c9.io/" target="_blank"><img src="css/icons/ace-tab.png"></a>
                </small>
                <small class="login-message"><?php echo $error;?></small>
            </h3>
            <div class="dialog-content text-center">
                <label></label><br>
                <input type="text" name="name" placeholder="demo" autofocus><br>
                <label></label><br>
                <input type="password" name="pass" placeholder="demo"><br><br>
                <label>Choose Editor theme</label><br>
                <select id="ace-theme" size="1" name="ace-theme">
                    <optgroup label="Bright">
                        <option value="ace/theme/chrome">Chrome</option>
                        <option value="ace/theme/clouds">Clouds</option>
                        <option value="ace/theme/crimson_editor">Crimson Editor</option>
                        <option value="ace/theme/dawn">Dawn</option>
                        <option value="ace/theme/dreamweaver">Dreamweaver</option>
                        <option value="ace/theme/eclipse">Eclipse</option>
                        <option value="ace/theme/github">GitHub</option>
                        <option value="ace/theme/solarized_light">Solarized Light</option>
                        <option value="ace/theme/textmate">TextMate</option>
                        <option value="ace/theme/tomorrow">Tomorrow</option>
                        <option value="ace/theme/xcode">XCode</option>
                        <option value="ace/theme/kuroir">Kuroir</option>
                        <option value="ace/theme/katzenmilch">KatzenMilch</option>
                    </optgroup>
                    <optgroup label="Dark"><option value="ace/theme/ambiance">Ambiance</option>
                        <option value="ace/theme/chaos">Chaos</option>
                        <option value="ace/theme/clouds_midnight">Clouds Midnight</option>
                        <option value="ace/theme/cobalt">Cobalt</option>
                        <option value="ace/theme/idle_fingers">idle Fingers</option>
                        <option value="ace/theme/kr_theme">krTheme</option>
                        <option value="ace/theme/merbivore">Merbivore</option>
                        <option value="ace/theme/merbivore_soft">Merbivore Soft</option>
                        <option value="ace/theme/mono_industrial">Mono Industrial</option>
                        <option value="ace/theme/monokai" selected>Monokai</option>
                        <option value="ace/theme/pastel_on_dark">Pastel on dark</option>
                        <option value="ace/theme/solarized_dark">Solarized Dark</option>
                        <option value="ace/theme/terminal">Terminal</option>
                        <option value="ace/theme/tomorrow_night">Tomorrow Night</option>
                        <option value="ace/theme/tomorrow_night_blue">Tomorrow Night Blue</option>
                        <option value="ace/theme/tomorrow_night_bright">Tomorrow Night Bright</option>
                        <option value="ace/theme/tomorrow_night_eighties">Tomorrow Night 80s</option>
                        <option value="ace/theme/twilight">Twilight</option>
                        <option value="ace/theme/vibrant_ink">Vibrant Ink</option>
                    </optgroup>
                </select><br><br>
                <button type="submit">Login</button>
            </div>
        </form>
        <script src="js/util.js" type="text/javascript"></script>
        <script type="text/javascript" id="loader-script">
        function removeElement(el) {
            el && el.parentNode && el.parentNode.removeChild(el);
        }
        function loaderDone() {
            document.querySelector('#loader-div').style.cssText = ('opacity: 0');
            setTimeout(function() {
                removeElement(document.querySelector('#loader-div'));
                removeElement(document.querySelector('#loader-style'));
                removeElement(document.querySelector('#loader-script'));
            }, 2000);
        }
        var params = {};
        new Util.Ajax().POST('com.php', {
                params: {
                order: 'initialize'             }
        }, loaderDone, true);
        new Util.movable().Init('.dialog', 'body', '.dialog h3');
        </script>
<?php
};
?>
    </body>
</html>