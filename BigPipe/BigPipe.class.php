<?php
/** 
 *           File:  BigPipe.class.php
 *           Path:  BigPipe
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-02 14:39:19  
 *    Description: BigPipe 页面输出控制器
 */

if(!defined('BIGPIPE_BASE_DIR')) {
    define('BIGPIPE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

define("BIGPIPE_DEBUG", 0);

abstract class BigPipe // BigPipe 流控制 {{{
{
    const NONE=0;
    const HTML=1;
    const HEAD=2;
    const TITLE=3;
    const BODY=4;
    const PAGELET=5;
    const SCRIPT=6;
    const LINK=7;
    
    const STAT_UNINIT=0;
    const STAT_FIRST=1;
    const STAT_LOOP=2;
    const STAT_END=3;
    
    const ATTR_PREFIX='bigpipe-';
    
    protected static $ajaxKey='__bigpipe__';
    protected static $sessionKey='__session__';
    protected static $nojsKey='__noscript__';
    protected static $jsLib='/BigPipe/boot.js';
//    protected static $globalVar='bigPipe';
    protected static $separator=' ';
    
    private static $state=self::STAT_UNINIT; // 当前状态
    private static $context=null; // 当前上下文 BigPipeContext
    
    private static $controller=null; // 输出控制器
    
    private static $savedAssertOptions=null; //保存的断言配置

    private static $testHandler=null;//test标签的处理函数
    
    public static final function decideController() // 根据请求参数得到控制器 {{{
    {
        //$nojs=self::$nojsKey;
        //if(isset($_GET[$nojs])||isset($_COOKIE[$nojs])) {
        //    setcookie($nojs, 1);
        //    if(!class_exists("NoScriptController", false)) {
        //        require(BIGPIPE_BASE_DIR . 'NoScriptController.class.php');
        //    }
        //    return new NoScriptController();
        //}
        
        $ajax=self::$ajaxKey;
        $session=self::$sessionKey;
        if(isset($_GET[$ajax])&&isset($_GET[$session])) {
            $ids=$_GET[$ajax];
            if(empty($ids)) {
                $ids=null;
            } else {
                $ids=explode(self::$separator, $ids);
            }
            
            if(!class_exists("QuicklingController", false)) {
                require(BIGPIPE_BASE_DIR . 'QuicklingController.class.php');
            }
            return new QuicklingController(intval($_GET[$session]), $ids);
        } else {
            if(!class_exists("FirstController", false)) {
                require(BIGPIPE_BASE_DIR . 'FirstController.class.php');
            }
            return new FirstController();
        }
    }
    // }}}
    public static function getContext() // 得到当前上下文 {{{
    {
        return self::$context;
	} // }}}

	public static function getController(){ // 得到当前控制器 {{{
		if(!isset(self::$controller)){
			throw new Exception('controller is null');
		}
		return self::$controller;
	} // }}}
    public static function setTestHandler($handler){
        self::$testHandler = $handler;
    }
    // {{{ Smarty编译辅助函数
    public static final function compileParamsArray($params)
    {
        $items=array();
        $code='array(';
        foreach($params as $key=>$value) {
            $items[]="\"$key\"=>$value";
            //$code.="\"$key\"=>$value,";
        }
        $code.=join($items, ",");
        $code.=")";
        return $code;
    }

    public static final function compileParamsPlain($params)
    {
        $code="(\"";
        foreach($params as $key=>$value) {
            $code.=" $key=\" . $value . \"";
        }
        $code.="\")";
        return $code;
    }
    
    public static final function compileUniqid()
    {
        return var_export(uniqid(), true);
    }
    // }}}
    // {{{ 模板调用函数
    // {{{ 私有辅助函数
    private static function saveAssertOptions()
    {
        self::$savedAssertOptions=array();
        foreach(array(
            ASSERT_ACTIVE,
            ASSERT_WARNING,
            ASSERT_BAIL,
            ASSERT_QUIET_EVAL,
            ASSERT_CALLBACK
        ) as $key) {
            self::$savedAssertOptions[]=array(
                'key'=>$key,
                'val'=>assert_options($key)
            );
        }
    }
    private static function assertCallback($file, $line, $code)
    {
        echo "<hr />Assertion Failed:<br />File '$file'<br />Line '$line'<br />Code '$code'<br /><hr />";
    }
    private static function setAssertOptions()
    {
        if(defined("BIGPIPE_DEBUG")&&BIGPIPE_DEBUG) {
            assert_options(ASSERT_ACTIVE, 1);
            assert_options(ASSERT_WARNING, 1);
            assert_options(ASSERT_BAIL, 1);
            assert_options(ASSERT_QUIET_EVAL, 0);
            assert_options(ASSERT_CALLBACK, array(
                "self",
                "assertCallback"
            ));
        } else {
            assert_options(ASSERT_ACTIVE, 0);
        }
    }
    // }}}
    
    public static final function init($config) // {{{ 初始化控制器
    {
        self::saveAssertOptions();
        self::setAssertOptions();
        
        assert('self::$state === self::STAT_UNINIT');
        
        $key=self::getAttrKey('key');
        if(isset($config[$key])) {
            self::$ajaxKey=$config[$key];
        }
        
        $key=self::getAttrKey('session-key');
        if(isset($config[$key])) {
            self::$sessionKey=$config[$key];
        }
        
        $key=self::getAttrKey('nojs-key');
        if(isset($config[$key])) {
            self::$nojsKey=$config[$key];
        }
        
        $key=self::getAttrKey('jslib');
        if(isset($config[$key])) {
            self::$jsLib=$config[$key];
        }
        
//        $key=self::getAttrKey('var');
//        if(isset($config[$key])) {
//            self::$globalVar=$config[$key];
//        }
        
        self::$controller=self::decideController();
        self::$state=self::STAT_FIRST;
        
        self::$context=new BigPipeContext(self::NONE);
        
        return true;
    } // }}}
    
    public static final function more() // {{{ 是否重复
    {
		assert('self::$state === self::STAT_FIRST || self::$state === self::STAT_LOOP');
		$controller = self::getController();
        
        if($controller->hasMore()) {
            self::$state=self::STAT_LOOP;
            return true;
        } else {
            self::$state=self::STAT_END;
            return false;
        }
    }
    //}}}
    
    public static final function tag($type, $config, $uniqid) // 自闭合标签{{{
    {
        self::open($type, $config, $uniqid);
        self::close($type);
    } // }}}
    public static final function test($config,$template,$flag=false){
        //决定是否需要渲染模板
        if(isset(self::$testHandler)){
            return call_user_func(self::$testHandler,$config,$template,$flag);
        }
        return true;
    }
    public static final function opened(){
        return self::$context->opened;
    }
    public static final function open($type, $config, $uniqid) // {{{ 打开某个标签
    {

		assert('self::$state === self::STAT_FIRST || self::$state === self::STAT_LOOP');

        if(self::has($uniqid)) {
            $context=self::$context;
            assert('isset($context->children[$uniqid])');
            $context=$context->children[$uniqid];
            assert('$context->type === $type');
        } else {
            $context=new BigPipeContext($type, $config);
            $context->parent=self::$context;
            $context->uniqid=$uniqid;
            self::$context->children[$uniqid]=$context;
        }
        
        self::$context=$context;
		$controller = self::getController();

        return $context->opened=$controller->openTag($context);
    } // }}}
    
    public static final function close($type) // {{{ 标签处理完后
    {
        assert('self::$state === self::STAT_FIRST || self::$state === self::STAT_LOOP');
        $context=self::$context;
        assert('$context->type === $type');
        
		$controller = self::getController();
        $controller->closeTag($context);
        $context->opened=false;
        
        $context=$context->parent;
        self::$context=$context;
        return $context->opened;
    } // }}}
    
    public static final function has($uniqid) // {{{ 查看当前上下文是否有子环境
    {
        $context=self::$context;
        return isset($context->children[$uniqid]) ? $context->children[$uniqid] : false;
    }
    // }}}
    // }}}
    
    public static function getAttrKey($key)
    {
        return self::ATTR_PREFIX . $key;
    }
    
    abstract protected function openTag($context);
    abstract protected function closeTag($context);
    abstract protected function hasMore();
}
// }}}

abstract class PageController extends BigPipe // {{{
{
    const ACTION_OPEN=1;
    const ACTION_CLOSE=2;
    const ACTION_MORE=3;
    
    const DEFAULT_PRIORITY=":"; //默认优先级
    
    protected $actionChain=null;
    
    abstract protected function getActionKey($context, $action);
    
    private function doAction($key, $context) // {{{
    {
        $ret=null;
        $actions=null;
        
        if(isset($this->actionChain[$key])) {
            $actions=$this->actionChain[$key];
            if(is_string($actions)) {
                $actions=array(
                    $actions
                );
            }
            if(is_array($actions)) {
                foreach($actions as $method) {
                    if(is_string($method)) {
                        $ret=call_user_func(array($this, $method), $context);
                    } else {
                        $ret=$method;
                    }
                }
            } else {
                $ret=$actions;
            }
        }
        return $ret;
    } // }}}
    
    protected final function openTag($context) // {{{
    {
        switch($context->type) {
        case BigPipe::SCRIPT:
            $src=$context->getConfig("src");
            if($src===null) { // 没有src属性，收集内容
                $this->startCollect($context);
                return true;
            } else {
                $context->parent->addScriptLink($src);
                return false;
            }
        case BigPipe::LINK:
            assert('$context->getConfig("rel") === "stylesheet"');
            assert('$context->getConfig("type", "text/css") === "text/css"');
            $href=$context->getConfig("href");
            $context->parent->addStyleLink($href);
            return false;
        default:
            return $this->doAction($this->getActionKey($context->type, self::ACTION_OPEN), $context);
        }
    } // }}}
    
    protected final function closeTag($context) // {{{
    {
        switch($context->type) {
        case BigPipe::SCRIPT:
            if($context->opened) {
                $this->collectScript($context);
            }
            break;
        case BigPipe::LINK:
            //do nothing
            break;
        default:
            $this->doAction($this->getActionKey($context->type, self::ACTION_CLOSE), $context);
        }
    } // }}}
    
    protected final function hasMore() // {{{
    {
        return $this->doAction($this->getActionKey(BigPipe::NONE, self::ACTION_MORE), null);
    } // }}}
    
    /**
     * outputOpenTag 输出打开标签 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputOpenTag($context)
    {
        echo $context->getOpenHTML();
    } // }}}
    
    /**
     * outputCloseTag 输出闭合标签 {{{ 
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputCloseTag($context)
    {
        echo $context->getCloseHTML();
    } // }}}
    
    /**
     * startCollect 开始收集内容 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function startCollect($context)
    {
        ob_start();
    } // }}}

	/**
	 * clearCollect 清除收集的内容 {{{
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function clearCollect($context)
	{
		ob_clean();
	} // }}}

    /**
     * collectScript 收集脚本 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function collectScript($context)
    {
        $context->parent->addScript(ob_get_clean(), $context->getBigPipeConfig("on", "load"));
    } // }}}
    
    /**
     * setPageletPriority 设置优先级 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function setPageletPriority($context)
    {
        $priority=$context->getBigPipeConfig("priority");
        if($priority===null) {
            $priority=self::DEFAULT_PRIORITY;
        } else {
            $priority=intval($priority);
        }
        $context->setPriority($priority);
    } // }}}
    
    /**
     * getDependURLs 得到一组资源的依赖URL {{{
     * 
     * @param mixed $pathList 
     * @access protected
     * @return void
     */
    protected function getDependURLs($pathList)
	{

    } // }}}
} // }}}

class BigPipeContext // BigPipe上下文 {{{ 
{
    private static $priority_list=array();
    private static $max_priority=0;
    
    private $vars=null;
    
    public $type=null;
    public $config=null;
    public $uniqid=null;
    
    public $parent=null;
    public $children=null;
    public $opened=false;
    
    public $priority=null; // 输出优先级
    public $priorityArray=null; //输出数组
    public $scripts=null; // script内容
    public $scriptLinks=null; // js 链接
    public $styles=null; // style内容
    public $styleLinks=null; // css 链接

	public $shouldShow = false;
    
    public function __construct($type, $config=null)
    {
        $this->type=$type;
        $this->config=$config;
        $this->children=array();
        $this->scripts=array();
        $this->scriptLinks=array();
        $this->styles=array();
        $this->styleLinks=array();
        
        $this->vars=array();
    }
    
    private static function getPriorityString($arr)
    {
        $str=array();
        foreach($arr as $pri) {
            $str[]=str_pad($pri, self::$max_priority, '0', STR_PAD_LEFT);
        }
        $str=implode('/', $str) . ".";
        return $str;
    }
    
    public static function uniquePriority()
    {
        $list=array();
        foreach(self::$priority_list as $arr) {
            $list[]=self::getPriorityString($arr);
        }
        $list=array_unique($list, SORT_STRING);
        rsort($list, SORT_STRING);
        return $list;
    }
    
    public function setPriority($priority)
    {
        if($this->parent!==null&&$this->parent->priorityArray!==null) {
            $priorityArray=$this->parent->priorityArray;
        } else {
            $priorityArray=array();
        }
        $priorityArray[]=$priority;
        $this->priorityArray=$priorityArray;
        self::$priority_list[]=$this->priorityArray;
        self::$max_priority=max(self::$max_priority, strlen($priority));
    }
    
    public function getPriority()
    {
        if($this->priority===null) {
            $this->priority=self::getPriorityString($this->priorityArray);
        }
        return $this->priority;
    }
    
    public function addScript($content, $type)
    {
        if(!isset($this->scripts[$type])) {
            $this->scripts[$type]=array();
        }
        $this->scripts[$type][]=$content;
    }
    
    public function addScriptLink($link)
    {
        $this->scriptLinks[]=$link;
    }
    
    public function addStyle($content)
    {
        $this->styles[]=$content;
    }
    
    public function addStyleLink($link)
    {
        $this->styleLinks[]=$link;
    }
    
    public function getBigPipeConfig($key, $default=null)
    {
        return $this->getConfig(BigPipe::getAttrKey($key), $default);
    }
    
    public function getConfig($key, $default=null)
    {
        $config=$this->config;
        if(isset($config[$key])) {
            return $config[$key];
        }
        return $default;
    }
    
    public function get($key, $default=null)
    {
        if(isset($this->vars[$key])) {
            return $this->vars[$key];
        }
        return $default;
    }
    
    public function set($key, $value=null)
    {
        if(isset($value)) {
            $this->vars[$key]=$value;
        } elseif(isset($this->vars[$key])) {
            unset($this->vars[$key]);
        }
        return $value;
    }
    
    private function getTagName()
    {
        switch($this->type) {
        case BigPipe::HTML:
            return 'html';
        case BigPipe::HEAD:
            return 'head';
        case BigPipe::TITLE:
            return 'title';
        case BigPipe::BODY:
            return 'body';
        case BigPipe::SCRIPT:
            return 'script';
        case BigPipe::LINK:
            return 'link';
        case BigPipe::PAGELET:
            return $this->getBigPipeConfig("tag", "div");
        default:
        }
    }
    
    public function getOpenHTML($params=null)
    {
        $text='<' . $this->getTagName();
        if($params!==false) {
            if(!isset($params))
                $params=$this->config;
            foreach($params as $key=>$value) {
                if(strpos($key, BigPipe::ATTR_PREFIX)!==0) {
                    $text.=" $key=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8', true) . "\"";
                }
            }
        }
        $text.='>';
        return $text;
    }
    
    public function getCloseHTML()
    {
        return '</' . $this->getTagName() . '>';
    }
} // }}}

// vim600: sw=4 ts=4 fdm=marker syn=php

