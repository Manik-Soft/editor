Web Dev Editor
=============

Web Dev Editor / [WDE] / is a Developer text editor for a PHP based  web server.

- Very simple usage
- Some importan options
- Quick implementation
- Multiple users
  
Each user can be assigned:
 
- User name
- Password 
- Path to library for editing allowed

[WDE] - Online Demo 
[Video] - Short Video demo

<img src = "capture.jpg"/>

Version
----
1.0.0 beta

Compatibility - tested
----
- IE 10 or above
- Chrome 
- Firefox

Requirements
----
- PHP Web server 5.5 or above

Installation
----
- Copy whole folder (editor) to your web server.
- Fill the config.php with yours details .

Usage
----
- Check out the help on the popup window after login
- Check out [ACE] editor options 

config.php - users, passwords, paths configurations
--------------
```sh
<?php
// user_password: password_hash('user_password', PASSWORD_DEFAULT);
class Users
{
    private $users = array(
        'user_name' => 
        array('password' => 'user_password', 'path' => 'path to the allowed folder')
        // More users -> 
    );

?>
```

editor.js - key configurations
--------------
```sh
var keys = (function() {
    return {
        MODIFIER_KEY: 'altKey',
        COPY_FILE: 'C'.charCodeAt(0),
        PASTE_FILE: 'V'.charCodeAt(0),
        SAVE_FILE: 'S'.charCodeAt(0),
        TOGGLE_BROWSE_DIALOG: 'O'.charCodeAt(0),
        NEXT_TAB: 'D'.charCodeAt(0),
        PREV_TAB: 'A'.charCodeAt(0),
        CLOSE_TAB: 'Q'.charCodeAt(0),
        ADD_NEW_TAB: 'N'.charCodeAt(0),
        TOGGLE_HELP_WINDOW: 'H'.charCodeAt(0)
    };
})();
```

Used plugins, frameworks etc:
----
- [ACE] High performance code editor

License
----
MIT
Author: Tóth András
---
http://atandrastoth.co.uk/

2015-04-01
[Video]:http://atandrastoth.co.uk/main/pages/plugins/webeditor/
[WDE]:http://editor.atandrastoth.co.uk/
[ACE]:http://ace.c9.io/
