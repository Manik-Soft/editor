<?php
class SqlFuncProc
{
    private $thisPath = "";
    private $db;
    private $prefix = "";
    public static function getInstance($connStr, $user, $pass, $defPathToSQL = '') {
        static $instance = null;
        if (null === $instance) {
            $instance = new static ($connStr, $user, $pass, $defPathToSQL = '');
        }
        return $instance;
    }
    protected function __construct($connStr, $user, $pass, $defPathToSQL = '') {
        $reflector = new ReflectionClass('SqlFuncProc');
        $this->thisPath = $defPathToSQL == '' ? dirname($reflector->getFileName()) . DIRECTORY_SEPARATOR : $defPathToSQL . DIRECTORY_SEPARATOR;
        try {
            $this->db = new PDO($connStr, $user, $pass);
            $this->prefix = strtolower($this->db->getAttribute(constant("PDO::ATTR_DRIVER_NAME")));
        }
        catch(Exception $e) {
            return (false);
        }
        if (mb_internal_encoding() == 'UTF-8' && $this->prefix == 'sqlsrv') {
            $this->db->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } else if (mb_internal_encoding() != 'UTF-8' && $this->prefix == 'sqlsrv') {
            $this->db->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
        }
    }
    public function runQuery($sql, $params = array(), $limit = false, $force = false) {
        $resArray = array();
        $i = 0;
        if ($force) {
            $sql = $this->forcePrepare($sql, $params);
            $query = false;
            $query = $this->db->query($sql);
            if ($this->db->errorCode() != "00000") {
                return array('error' => $this->db->errorInfo());
            };
        } else {
            $query = $this->db->prepare($sql);
            if (!$query->execute($params)) {
                return  array('error' => $this->db->errorInfo());
            };
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $resArray[] = $row;
            $i++;
            if ($limit != false && $limit == $i) return $resArray;
        }
        return $resArray;
    }
    public function runFunc($proc, $params = array(), $limit = false, $force = false) {
        $resArray = array();
        $i = 0;
            $file = $this->thisPath . 'sql_func' . DIRECTORY_SEPARATOR . $this->prefix . '_' . $proc . '.sql';
            $sql = file_get_contents($file);
        if ($force) {
            $sql = $this->forcePrepare($sql, $params);
            $query = false;
            $query = $this->db->query($sql);
            if ($this->db->errorCode() != "00000") {
                return 'error: ' . $this->db->errorCode();
            };
        } else {
            $query = $this->db->prepare($sql);
            if (!$query->execute($params)) {
                return 'error: ' . $query->errorCode();
            };
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $resArray[] = $row;
            $i++;
            if ($limit != false && $limit == $i) return $resArray;
        }
        return $resArray;
    }
    public function runProc($proc, $params = array(), $force = false) {
        $file = $this->thisPath . 'sql_proc' . DIRECTORY_SEPARATOR . $this->prefix . '_' . $proc . '.sql';
        $sql = file_get_contents($file);
        if ($force) {
            $sql = $this->forcePrepare($sql, $params);
            $this->db->query($sql);
            return $this->db->errorCode();
        } else {
            $query = $this->db->prepare($sql);
            $query->execute($params);
            return $query->errorInfo();
        }
    }
    private function forcePrepare($sql, $params = array()) {
        $keys = array();
        $values = $params;
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
            
            if (is_string($value)) $values[$key] = "'" . $value . "'";
            
            if (is_array($value)) $values[$key] = implode(',', $value);
            
            if (is_null($value)) $values[$key] = 'NULL';
        }
        
        //array_walk($values, create_function('&$v, $k', 'if (!is_numeric($v) && $v!="NULL") $v = "\'".$v."\'";'));
        $sql = preg_replace($keys, $values, $sql, 1, $count);
        return $sql;
    }
    public function viewProc($name = '', $class = "") {
        $SqlProc = $this->getFiles($this->thisPath . 'SqlProc' . DIRECTORY_SEPARATOR, $type = "sql");
        $table = "<table class='" . $class . "'><caption>Sql Procedures</caption><thead><tr><td>File</td><td>SQL string</td></tr></thead><tbody>";
        $re = '/(.*ab)/';
        foreach ($SqlProc as $value) {
            if ($name == '' || preg_match('/(.*' . strtolower($name) . ')/', strtolower($value))) {
                $file = $this->thisPath . 'SqlProc' . DIRECTORY_SEPARATOR . $value . '.sql';
                $data = file_get_contents($file);
                $table.= '<tr><td><br>' . $value . '</td><td>' . $data . '</td></tr>';
            }
        }
        return $table . '</tbody></table>';
    }
    public function viewFunc($name = '', $class = "") {
        $SqlFunc = $this->getFiles($this->thisPath . 'SqlFunc' . DIRECTORY_SEPARATOR, $type = "sql");
        $table = "<table class='" . $class . "'><caption>Sql Functions</caption><thead><tr><td>File</td><td>SQL string</td></tr></thead><tbody>";
        $re = '/(.*ab)/';
        foreach ($SqlFunc as $value) {
            if ($name == '' || preg_match('/(.*' . strtolower($name) . ')/', strtolower($value))) {
                $file = $this->thisPath . 'SqlFunc' . DIRECTORY_SEPARATOR . $value . '.sql';
                $data = file_get_contents($file);
                $table.= '<tr><td><br>' . $value . '</td><td>' . $data . '</td></tr>';
            }
        }
        return $table . '</tbody></table>';
    }
    public function getHTMLtable($array = array(), $id = "", $class = "", $head = true) {
        $ret = '';
        $firstrow = true;
        $firstrow_data = '';
        foreach ($array as $row) {
            if ($firstrow == true) {
                $ret.= '<table id = "' . $id . '" class = "' . $class . '"><thead><tr>';
            } else {
                $ret.= '<tr>';
            }
            foreach ($row as $key => $value) {
                if ($firstrow == true && $head == true) {
                    $ret.= '<th>' . $key . '</th>';
                    $firstrow_data.= '<td>' . $value . '</td>';
                } else {
                    $ret.= '<td>' . $value . '</td>';
                }
            }
            if ($firstrow == true) {
                $firstrow = false;
                $ret.= '</tr><tbody>';
                $ret.= '<tr>' . $firstrow_data . '</tr>';
            } else {
                $ret.= '</tr>';
            }
        }
        $ret.= '</tbody>';
        $ret.= '</table>';
        return $ret;
    }
    private function getFiles($openFolder, $type = "sql", $one = false) {
        $ipFiles = array();
        $i = 0;
        if ($handle = opendir($openFolder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && strtolower(substr($entry, strlen($entry) - 3)) == $type) {
                    if ($one) {
                        return $entry;
                    }
                    $splitted = explode('.', $entry);
                    $ipFiles[] = $splitted[0];
                }
            }
            closedir($handle);
        }
        sort($ipFiles);
        return $ipFiles;
    }
}
