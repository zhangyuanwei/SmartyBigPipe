<?php

require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'common.php');

if(isset($_GET['type'])) {
    if(isset($_GET['url'])) {
        $urls=$_GET['url'];
    } else {
        $urls=array();
    }
    switch($_GET['type']) {
    case 'css':
        header('Content-Type: text/css');
        foreach($urls as $url) {
            echo "@import \"$url\";\n";
        }
        break;
    }
} else {
    $uri = $_GET['uri'];
    //$uri=$_SERVER['REQUEST_URI'];
    $list=explode("?", $uri);
    $path=$list[0];
    $resource=getResource($path);
    $resource->output();
}

