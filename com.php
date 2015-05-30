<?php
ini_set('max_execution_time', 300);
$p = json_decode($_REQUEST['params']);

if ($p->order == 'initialize') {
    sleep(0.5);
    $src = getcwd() . DIRECTORY_SEPARATOR . 'ace-builds-master' . DIRECTORY_SEPARATOR . 'src-min-noconflict';
    $dst = getcwd() . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'src-min-noconflict';
    if (!file_exists($dst)) {
        $zip = new ZipArchive;
        file_put_contents("tmp.zip", file_get_contents("https://github.com/ajaxorg/ace-builds/archive/master.zip"));
        if (true === $zip->open("tmp.zip")) {
            $zip->extractTo(getcwd());
            $zip->close();
        }
        xcopy($src, $dst);
        rmdir_recursive(getcwd() . DIRECTORY_SEPARATOR . 'ace-builds-master');
        @unlink("tmp.zip");
    }
    echo json_encode('OK');
    exit;
}

@session_start();
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
        @rmdir_recursive($data);
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
    require_once('sql_func_proc/sqlfuncproc.php');
    $func = SqlFuncProc::getInstance($sql['conn'], $sql['user'], $sql['pass']);
    $data = $func->runQuery(chop($p->data), array(), false, true);
    if (isset($data['error'])) {
        echo json_encode($data);
    } else {
        echo $func->getHTMLtable($data, '', 'hovertable');
    }
}
if ($p->order == 'create_zip') {
    $rootPath = $root . str_replace('.', '', $p->path) . DIRECTORY_SEPARATOR . $p->file;
    $zipname  = is_dir($rootPath) ? dirname($rootPath) . DIRECTORY_SEPARATOR . basename($rootPath) . '.zip' : dirname($rootPath) . DIRECTORY_SEPARATOR . basename($rootPath) . '.zip';
    Zip($rootPath, $zipname);
}
function rmdir_recursive($dir) {
    $it = new RecursiveDirectoryIterator($dir);
    $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) {
        if ('.' === $file->getBasename() || '..' === $file->getBasename())
            continue;
        if ($file->isDir())
            @rmdir($file->getPathname());
        else
            @unlink($file->getPathname());
    }
    @rmdir($dir);
}
function xcopy($source, $dest, $permissions = 0755) {
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }
    if (is_file($source)) {
        return copy($source, $dest);
    }
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }
        xcopy("$source/$entry", "$dest/$entry", $permissions);
    }
    $dir->close();
    return true;
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
        $d       = new Datas();
        $d->name = $value;
        $d->path = str_replace('.', '', $p->path);
        $d->type = ($value == '.' || $value == '..') ? 'wde-folder-move' : get_extension($root . str_replace('.', '', $p->path) . DIRECTORY_SEPARATOR . $value);
        $data[]  = $d;
    }
    return $data;
}
function get_extension($str) {
    if (is_dir($str))
        return 'wde-folder';
    $str = strtolower($str);
    $arr = explode('.', $str);
    if (sizeof($arr) < 2) {
        return 'wde-file';
    }
    return get_type($arr[sizeof($arr) - 1]);
}
function get_type($ext) {
    if (strpos($ext, 'html') !== false) return 'wde-file-html';
    if (strpos($ext, 'css') !== false) return 'wde-file-css';
    if (strpos($ext, 'pdf') !== false) return 'wde-file-pdf';
    if (strpos($ext, 'sql') !== false) return 'wde-file-delimited';
    if (in_array($ext, explode(',', 'mp3,wav,pcm,wave,wma'))) return 'wde-file-music';
    if (in_array($ext, explode(',', 'mpg,mp4,ogg,webm,avi,wmv,mkv'))) return 'wde-file-video';
    if (in_array($ext, explode(',', 'doc,docx'))) return 'wde-file-word';
    if (in_array($ext, explode(',', 'php,phps,inc'))) return 'wde-file-php';
    if (in_array($ext, explode(',', 'js,javascript'))) return 'wde-file-javascript';
    if (in_array($ext, explode(',', 'xls,xlsm,xlsx,csv'))) return 'wde-file-excel';
    if (in_array($ext, explode(',', 'txt,text,md,json,rtf,ini,log'))) return 'wde-file-document';
    if (in_array($ext, explode(',', 'bmp,jpeg,jpg,gif,tiff,png,pcx,emf,rle,dib,webp,ico,svg'))) return 'wde-file-image';
    if (in_array($ext, explode(',', 'zip,tgz,bgz,gz,tz,rar'))) return 'wde-file-zip';
    return 'wde-file';
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
            $arr     = explode("/", $source);
            $maindir = $arr[count($arr) - 1];
            $source  = "";
            for ($i = 0; $i < count($arr) - 1; $i++) {
                $source .= DIRECTORY_SEPARATOR . $arr[$i];
            }
            $source = substr($source, 1);
            $zip->addEmptyDir($maindir);
        }
        foreach ($files as $file) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
            if (in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1), array(
                '.',
                '..'
            )))
                continue;
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
function getMimeType($filename) {
    $realpath = realpath($filename);
    if ($realpath && function_exists('finfo_file') && function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE')) {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $realpath);
    }
    if (function_exists('mime_content_type')) {
        return mime_content_type($realpath);
    }
    return false;
}
