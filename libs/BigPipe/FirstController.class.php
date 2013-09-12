<?php
/** 
 *           File:  FirstController.class.php
 *           Path:  BigPipe
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-07 16:36:51  
 *    Description: 第一次请求页面时的输出控制器 
 *    共分为3个阶段输出:
 *
 *    1.渲染并收集最外层结构(未被pagelet包裹的内容)并收集使用到的Js和CSS
 *    2.输出html、head、body和最外层结构，并且输出前端库及使用到的CSS和Js资源
 *    3.根据优先级输出各层结构，并输出依赖资源表
 *    4.结束
 */
class FirstController extends PageController
{
    const STAT_COLLECT_LAYOUT=1; // 收集最外层布局
    const STAT_OUTPUT_LAYOUT=2; // 输出最外层布局
    const STAT_OUTPUT_CONTENT=3; // 输出内容
    const STAT_OUTPUT_END=4; // 结束
    
    const HTML_CONTAINER_ID="html_container_id"; //HTML收集器id
    const HTML_OUTPUT_FORMAT="html_output_format"; //HTML输出方式
    
    private $state=self::STAT_COLLECT_LAYOUT; //初始状态
    private $layoutHTML=null; //布局结构
    private $layoutStyleLinks=null; //布局所用到的样式链接
    private $layoutStyles=null; //布局所用到的样式
	private $layoutScriptLinks=null;//布局所用到的js链接
    private $layoutScripts=null;//布局所用到的js

    private $resourceMap=array(); //资源表
    private $moduleNameMap=array();//异步模块表
    private $loadedResource=null;//已加载的资源表

    protected $priorityList=null; //优先级数组
    protected $currentPriority=null; //当前优先级
    protected $sessionId=0; //此次会话ID,用于自动生成不重复id,第一次默认为0
    protected $uniqIds=array(); //不重复id种子
    
    /**
     * __construct 构造函数
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
		$this->actionChain=array(
			'default'              => false,
			//收集阶段
//			'collect_html_open'    => array('startCollect', true),
			'collect_html_open'    => array('outputHtmlOpenTag', true),
			'collect_body_open'    => array('startCollect', true),
			'collect_body_close'   => array('collectBodyHtml','collectBodyStyle'),
			'collect_pagelet_open' => array('outputPlaceHolder', 'setPageletPriority', false),
//			'collect_html_close'   => array('clearCollect'),
			'collect_more'         => array('changeState', true),
			//输出布局阶段
//			'layout_html_open'     => array('outputOpenTag', false),
			'layout_head_open'     => array('outputOpenTag', 'outputNoscriptFallback', true),
			'layout_title_open'    => array('outputOpenTag', true),
			'layout_title_close'   => array('outputCloseTag'),
			'layout_head_close'    => array('collectHeadScripts', 'outputLayoutStyle', 'outputCloseTag'),
			'layout_body_open'     => array('outputOpenTag', 'outputLayout', false),
			'layout_body_close'    => array('outputBigPipeLibrary','outputLoadedResource','sessionStart','outputLayoutPagelet'/*, 'outputLayoutScript'*/),
			'layout_more'          => array('changeState', true),
			//输出内容阶段
			'content_body_open'    => array('setCurrentPriority', false),
			'content_pagelet_open' => array('pageletOpen'),
			'content_pagelet_close'=> array('pageletClose'),
			'content_more'         => array('changeState', true),
			//输出结束标签
			'end_body_close'       => array('sessionEnd','outputCloseTag'),
			'end_html_close'       => array('outputCloseTag'),
			'end_more'             => false,
        );
    }

	protected function outputHtmlOpenTag($context){
		//echo '<!DOCTYPE html><!--[if lt IE 7 ]><html class="ie6"><![endif]--><!--[if IE 7 ]><html class="ie7"><![endif]--><!--[if IE 8 ]><html class="ie8"><![endif]--><!--[if IE 9 ]><html class="ie9"><![endif]--><!--[if (gt IE 9)|!(IE)]><!--><html><!--<![endif]-->';
		echo '<!DOCTYPE html><html>';

	}

    
    protected function sessionStart($context)
    {
        echo "<script>BigPipe.sessionStart(", json_encode($this->sessionId), ");</script>\n";
    }
    
    protected function sessionEnd($context)
    {
        echo "<script>BigPipe.sessionEnd(", json_encode($this->sessionId), ");</script>\n";
    }
    
	/**
	    * collectBodyHtml 收集body html,保存优先级 {{{ 
	    * 
	    * @param mixed $context 
	    * @access protected
	    * @return void
	    */
	protected function collectBodyHtml($context)
	{
		$this->layoutHTML=ob_get_clean();
		$this->priorityList=BigPipeContext::uniquePriority();
	}

	protected function collectHeadScripts($context)
	{
	
		$this->layoutScripts = $context->scripts;
		$this->layoutScriptLinks = $context->scriptLinks;
	}
	/**
	    * collectBodyStyle 收集body style {{{ 
	    * 
	    * @param mixed $context 
	    * @access protected
	    * @return void
	    */
	protected function collectBodyStyle($context)
	{
		$this->layoutStyleLinks=$context->styleLinks;
		$this->layoutStyles=$context->styles;
	}
    
    /**
     * outputPlaceHolder 输出占位符 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputPlaceHolder($context)
    {
        if($context->parent->opened) { // 如果父级标签处于输出状态，则输出最外层节点
            $this->outputPageletOpenTag($context);
            $this->outputCloseTag($context);
        }
    } // }}}
    
    protected function outputPageletOpenTag($context)
	{
        if(!isset($context->config["id"]))
			$context->config["id"]=$this->sessionUniqId("__elm_");
		
		$height = $context->getBigPipeConfig("height");
		if(isset($height)){
			$height = intval($height);
			if(isset($context->config["style"])){
				$style = $context->config["style"];
			}else{
				$style = "";
			}

			$style = "height:{$height}px;" . $style;

			$context->config["style"] = $style;
		}
        $this->outputOpenTag($context);
    }
    
    /**
     * outputNoscriptFallback 输出noscript跳转 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputNoscriptFallback($context)
    {
        //$uri=$_SERVER["REQUEST_URI"];
        //$uri=$uri . (strpos($uri, "?")===false ? "?" : "&") . BigPipe::$nojsKey . "=1";
        //
        //echo "<noscript>";
        //echo "<meta http-equiv=\"refresh\" content=\"0; URL=$uri\" />";
        //echo "</noscript>";
    } // }}}
    
    /**
     * outputLayout 输出布局 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputLayout($context)
    {
        echo $this->layoutHTML;
    } // }}}
    
    /**
     * setCurrentPriority 设置当前显示的优先级 {{{
     * 
     * @access protected
     * @return void
     */
    protected function setCurrentPriority()
    {
        $this->currentPriority=array_pop($this->priorityList);
    } // }}}
    
	  /**
     * outputLayoutScript 输出布局所用到的JS {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
	protected function outputLayoutPagelet($context)
    {
		$context->scriptLinks = array_merge($context->scriptLinks,$this->layoutScriptLinks);
		foreach($this->layoutScripts as $key=>$script)
		{
			if(!isset($context->scripts[$key]))
				$context->scripts[$key] = $script;
			else
				$context->scripts[$key] = array_merge($script,$context->scripts[$key]);
		}
		
		$config = $this->getConfig($context);
		$this->outputPageletArrive($config);
	}

    /**
     * outputLayoutStyle 输出布局所用到的Css {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputLayoutStyle($context)
    {
        $this->layoutStyleLinks=array_merge($context->styleLinks, $this->layoutStyleLinks);
        $this->layoutStyles=array_merge($context->styles, $this->layoutStyles);
        
        $links=Resource::pathToResource($this->layoutStyleLinks);
        $links=Resource::getDependResource($links);
	    $this->loadedResource = $links;
        $links=Resource::resourceToURL($links);

		if(count($links) < 10){
			foreach($links as $link){
				echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$link\" />";
			}
		}else{
			$links = array_chunk($links, 31);
			foreach($links as $set) {
				echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"/rsrc.php?type=css";
				foreach($set as $item){
					//echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$link\" />";
					echo "&url[]=$item";
				}
				echo "\" />";
			}
		}
        
        if(!empty($this->layoutStyles)) {
            echo "<style type=\"text/css\">";
            foreach($this->layoutStyles as $style) {
                echo $style, "\n";
            }
            echo "</style>";
        }
    } // }}}
    
    /**
     * outputBigPipeLibrary 输出框架代码 {{{
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function outputBigPipeLibrary($context)
    {
        $jsLibs=Resource::pathToResource(array(
            BigPipe::$jsLib
        ));
        $jsLibs=Resource::getDependResource($jsLibs);
	    $this->loadedResource = array_merge($jsLibs, $this->loadedResource);
        $jsLibs=Resource::resourceToURL($jsLibs);
        
        foreach($jsLibs as $src) {
            echo "<script src=\"$src\"></script>";
        }
        //echo "<script>var ", BigPipe::$globalVar, "=new (require(\"BigPipe\"))();</script>\n";
        echo "<script>BigPipe.init(" . json_encode(array(
            "ajaxKey"=>BigPipe::$ajaxKey,
            "sessionKey"=>BigPipe::$sessionKey,
            "separator"=>BigPipe::$separator
        )) . ");</script>\n";
    } // }}}
    
	protected function outputLoadedResource($context)
	{
	    $loadedResource  = json_encode(array_keys($this->loadedResource));
		echo "<script>BigPipe.loadedResource(".$loadedResource.");</script>\n";
	}

    /**
     * pageletOpen 
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function pageletOpen($context)
    {
        $ret=false;
        if($context->parent->opened) {
            $this->outputPageletOpenTag($context);
        }
        if($this->currentPriority===$context->getPriority()) {
            /*
            $format = $context->getBigPipeConfig("format", "comment");
            $context->set(self::HTML_OUTPUT_FORMAT, $format);
            switch($format){
            case "json":
            ob_start();
            break;
            case "comment":
            default:
            $id=$context->set(self::HTML_CONTAINER_ID, $this->sessionUniqId("__cnt_"));
            echo "<code id=\"$id\" style=\"display:none\"><!--";
            break;
            }
            */
            ob_start();
            $ret=true;
        }
        return $ret;
    }
    
	protected function getConfig($context)
	{
		$config=array(
			"id"=>$context->getConfig("id")
		);
		
		$parent=$context->parent->getConfig("id");
		if(!empty($parent)) {
			$config["parent"]=$parent;
		}
		
		$childrenMap=$context->children;
		if(!empty($childrenMap)) {
			$children=array();
			foreach($childrenMap as $child) {
				if($child->type==BigPipe::PAGELET)
					$children[]=$child->getConfig("id");
			}
			if(!empty($children)) {
				$config["children"]=$children;
			}
		}
		
		
		
		$css = array();
		if(!empty($context->styleLinks)) {
			$css=Resource::pathToResource($context->styleLinks);
		}

		$js = array();
		if(!empty($context->scriptLinks)) {
			$js=Resource::pathToResource($context->scriptLinks);
		}

		$hook = array();
		if(!empty($context->scripts)) {
			$scripts=$context->scripts;
			$config["hook"]=$scripts;
			foreach($scripts as $on=>$list)
			{
				foreach($list as $script)
				{
					$path = "/.".sha1($script).".js";
					$res = Resource::getResource($path, $script);
					$hook[$res->getId()] = $res;
				}
			}
		}

		$resources=Resource::pathToResource(array_merge($context->styleLinks, $context->scriptLinks));
		$resources = array_merge($resources, $hook);
		$resourceMap=Resource::getDependResource($resources, $this->resourceMap, $modules, $asyncs);
		$resourceMap=array_merge($resourceMap, $asyncs);
		if(!empty($modules)) {
			$mods=array();
			foreach($modules as $name=>$res) {
				$id=$res->getId();
				if(!isset($moduleNameMap[$name])) {
					$moduleNameMap[$name]=$id;
					$mods[$name]=$id;
				}
			}
			$config["mods"]=$mods;
		}
		
		if(!empty($resourceMap)) {
			$map=array();
			$async=array();
			foreach($resourceMap as $id=>$res) {
				$deps=$res->getDepends();
				if(isset($hook[$id])){
					$js = array_merge($js, $deps);
				}else{
					$item=array(
						'src'=>$res->getURL(),
						'type'=>$res->getType()
					);
					if(!empty($deps)) {
						$item['deps']=array_keys($deps);
					}
					$map[$id]=$item;
				}
				//$async = $res->getAsyncModule();
			}
			$config["map"]=$map;
		}
		
		if(!empty($css)) {
			$config["css"]=array_keys($css);
		}
		
		if(!empty($js)) {
			$config["js"]=array_keys($js);
		}
		return $config;
	}
    
    protected function outputResources($context)
    {
		$content=ob_get_clean();
		$content=str_replace(array(
			"\\",
			"-->"
			), array(
			"\\\\",
			"--\\>"
			), $content);
		
		$containerId=$this->sessionUniqId("__cnt_");
		$config = $this->getConfig($context);
		$config["container_id"] = $containerId;
		echo "<code id=\"$containerId\" style=\"display:none\"><!-- ";
		echo $content;
		echo " --></code>";
		return $config;
	}
    
	private function outputPageletArrive($config)
	{
		echo "<script>\"use strict\";\n";
		if(isset($config["hook"]))
		{
			$scripts = $config["hook"];
			$id = $config["id"];
			$callback = array();
			foreach($scripts as $on=>$list)
			{
				foreach($list as $index=>$script)
				{
					$functionKey = $this->sessionUniqId("__cb_");
					echo "BigPipe.hooks[\"" , $functionKey, "\"]=function(pagelet){", $script, "};\n";
					$callback[$on][] = $functionKey;
				}
			}
			unset($config["hook"]);
			$config["callback"] = $callback;
		}
		echo "BigPipe.onPageletArrive(", json_encode($config), ");</script>\n";
	}
    
    /**
     * pageletClose 
     * 
     * @param mixed $context 
     * @access protected
     * @return void
     */
    protected function pageletClose($context)
    {
        if($context->opened) {
			$config = $this->outputResources($context);
			$this->outputPageletArrive($config);
        }
        if($context->parent->opened) {
            $this->outputCloseTag($context);
        }
    }
    
    /**
     * changeState 改变状态 {{{ 
     * 
     * @access protected
     * @return void
     */
    protected function changeState()
    {
        switch($this->state) {
        case self::STAT_COLLECT_LAYOUT:
            $this->state=self::STAT_OUTPUT_LAYOUT;
            break;
        case self::STAT_OUTPUT_LAYOUT:
            $this->state=self::STAT_OUTPUT_CONTENT;
            break;
        case self::STAT_OUTPUT_CONTENT:
            if(empty($this->priorityList))
                $this->state=self::STAT_OUTPUT_END;
            break;
        case self::STAT_OUTPUT_END:
            break;
        default:
            break;
        }
    } // }}}
    
    /**
     * getActionKey 得到需要执行的动作索引 {{{ 
     * 
     * @param mixed $context 
     * @param mixed $action 
     * @access protected
     * @return void
     */
    protected function getActionKey($type, $action)
    {
        $keys=array();
        switch($this->state) {
        case self::STAT_COLLECT_LAYOUT:
            $keys[]="collect";
            break;
        case self::STAT_OUTPUT_LAYOUT:
            $keys[]="layout";
            break;
        case self::STAT_OUTPUT_CONTENT:
            $keys[]="content";
            break;
        case self::STAT_OUTPUT_END:
            $keys[]="end";
            break;
        default:
        }
        
        switch($type) {
        case BigPipe::HTML:
            $keys[]="html";
            break;
        case BigPipe::HEAD:
            $keys[]="head";
            break;
        case BigPipe::TITLE:
            $keys[]="title";
            break;
        case BigPipe::BODY:
            $keys[]="body";
            break;
        case BigPipe::PAGELET:
            $keys[]="pagelet";
            break;
        default:
        }
        
        switch($action) {
        case PageController::ACTION_OPEN:
            $keys[]="open";
            break;
        case PageController::ACTION_CLOSE:
            $keys[]="close";
            break;
        case PageController::ACTION_MORE:
            $keys[]="more";
            break;
        default:
        }
        
        $key=join("_", $keys);
        if(!isset($this->actionChain[$key])) {
            $key='default';
        }
        return $key;
    } // }}}
    
    /**
     * sessionUniqId 得到本次会话中唯一ID {{{ 
     * 
     * @param string $prefix 可选前缀 
     * @access private
     * @return string
     */
    public function sessionUniqId($prefix="")
    {
        if(!isset($this->uniqIds[$prefix])) {
            $this->uniqIds[$prefix]=0;
        }
        $this->uniqIds[$prefix]++;
        return $prefix . $this->sessionId . "_" . $this->uniqIds[$prefix];
        //sessionKey
    } // }}}
    
}

// vim600: sw=4 ts=4 fdm=marker syn=php

