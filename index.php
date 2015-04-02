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
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: http://' . $_SERVER['HTTP_HOST']);
}
?>
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
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
<?php
if (isset($_SESSION['user'])) {
?>
    <div class="header no-select sh-1">
        <h3>Web Dev Editor</h3>
        <ul class="tab-holder">
            <li class="add-new"></li>
        </ul>
        <span class="icon logout" onclick="document.querySelector('button').click()"></span>
        <span class="icon full-screen" onclick="WDE.toggleFullScreen(this);"></span>
        <span class="icon open-folder" onclick="WDE.toggleBrowseDialog(undefined,true);"></span>
        <form action="." method="post"><button type="submit" name="logout"></button></form>
    </div>
    <pre></pre>
    <div class="footer no-select sh-1">
        <a id="current-path" target="_blank"></a>
        <span class="icon save-file" onclick="WDE.saveFile();"></span>
        <span class="icon copy-file" onclick="WDE.copyFile();"></span>
        <span class="icon paste-file" onclick="WDE.pasteFile();"></span>
    </div>
    <div class="dialog sh-5 no-select hidden" id="browse-dialog">
        <h3>Browser Dialog
        <span class="icon close-dialog" onclick="WDE.toggleBrowseDialog(undefined, true);"></span>
        <span class="icon create-new-file" onclick="WDE.createNewFile();"></span>
        <span class="icon create-new-folder" onclick="WDE.createNewFolder();"></span>
        <span class="icon upload-file" onclick="setTimeout(WDE.uploadFile());"></span>
        </h3>
        <div class="dialog-content"></div>
    </div>
    <div class="dialog sh-5 no-select hidden" id="help-window">
        <h3>Help
            <span class="icon close-dialog" onclick="this.parentElement.parentElement.classList.add('hidden');"></span>
        </h3>
        <div class="dialog-content"></div>
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
    <form class="dialog login sh-5" action="." method="post">
        <h3>Web Dev Editor 
            <small class="login-link">Powered by 
                <a href="http://ace.c9.io/" target="_blank"><img src="css/icons/ace-tab.png"></a>
            </small>
            <small class="login-message"><?php echo $error;?></small>
        </h3>
        <div class="dialog-content text-center">
            <label></label><br>
            <input type="text" name="name" placeholder="User" autofocus><br>
            <label></label><br>
            <input type="password" name="pass" placeholder="Password"><br><br>
            <label>Choose Editor theme</label><br>
            <select id="ace-theme" size="1" name="ace-theme">
                <optgroup label="Bright"><option value="ace/theme/chrome">Chrome</option>
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
    <script type="text/javascript">
        new Util.movable().Init('.dialog', 'body', '.dialog h3');
    </script>
<?php
};
?>
    </body>
</html>
