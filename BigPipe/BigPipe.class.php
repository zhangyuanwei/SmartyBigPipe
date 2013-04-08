<?php
/** 
 *           File:  BigPipe.class.php
 *           Path:  ~/public_html/hao123/libs/BigPipe
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-02 14:39:19  
 *    Description: BigPipe 页面输出控制器
 */

if(!defined('BIGPIPE_BASE_DIR')) {
    define('BIGPIPE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

define("BIGPIPE_DEBUG", 1);

abstract class BigPipe // BigPipe 流控制 {{{
{
    const NONE=0;
    const HTML=1;
    const HEAD=2;
    const TITLE=3;
    const BODY=4;
    const BLOCK=5;
    const SCRIPT=6;
    
    const STAT_UNINIT=0;
    const STAT_FIRST=1;
    const STAT_LOOP=2;
    const STAT_END=3;
    
    const ATTR_PREFIX='ajax-';
    
    protected static $ajaxKey='__ajax__';
    protected static $sessionKey='__session__';
    protected static $nojsKey='__noscript__';
    protected static $jsLib='/common/js/boot.js';
    protected static $separator=' ';
    
    private static $state=self::STAT_UNINIT; // 当前状态
    private static $context=null; // 当前上下文 BigPipeContext
    
    private static $controller=null; // 输出控制器
    
    private static $savedAssertOptions=null; //保存的断言配置
    
    public static final function getController() // 根据请求参数得到控制器 {{{
    {
        $nojs=self::$nojsKey;
        if(isset($_GET[$nojs])||isset($_COOKIE[$nojs])) {
            setcookie($nojs, 1);
            if(!class_exists("NoScriptController")) {
                require(BIGPIPE_BASE_DIR . 'NoScriptController.class.php');
            }
            return new NoScriptController();
        }
        
        $ajax=self::$ajaxKey;
        $session=self::$sessionKey;
        if(isset($_GET[$ajax])&&isset($_GET[$session])) {
            $ids=$_GET[$ajax];
            if(empty($ids)) {
                $ids=null;
            } else {
                $ids=explode(self::$separator, $ids);
            }
            
            if(!class_exists("QuicklingController")) {
                require(BIGPIPE_BASE_DIR . 'QuicklingController.class.php');
            }
            return new QuicklingController(intval($_GET[$session]), $ids);
        } else {
            if(!class_exists("FirstController")) {
                require(BIGPIPE_BASE_DIR . 'FirstController.class.php');
            }
            return new FirstController();
        }
    }
    // }}}
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
        
        $key=self::getAttrKey('boot-uri');
        if(isset($config[$key])) {
            self::$jsLib=$config[$key];
        }
        
        self::$controller=self::getController();
        self::$state=self::STAT_FIRST;
        
        self::$context=new BigPipeContext(self::NONE);
        
        return true;
    } // }}}
    
    public static final function more() // {{{ 是否重复
    {
        assert('self::$state === self::STAT_FIRST || self::$state === self::STAT_LOOP');
        
        if(self::$controller->hasMore()) {
            self::$state=self::STAT_LOOP;
            return true;
        } else {
            self::$state=self::STAT_END;
            return false;
        }
    }
    //}}}
    
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
        return $context->opened=self::$controller->openTag($context);
    } // }}}
    
    public static final function close($type) // {{{ 标签处理完后
    {
        assert('self::$state === self::STAT_FIRST || self::$state === self::STAT_LOOP');
        $context=self::$context;
        assert('$context->type === $type');
        
        self::$controller->closeTag($context);
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
	
	const DEFAULT_PRIORITY=0;                    //默认优先级
    
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
                        $ret=call_user_method($method, $this, $context);
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
        return $this->doAction($this->getActionKey($context->type, self::ACTION_OPEN), $context);
    } // }}}
    
    protected final function closeTag($context) // {{{
    {
        return $this->doAction($this->getActionKey($context->type, self::ACTION_CLOSE), $context);
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
        $context->outputOpen();
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
        $context->outputClose();
    } // }}}
   
	/**
	 * startCollect 开始收集内容 {{{
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function startCollect($context){
		ob_start();
	} // }}}

	/**
	 * collectScript 收集脚本 {{{
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function collectScript($context){
		$context->parent->addScript(ob_get_clean(), $context->getBigPipeConfig("runtime", "onload"));
	} // }}}

	/**
	 * setBlockPriority 设置优先级 {{{
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function setBlockPriority($context){
		$context->setPriority($context->getBigPipeConfig("priority", self::DEFAULT_PRIORITY));
	} // }}}
} // }}}

class BigPipeContext // BigPipe上下文 {{{ 
{
    private static $priority_list=array();
    
    private $vars=null;
    
    public $type=null;
    public $config=null;
    public $uniqid=null;
    
    public $parent=null;
    public $children=null;
    public $opened=false;
    
    public $priority=-1;
    public $scripts=null;
    
    public function __construct($type, $config=null)
    {
        $this->type=$type;
        $this->config=$config;
        $this->children=array();
        $this->scripts=array();
        
        $this->vars=array();
    }
    
    public static function uniquePriority()
    {
        self::$priority_list=array_unique(self::$priority_list);
        asort(self::$priority_list, SORT_NUMERIC);
        return self::$priority_list;
    }
    
    public function setPriority($priority)
    {
        if($priority>$this->priority) {
            $this->priority=$priority;
            if(isset($this->parent)) {
                $this->parent->setPriority($priority+1);
            }
            self::$priority_list[]=$priority;
        }
    }
    
    public function addScript($content, $type)
    {
        if(!isset($this->scripts[$type])) {
            $this->scripts[$type]=array();
        }
        $this->scripts[$type][]=$content;
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
        case BigPipe::BLOCK:
            return $this->getBigPipeConfig("tag", "div");
        default:
        }
    }
    
    public function outputOpen($params=null)
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
        echo $text;
    }
    
    public function outputClose()
    {
        echo '</' . $this->getTagName() . '>';
    }
} // }}}

// vim600: sw=4 ts=4 fdm=marker syn=php

