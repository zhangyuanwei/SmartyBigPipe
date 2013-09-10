<?php
/** 
 *           File:  Resource.class.php
 *           Path:  Resource
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-08 13:58:18  
 *    Description: 资源处理类 
 */

define('RESOURCES_BASE_DIR', dirname(__FILE__));
define('RESOURCES_PLUGIN_DIR', RESOURCES_BASE_DIR . DIRECTORY_SEPARATOR . 'resources');

class Resource
{
	private static $fromMap = false;  //资源映射是否来源于内存
    private static $rootDirs=array(); //根目录
    private static $resourceHandlers=null; //处理器表
    private static $resourcesMap=array(); //资源映射表
    private static $idChars=null; //id可用的字符
	
	private $path=null; //资源路径
	private $url = null; //资源请求地址
    private $content=null; //输出内容
    private $depends=array(); //依赖资源列表
	private $asyncModule = array();//异步模块列表
    private $id=null; //资源ID
	private $type = null;
	protected $cmd=true;//资源是否是业务层cmd
    

    //private $configHandlers=array('depend'=>'depend', 'import'=>'import');
	private $configHandlers=array();
	private $filters=array(); //过滤器
	private $vars = array();
    
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
    protected function registerFilter($type, $callback)
    {
        if(isset($this->filters[$type])) {
			$this->filters[$type][]=$callback;
        } else {
			$this->filters[$type]=array(
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
    public static function getResource($path,$content=null)
    {
        if(isset(self::$resourcesMap[$path])) {
            return self::$resourcesMap[$path];
        }

		if(!isset($content) && self::$fromMap){
        	throw new Exception("No rsource path \"$path\" in map");
		}
			
        if(null===self::$resourceHandlers) {
            self::requireAllResourceHandlers();
        }
        
        foreach(self::$resourceHandlers as $pattern=>$handler) {
            if(preg_match($pattern, $path)) {
                $res=new $handler($path);
				if($content!==null)
					$res->content = $res->runFilter("post",$content);
                self::$resourcesMap[$path]=$res;
                return $res;
            }
        }
        throw new Exception("No handler for path \"$path\"");
    } // }}}

	/**
	    * setResourceMap  设置资源表 
	    * 
	    * @param array $map 
	    * @static
	    * @access public
	    * @return void
	 */
	public static function setResourceMap($map)
	{
		self::$fromMap = true;
		$idMap = array();
		$modMap = array();
		foreach($map as $key=>$data)
		{
			$res=new Resource();
			$res->id = $key;
			$res->url = $data["url"];
			$res->type = $data["type"];
			$res->content = "";
			$idMap[$key] = $res;
			foreach($data["paths"] as $path)
			{
				self::$resourcesMap[$path] = $res;
			}
			foreach($data["define"] as $name)
			{
				$modMap[$name] = $res;
			}
				
		}
		foreach($map as $key=>$data)
		{
			$res = $idMap[$key];
			foreach($data["mods"] as $name)
			{
				$res->asyncModule[$name] = $modMap[$name];
			}
			foreach($data["deps"] as $id)
			{
				$res->depends[$id] = $idMap[$id];
			}
		}
	}
    
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
		if(self::$fromMap){
		   return isset(self::$resourcesMap[$path]);
		}
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
    } // }}}
    
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
	public static function getDependResource($list, &$reference=null, &$modules=null, &$asyncs=null) 
    {
        $resolved=array();
	
		//output
		$asyncs=array();
        $depends=array();		
		$modules=array();
        
        $list=array_reverse($list);
        
		while(!empty($list)) {
		/*	foreach($list as $id=>$res)
			{
				echo $res->getUrl()."|";
			}
			echo "\n";*/
            $res=end($list);
            $id=key($list);

            if(isset($reference[$id])||isset($depends[$id])) { // 依赖列表中已经存在
                array_pop($list);
                continue;
            }
            
            if(isset($resolved[$id])) { // 依赖已经解决
				$depends[$id]=$res;
				$reference[$id]=$res;
				unset($asyncs[$id]);
                array_pop($list);
                continue;
            }
            
            $more=false; // 是否有未添加的依赖资源
            foreach(array_reverse($res->getDepends()) as $did=>$dep) {
                if(!isset($depends[$did])) {
                    unset($list[$did]);
                    $list[$did]=$dep;
                    $more=true;
                }
			}

			foreach($res->getAsyncModules() as $name=>$async)
			{
				$modules[$name] = $async;
				$aid = $async->getId();
				if(!isset($depends[$aid]))
				{
					$asyncs[$aid] = $async;
				}
			}

            if($more) {
                $resolved[$id]=true;
            } else {
				$depends[$id]=$res;
				$reference[$id]=$res;
				unset($asyncs[$id]);
                array_pop($list);
            }
		}
		
		reset($asyncs);
		while(($res = current($asyncs))!==FALSE){
			 foreach($res->getDepends() as $did=>$dep)
			 {
				if(!isset($depends[$did]) && !isset($asyncs[$did]))
				{
					$asyncs[$did] = $dep;
				}
			 }
			 foreach($res->getAsyncModules() as $name=>$async)
			 {
				$modules[$name] = $async;
				$aid = $async->getId();
				if(!isset($depends[$aid]) && !isset($asyncs[$aid]))
				{
					$asyncs[$aid] = $async;
				}
			 }
			 next($asyncs);
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
    protected function __construct($path=null)
    {
		if(isset($path))
		{
			if(substr($path, 0, 1)=='/') {
				$this->path=$path;
				$this->registerConfigHandler("depend", array($this, "depend"));
				$this->registerConfigHandler("import", array($this, "import"));
				$this->registerConfigHandler("cmd",   array($this,"cmd"));
			} else {
				throw new Exception("Resource path mast start whith\"\/\".");
			}
		}
    } // }}}
    
	public function getType()
	{
		return $this->type;
	}

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
        return $this->url!==null ? $this->url : $this->path."?".$this->getId();
        //return '/rsrc.php?uri=' . urlencode($this->path);
        //return '/rsrc.php?uri='.urlencode($this->path).'&v='.$this->getId().'.'.$this->getType();
    }
    
    public function getDepends()
    {
		if(empty($this->depends))
			$this->getContent();
        return $this->depends;
    }

	public function getAsyncModules()
	{
		if(empty($this->asyncModule))
		    $this->getContent();
		return $this->asyncModule;
	}
    
    public function getContent()
    {
        if(null===$this->content) {			
			$this->content = $this->runFilter("post",$this->genContent());
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

	protected function nameToPath($name)
	{
		$path = str_replace(".","/",$name);
		$path = "/".$path.".js";
		return $path;
	}

	public function get($key, $default = null){
		return isset($this->vars[$key]) ? $this->vars[$key] : $default;
	}

	public function set($key, $value){
        if(isset($value)) {
            $this->vars[$key]=$value;
        } elseif(isset($this->vars[$key])) {
            unset($this->vars[$key]);
        }
        return $value;
	}

    public function registerConfigHandler($config, $callback)
    {
        $this->configHandlers[$config][]=$callback;
    }
    
    protected function parseConfig($code)
    {
        $output='';
        if(preg_match_all('!@(?<config>\w+)(?:[ \t]+(?<argument>true|false|\d+|"[^"]*"|\'[^\']*\'))?!', $code, $matches, PREG_SET_ORDER)) {
            foreach($matches as $item) {
                $config=$item['config'];
                if(isset($this->configHandlers[$config])) {
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
                    foreach($this->configHandlers[$config] as $callback) {
                        $output.=call_user_func($callback, $argument, $this);
                    }
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
	   * async 添加异步模块 {{{
	   * 
	   * @param mixed $name 
	   * @param mixed $path 
	   * @access protected
	   * @return void
	  */
	protected function async($name, $path)
	{
		try
		{
			$res=self::getResource($this->getAbsolutPath($path));
			$this->asyncModule[$name]=$res;
		}
		catch(Exception $e)
		{
			trigger_error("\"" . $this->path . "\" async \"$name\" error:" . $e->getMessage());
		}
	}// }}}
    
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

	protected function cmd($cmd)
	{
		$this->cmd = $cmd;
	}
    
    /**
     * expires 设置过期时间 {{{ 
     * 
     * @param int $seconds 
     * @access public
     * @return void
     */
    public function expires($seconds=31104000)
    {
        $time=date('D, d M Y H:i:s', time()+$seconds) . ' GMT';
        header("Expires: $time");
        header("Cache-Control: max-age=$seconds");
    }
    //}}}
    
    protected function runFilter($type, $content)
    {
		if(!empty($this->filters[$type])) {
			foreach($this->filters[$type] as $callback) {
				if(is_array($callback)) {
					$content=call_user_func($callback, $content, $this);
                } else {
					$content=$this->$callback($content, $this);
                }
            }
        }
        return $content;
    }
    
    protected function genContent()
    {
        if($file=$this->getFilePath()) {
            $content=file_get_contents($file);
            $content=$this->runFilter("pre", $content);
            return $content;
        } else {
            throw new Exception("Resource \"$this->path\" not found.");
        }
    }
}
// vim600: sw=4 ts=4 fdm=marker syn=php

