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