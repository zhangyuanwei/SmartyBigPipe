<?php

define('RESOURCES_BASE_DIR', dirname(__FILE__));
define('RESOURCES_PLUGIN_DIR', RESOURCES_BASE_DIR . DIRECTORY_SEPARATOR . 'resources');

abstract class Resource {

	protected $path = null;

	private $contents = null;
	private $depends = array();
	private $id = null;
	private $permanent = false;
	private $public = false;
	//
	private static $resourceHandlers = null;
	private static $resourcesMap = array();
	//
	private static $rootDirs = null;
	//
	private static $versionCharMap = null;

	/**
	 * __construct 构造函数 {{{
	 * 
	 * @param mixed $path 
	 * @access public
	 * @return void
	 */
	public function __construct($path){
		if(substr($path, 0, 1) == '/'){
			$this->path = $path;
		}else{
			throw new Exception('资源路径必须以"/"开始');
		}
	}
	// }}}
	/**
	 * getDependIds 得到所有依赖资源ID{{{
	 * 
	 * @access public
	 * @return void
	 */
	public function getDependIds(){
		return array_keys($this->getDependResources());
	}
	// }}}
	/**
	 * getDependResources  得到所有依赖的资源 {{{
	 * 
	 * @access public
	 * @return void
	 */
	public function getDependResources(){
		$this->getContents();
		return  $this->depends;
	}
	// }}}
	/**
	 * getContents 得到资源内容 {{{
	 * 
	 * @abstract
	 * @access public
	 * @return void
	 */
	public function getContents(){
		if(null === $this->contents){
			$this->contents = $this->genContents();
		}
		return $this->contents;
	}
	// }}}
	/**
	 * getAbsolutPath 得到绝对路径 {{{
	 * 
	 * @param mixed $path 
	 * @access private
	 * @return void
	 */
	private function getAbsolutPath($path){
		if(substr($path, 0, 1) != '/'){
			$path = dirname($this->path) . '/' . $path;
		}
		return $path;
	}
	// }}}
	/**
	 * depend 设置依赖文件，支持相对和绝对路径  {{{
	 * 
	 * @static
	 * @access public
	 * @return void
	 */
	public function depend($path){
		$resource = Resource::getResource($this->getAbsolutPath($path));
		$this->depends[$resource->getId()] = $resource;
	}
	//}}}	
	/**
	 * import 引入文件,支持相对和绝对路径{{{
	 * 
	 * @param mixed $path 
	 * @access public
	 * @return void
	 */
	public function import($path){
		$resource = Resource::getResource($this->getAbsolutPath($path), false);
		$contents = $resource->getContents();
		if(false !== $contents){
			echo "\n", $contents , "\n";
		}
	}
	// }}}
	/**
	 * getId 得到ID  {{{
	 * 
	 * @access public
	 * @return void
	 */
	public function getId(){
		if(null === $this->id){
			$id = '';
			$seed = $this->getContents();
			if(null === Resource::$versionCharMap){
				Resource::$versionCharMap = array_merge(range(65, 90), range(97, 122));
			}
			$count = count(Resource::$versionCharMap);
			$hash_code = unpack('S*', sha1($seed, true));
			foreach($hash_code  as $n){
				$id .= chr(Resource::$versionCharMap[fmod($n, $count)]);
			}
			$this->id = $id;
		}
		return $this->id;
	}
	// }}}
	/**
	 * getURL 得到资源URL {{{
	 * 
	 * @access public
	 * @return void
	 */
	public function getURL(){
		//return $this->path;
		//return $this->path.'?'.$this->getId().'.'.$this->getType();
		return 'rsrc.php?uri='.urlencode($this->path).'&v='.$this->getId().'.'.$this->getType();
	}
	// }}}
	/**
	 * setPermanent 设置为永久资源 {{{ 
	 * 
	 * @access public
	 * @return void
	 */
	public function setPermanent(){
		if(!$this->permanent){
			$this->permanent = true;
			foreach( $this->getDependResources() as $depend){
				$depend->setPermanent();
			}
		}
	}
	// }}}
	/**
	 * isPermanent 得到是否为永久资源 {{{
	 * 
	 * @access public
	 * @return void
	 */
	public function isPermanent(){
		return $this->permanent;
	}
	// }}}
	/**
	 * setPublic 设置文件的可见性 {{{ 
	 * 
	 * @param mixed $public 
	 * @access protected
	 * @return void
	 */
	protected function setPublic($public){
		$this->public = $this->public || $public;
	}
	//}}}
	/**
	 * setRootDir 设置基准目录 {{{
	 * 
	 * @param mixed $dirs 
	 * @param mixed $append 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function setRootDir($dirs, $append = false){
		if(!is_array($dirs)){
			$dirs = array($dirs);
		}
		if($append){
			Resource::$rootDirs = array_merge(Resource::$rootDirs, $dirs);
		}else{
			Resource::$rootDirs = $dirs;
		}
	}
	// }}}
	/**
	 * getFilePath 得到资源文件路径 {{{
	 * 
	 * @access public
	 * @return void
	 */
	public function getFilePath(){
		foreach(Resource::$rootDirs as $dir){
			$file = $dir . $this->path;
			if(file_exists($file)){
				return $file;
			}
		}
		return false;
	}
	// }}}
	/**
	 * requireAllResource 请求所有资源处理器 {{{
	 * 
	 * @static
	 * @access private
	 * @return void
	 */
	private static function requireAllResourceHandlers(){
		$entries = scandir(RESOURCES_PLUGIN_DIR);
		foreach($entries as $entry){
			$file = RESOURCES_PLUGIN_DIR . DIRECTORY_SEPARATOR . $entry;
			if(preg_match('/^.*\.php$/', $entry, $matches)){
				require_once($file);
			}
		}
	}
	//}}}
	/**
	 * setResourceHandler 设置资源处理器 {{{
	 * 
	 * @param mixed $pattern 
	 * @param mixed $handler 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function setResourceHandler($pattern, $handler){
		Resource::$resourceHandlers[$pattern] = $handler;
	}
	// }}}
	/**
	 * getResourceMap 得到所有资源列表 {{{
	 * 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getResourceMap(){
		return array_filter(Resource::$resourcesMap, 'Resource::filterPrivateResources');
	}
	// }}}
	/**
	 * filterPrivateResources 过滤未公开的资源  {{{
	 * 
	 * @param mixed $res 
	 * @static
	 * @access private
	 * @return void
	 */
	private static function filterPrivateResources($res){
		return $res->public;
	}
	//}}}
	/**
	 * getResource 得到资源 {{{ 
	 * 
	 * @param mixed $path 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getResource($path, $public = true){
		if(isset(Resource::$resourcesMap[$path])){
			$resource = Resource::$resourcesMap[$path];
			$resource->setPublic($public);
			return $resource;
		}
		if(null === Resource::$resourceHandlers){
			Resource::requireAllResourceHandlers();
		}
		if(!isset(Resource::$rootDirs)){
			Resource::$rootDirs = array($_SERVER["DOCUMENT_ROOT"]);
		}
		foreach(Resource::$resourceHandlers as $pattern => $handler){
			if(preg_match($pattern, $path)){
				$resource = new $handler($path);
				$resource->setPublic($public);
				Resource::$resourcesMap[$path] = $resource;
				return $resource;
			}
		}
		throw new Exception("没有找到资源\"$path\"的处理器");
	}
	// }}}
	/**
	 * expires 设置过期时间 {{{ 
	 * 
	 * @param int $seconds 
	 * @access public
	 * @return void
	 */
	public function expires($seconds = 1800){
		$time = date('D, d M Y H:i:s', time() + $seconds) . ' GMT';
		header("Expires: $time");
	}
	//}}}
	/**
	 * getType 得到资源类型
	 * 
	 * @access public
	 * @return void
	 */
	abstract function getType();
	/**
	 * genContentis 生成内容
	 * 
	 * @abstract
	 * @access public
	 * @return void
	 */
	abstract function genContents();
	/**
	 * output 输出资源 
	 * 
	 * @abstract
	 * @access public
	 * @return void
	 */
	abstract function output();
}
// vim600: sw=4 ts=4 fdm=marker syn=php
