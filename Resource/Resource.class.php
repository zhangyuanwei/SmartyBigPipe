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
    
    protected $path=null; //资源路径
    private $content=null; //输出内容
    private $depends=array(); //依赖资源列表
    private $id=null; //资源ID
    
    private $configHandlers=array('depend'=>'depend');
    
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
    // }}}
    
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
        return $this->path;
    }
    
    public function getDepends()
    {
        $this->getContent();
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
                    call_user_method($this->configHandlers[$config], $this, $argument);
                }
            }
        }
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
        $res=self::getResource($this->getAbsolutPath($path));
        $this->depends[$res->getId()]=$res;
    } // }}}
    
    abstract protected function genContent();
}
// vim600: sw=4 ts=4 fdm=marker syn=php

