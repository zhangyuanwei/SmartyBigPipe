<?php
/** 
 *           File:  Resource.class.php
 *           Path:  ~/public_html/hao123/libs/Resource
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-08 13:58:18  
 *    Description: 资源处理类 
 */

define('RESOURCES_BASE_DIR', dirname(__FILE__));
define('RESOURCES_PLUGIN_DIR', RESOURCES_BASE_DIR . DIRECTORY_SEPARATOR . 'resources');

abstract class Resource
{
    private static $rootDirs=array(); //根目录
    private static $resourceHandlers=null; //处理器表
    private static $resourcesMap=array(); //资源映射表
    private static $idChars=null; //id可用的字符
    private static $filters=array(); //过滤器
    
    private $path=null; //资源路径
    private $content=null; //输出内容
    private $depends=null; //依赖资源列表
    private $id=null; //资源ID
    
    private $configHandlers=array('depend'=>'depend', 'import'=>'import');
    
    // 静态方法	{{{
    /**
     * setRootDir  设置静态文件根目录 {{{
     * 
     * @param mixed $dirs 
     * @param mixed $append 
     * @static
     * @access public
     * @return void
     */
    public static function setRootDir($dirs, $append=false)
    {
        if(!is_array($dirs)) {
            $dirs=array(
                $dirs
            );
        }
        if($append) {
            self::$rootDirs=array_merge($dirs, self::$rootDirs);
        } else {
            self::$rootDirs=$dirs;
        }
    } // }}}
    
    /**
     * registerFilter 注册过滤器 {{{
     * 
     * @param mixed $type 
     * @param mixed $callback 
     * @static
     * @access public
     * @return void
     */
    public static function registerFilter($type, $callback)
    {
        if(isset(self::$filters[$type])) {
            self::$filters[$type][]=$callback;
        } else {
            self::$filters[$type]=array(
                $callback
            );
        }
    } // }}}
    
    /**
     * getResource 得到资源 {{{
     * 
     * @param mixed $path 
     * @static
     * @access public
     * @return void
     */
    public static function getResource($path)
    {
        if(isset(self::$resourcesMap[$path])) {
            return self::$resourcesMap[$path];
        }
        
        if(null===self::$resourceHandlers) {
            self::requireAllResourceHandlers();
        }
        
        foreach(self::$resourceHandlers as $pattern=>$handler) {
            if(preg_match($pattern, $path)) {
                $res=new $handler($path);
                self::$resourcesMap[$path]=$res;
                return $res;
            }
        }
        throw new Exception("No handler for path \"$path\"");
    } // }}}
    
    /**
     * hasResource  资源是否存在 {{{
     * 
     * @param mixed $path 
     * @static
     * @access public
     * @return void
     */
    public static function hasResource($path)
    {
        return self::getResource($path)->exists();
    } // }}}
    
    /**
     * requireAllResourceHandlers 加载所有资源处理类 {{{
     * 
     * @static
     * @access private
     * @return void
     */
    private static function requireAllResourceHandlers()
    {
        $entries=scandir(RESOURCES_PLUGIN_DIR);
        foreach($entries as $entry) {
            $file=RESOURCES_PLUGIN_DIR . DIRECTORY_SEPARATOR . $entry;
            if(preg_match('/^.*\.php$/', $entry, $matches)) {
                require_once($file);
            }
        }
    } // }}}
    
    /**
     * registerHandler  注册资源处理类 {{{ 
     * 
     * @param mixed $pattern 
     * @param mixed $handler 
     * @static
     * @access public
     * @return void
     */
    public static function registerHandler($pattern, $handler)
    {
        self::$resourceHandlers[$pattern]=$handler;
    } // }}}
    
    /**
     * pathToResource 批量生成Resources对象 {{{ 
     * 
     * @param mixed $list 
     * @static
     * @access public
     * @return void
     */
    public static function pathToResource($list)
    {
        $ret=array();
        foreach($list as $path) {
            $res=self::getResource($path);
            $ret[$res->getId()]=$res;
        }
        return $ret;
    }// }}}
    
    /**
     * resourceToURL 批量得到资源地址 {{{
     * 
     * @param mixed $list 
     * @static
     * @access public
     * @return void
     */
    public static function resourceToURL($list)
    {
        $ret=array();
        foreach($list as $key=>$res) {
            $ret[$key]=$res->getURL();
        }
        return $ret;
    } // }}}
    
    /**
     * getDependResource 得到列表中资源依赖关系 {{{
     * 
     * @param mixed $list 
     * @param mixed $reference 
     * @static
     * @access public
     * @return void
     */
    public static function getDependResource($list, $reference=null)
    {
        $resolved=array();
        $depends=array();
        
        $list=array_reverse($list);
        
        while(!empty($list)) {
            $res=end($list);
            $id=key($list);
            
            if(isset($reference[$id])||isset($depends[$id])) { // 依赖列表中已经存在
                array_pop($list);
                continue;
            }
            
            if(isset($resolved[$id])) { // 依赖已经解决
                $depends[$id]=$res;
                array_pop($list);
                continue;
            }
            
            $more=false; // 是否有未填加的依赖资源
            foreach($res->getDepends() as $did=>$dep) {
                if(!isset($depends[$did])) {
                    unset($list[$did]);
                    $list[$did]=$dep;
                    $more=true;
                }
            }
            
            if($more) {
                $resolved[$id]=true;
            } else {
                $depends[$id]=$res;
                array_pop($list);
            }
		}
		return $depends;
    } // }}}
    
    /**
     * scanResources 查找所有资源 {{{
     * 
     * @static
     * @access public
     * @return void
     */
    public static function scanResources()
    {
        $resources=array();
        foreach(self::$rootDirs as $root) {
            $dirs=array(
                "/"
            );
            while(!empty($dirs)) {
                $path=array_pop($dirs);
                $dir=$root . $path;
                foreach(scandir($dir) as $entry) {
                    if($entry==="."||$entry==="..")
                        continue;
                    $file=$dir . $entry;
                    if(is_dir($file)) {
                        $dirs[]=$path . $entry . "/";
                    } else {
                        try {
                            $res=self::getResource($path . $entry);
                            $resources[$res->getId()]=$res;
                        }
                        catch(Exception $e) {
                        }
                    }
                }
            }
        }
        return $resources;
    } // }}}
    
    // }}}
    /**
     * __construct 构造函数 {{{ 
     * 
     * @param mixed $path 
     * @access protected
     * @return void
     */
    protected function __construct($path)
    {
        if(substr($path, 0, 1)=='/') {
            $this->path=$path;
        } else {
            throw new Exception("Resource path mast start whith\"\/\".");
        }
    } // }}}
    
    public function getPath()
    {
        return $this->path;
    }
    
    public function getId()
    {
        if(null===$this->id) {
            $seed=$this->getContent();
            if(null===Resource::$idChars) {
                Resource::$idChars=array_merge(range(65, 90), range(97, 122));
            }
            $count=count(Resource::$idChars);
            $hash_code=unpack('S*', sha1($seed, true));
            $id='';
            foreach($hash_code as $n) {
                $id.=chr(Resource::$idChars[fmod($n, $count)]);
            }
            $this->id=$id;
        }
        return $this->id;
    }
    
    public function getURL()
    {
        //return $this->path;
        return '/rsrc.php?uri='.urlencode($this->path).'&v='.$this->getId().'.'.$this->getType();
    }
    
    public function getDepends()
    {
        if(null===$this->depends) {
            $this->depends=array();
            $this->getContent();
        }
        return $this->depends;
    }
    
    public function getContent()
    {
        if(null===$this->content) {
            $this->content=$this->genContent();
        }
        return $this->content;
    }
    
    protected function getFilePath()
    {
        foreach(self::$rootDirs as $root) {
            $file=$root . $this->path;
            if(file_exists($file)) {
                return $file;
            }
        }
        return false;
    }
    
    protected function getAbsolutPath($path)
    {
        if(substr($path, 0, 1)!='/') {
            $path=dirname($this->path) . '/' . $path;
        }
        $list=explode('/', $path);
        $path=array();
        foreach($list as $entry) {
            if($entry===".") {
                continue;
            } elseif($entry==="..") {
                if(!empty($path)) {
                    array_pop($path);
                }
            } elseif($entry==='') {
                continue;
            } else {
                $path[]=$entry;
            }
        }
        return '/' . implode('/', $path);
    }
    
    protected function parseConfig($code)
	{
		$output = '';
        if(preg_match_all('!@(?<config>\w+)(?:[ \t]+(?<argument>true|false|\d+|"[^"]*"|\'[^\']*\'))?!', $code, $matches, PREG_SET_ORDER)) {
            foreach($matches as $item) {
                $config=$item['config'];
                $argument=null;
                if(isset($item['argument'])) {
                    $argument=$item['argument'];
                    if($argument==="true") {
                        $argument=true;
                    } else if($argument==="false") {
                        $argument=false;
                    } else if($argument[0]==='"'||$argument[0]==="'") {
                        $argument=substr($argument, 1, -1);
                    } else {
                        $argument=intval($argument);
                    }
                }
                if(isset($this->configHandlers[$config])) {
                    $output .= call_user_func(array($this, $this->configHandlers[$config]), $argument);
                }
            }
		}
		return $output;
    }
    
    protected function exists()
    {
        return $this->getFilePath()!==false;
    }
    
    /**
     * depend 依赖文件 {{{
     * 
     * @param mixed $path 
     * @access protected
     * @return void
     */
    protected function depend($path)
    {
        try {
            $res=self::getResource($this->getAbsolutPath($path));
            $this->depends[$res->getId()]=$res;
        }
        catch(Exception $e) {
            trigger_error("\"" . $this->path . "\" depend \"$path\" error:" . $e->getMessage());
        }
    } // }}}

    /**
     * import 引入文件 {{{
     * 
     * @param mixed $path 
     * @access protected
     * @return void
     */
    protected function import($path)
    {
        try {
			$res=self::getResource($this->getAbsolutPath($path));
			return $res->getContent();
        }
        catch(Exception $e) {
            trigger_error("\"" . $this->path . "\" import \"$path\" error:" . $e->getMessage());
        }
    } // }}}

	/**
	 * expires 设置过期时间 {{{ 
	 * 
	 * @param int $seconds 
	 * @access public
	 * @return void
	 */
	public function expires($seconds = 31104000){
		$time = date('D, d M Y H:i:s', time() + $seconds) . ' GMT';
		header("Expires: $time");
		header("Cache-Control: max-age=$seconds");
	}
	//}}}
	
	protected function genContent()
    {
        if($file=$this->getFilePath()) {
            $content=file_get_contents($file);
            $type='pre';
            if(!empty(self::$filters[$type])) {
                foreach(self::$filters[$type] as $key=>$name) {
                    if(is_array(self::$filters[$type][$key])) {
                        $content=call_user_func(self::$filters[$type][$key], $content, $this);
                    } else {
                        $content=self::$filters[$type][$key]($content, $this);
                    }
                }
            }
            return $content;
        } else {
            throw new Exception("Resource \"$this->path\" not found.");
        }
    }
}
// vim600: sw=4 ts=4 fdm=marker syn=php

