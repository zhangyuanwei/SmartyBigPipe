<?php
define('FE_BASE_DIR', dirname(__FILE__));

if(isset($_GET['type'])){
	if(isset($_GET['url'])){
		$urls = $_GET['url'];
	}else{
		$urls = array();
	}
	switch($_GET['type']){
		case 'css':
			header('Content-Type: text/css');
			foreach($urls as $url){
				echo "@import \"$url\";\n";
			}
			break;
	}
}else{
	require(FE_BASE_DIR . '/Resource.class.php');
	Resource::setRootDir(FE_BASE_DIR);
	//$uri = $_GET['uri'];
	$uri = $_SERVER['REQUEST_URI'];
	$list = explode("?", $uri);
	$path = $list[0];
	$resource = Resource::getResource($path);
	$resource->output();
}
