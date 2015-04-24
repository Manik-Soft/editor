<?php
@session_start();
$p = json_decode($_REQUEST['params']);
$root = $_SESSION['user']['full_path'];
if ($root === null) exit;
class Datas
{
    public $name = "";
    public $path = "";
    public $type = "";
}

if ($p->order == 'get_dir') {
    echo json_encode(scan_dir($root, $p));
}
if ($p->order == 'load_file') {
    echo file_get_contents($root . $p->path . DIRECTORY_SEPARATOR . $p->file);
}
if ($p->order == 'save_file') {
    echo file_put_contents($root . $p->path . DIRECTORY_SEPARATOR . $p->file, $p->data);
}
if ($p->order == 'is_saved_file') {
    echo file_exists($root . $p->path . DIRECTORY_SEPARATOR . $p->file);
}
if ($p->order == 'save_file_binary') {
    $p->data = explode(',', $p->data) [1];
    echo file_put_contents($root . $p->path . DIRECTORY_SEPARATOR . $p->file, base64_decode($p->data));
}
if ($p->order == 'new_dir') {
    mkdir($root . $p->path . DIRECTORY_SEPARATOR . $p->file);
    echo json_encode(scan_dir($root, $p));
}
if ($p->order == 'new_file') {
    file_put_contents($root . $p->path . DIRECTORY_SEPARATOR . $p->file, '');
    echo json_encode(scan_dir($root, $p));
}
if ($p->order == 'delete') {
    $data = $root . $p->path . DIRECTORY_SEPARATOR . $p->file;
    if (is_dir($data)) {
        @delete_folder($data);
    } else {
        @unlink($data);
    }
    echo json_encode(scan_dir($root, $p));
}
if ($p->order == 'test_sql') {
    $sql = $_SESSION['user']['SQL'];
    if ($sql['conn'] == '') {
        echo ('Database connection is not configured!');
        return;
    }
    require_once ('sql_func_proc/sqlfuncproc.php');
    $func = SqlFuncProc::getInstance($sql['conn'], $sql['user'], $sql['pass']);
    $data = $func->runQuery($p->data, array(), false, true);
    if (isset($data['error'])) {
        echo json_encode($data);
    } else {
        echo $func->getHTMLtable($data, '', 'hovertable');
    }
}
if ($p->order == 'create_zip') {
    $rootPath = $root . str_replace('.', '', $p->path) . DIRECTORY_SEPARATOR . $p->file;
    $zipname = is_dir($rootPath) ? dirname($rootPath) . DIRECTORY_SEPARATOR . basename($rootPath) . '.zip' : dirname($rootPath) . DIRECTORY_SEPARATOR . basename($rootPath) . '.zip';
    Zip($rootPath, $zipname);
}
function delete_folder($folder) {
    $glob = glob($folder);
    foreach ($glob as $g) {
        if (!is_dir($g)) {
            @unlink($g);
        } else {
            delete_folder("$g/*");
            @rmdir($g);
        }
    }
}
function scan_dir($root, $p) {
    $sortedDataFile = array();
    $sortedDataDir = array();
    $dir = scandir($root . $p->path, 0);
    foreach ($dir as $file) {
        if (is_file($root . $p->path . DIRECTORY_SEPARATOR . $file)) {
            array_push($sortedDataFile, $file);
        } else {
            array_push($sortedDataDir, $file);
        }
    }
    natcasesort($sortedDataDir);
    natcasesort($sortedDataFile);
    foreach (array_merge($sortedDataDir, $sortedDataFile) as $key => $value) {
        $d = new Datas();
        $d->name = $value;
        $d->path = $p->path;
        $d->type = ($value == '.' || $value == '..') ? 'navigator' : get_extension($root . $p->path . DIRECTORY_SEPARATOR . $value);
        $data[] = $d;
    }
    return $data;
}
function get_extension($str) {
    if (is_dir($str)) return 'folder';
    $str = strtolower($str);
    $arr = explode('.', $str);
    if (sizeof($arr) < 2) {
        return "file-unknown";
    }
    return get_type($arr[sizeof($arr) - 1]);
}
function get_type($ext) {
    if (strpos($ext, 'html') !== false) return 'file-html';
    if (strpos($ext, 'css') !== false) return 'file-css';
    if (strpos($ext, 'pdf') !== false) return 'file-pdf';
    if (strpos($ext, 'sql') !== false) return 'file-sql';
    if (in_array($ext, explode(',', 'mp3,wav,pcm,wave,wma'))) return 'file-audio';
    if (in_array($ext, explode(',', 'mpg,mp4,ogg,webm,avi,wmv,mkv'))) return 'file-video';
    if (in_array($ext, explode(',', 'doc,docx'))) return 'file-doc';
    if (in_array($ext, explode(',', 'php,phps,inc'))) return 'file-php';
    if (in_array($ext, explode(',', 'js,javascript'))) return 'file-js';
    if (in_array($ext, explode(',', 'xls,xlsm,xlsx,csv'))) return 'file-xls';
    if (in_array($ext, explode(',', 'txt,text,md,json,rtf,ini,log'))) return 'file-text';
    if (in_array($ext, explode(',', 'bmp,jpeg,jpg,gif,tiff,png,pcx,emf,rle,dib,webp,ico,svg'))) return 'file-img';
    if (in_array($ext, explode(',', 'zip,tgz,bgz,gz,tz,rar'))) return 'file-zip';
    return 'file-unknown';
}
function Zip($source, $destination, $include_dir = true) {
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }
    if (file_exists($destination)) {
        unlink($destination);
    }
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }
    $source = str_replace('\\', DIRECTORY_SEPARATOR, realpath($source));
    if (is_dir($source) === true) {        
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        if ($include_dir) {
            $arr = explode("/", $source);
            $maindir = $arr[count($arr) - 1];
            $source = "";
            for ($i = 0; $i < count($arr) - 1; $i++) {
                $source.= DIRECTORY_SEPARATOR . $arr[$i];
            }
            $source = substr($source, 1);
            $zip->addEmptyDir($maindir);
        }
        foreach ($files as $file) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
            if (in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1), array('.', '..'))) continue;
            $file = realpath($file);
            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . DIRECTORY_SEPARATOR, '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source) === true) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
    return $zip->close();
}