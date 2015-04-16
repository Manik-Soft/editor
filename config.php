<?php
class Users
{
    // user_password: password_hash('user_password', PASSWORD_DEFAULT);
    private $users = array(
                'user_name' => array(
                    'password' => 'user_password',
                    'path' => 'path to the allowed folder',
                    'SQL' => array(
                        'conn' => 'mysql:host=YOUR_HOST;dbname=YOUR_DBNAME;charset=utf8',
                        'user' => 'DB_USER',
                        'pass' => 'DB_USER_PASS'
                    )
                )
                // More users -> 
            );
    
    private $name = null;
    private $password = null;
    private $path = null;
    private $valid = false;
    private $SQL = array();

    public static function get_instance($name, $pass) {
        static $instance = null;
        if (null === $instance) {
            $instance = new static ($name, $pass);
        }
        return $instance;
    }
    protected function __construct($name, $pass) {
        if (isset($this->users[$name]) && password_verify($pass, $this->users[$name]['password'])) {
            $this->valid = true;
            $this->name = $name;
            $this->path = $this->users[$name]['path'];
            $this->SQL = $this->users[$name]['SQL'];
        }
    }
    public function valid() {
        return $this->valid;
    }
    public function get_path() {
        return $this->path;
    }
    public function get_full_path() {
        return $this->path !== null ? $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $this->path : null;
    }
    public function get_name() {
        return $this->name;
    }
    public function get_SQL() {
        return $this->SQL;
    }
}