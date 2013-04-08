<?php
/** 
 *           File:  FirstController.class.php
 *           Path:  ~/public_html/hao123/libs/BigPipe
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-07 16:36:51  
 *    Description: 第一次请求页面时的输出控制器 
 *    共分为3个阶段输出:
 *
 *    1.渲染并收集最外层结构(未被ajax_block包裹的内容)并收集使用到的Js和CSS
 *    2.输出html、head、body和最外层结构，并且输出前端库及使用到的CSS和Js资源
 *    3.根据优先级输出各层结构，并输出依赖资源表
 *    4.结束
 */
class FirstController extends PageController
{
	const STAT_COLLECT_LAYOUT=1; // 收集最外层布局
    const STAT_OUTPUT_LAYOUT=2;  // 输出最外层布局
    const STAT_OUTPUT_CONTENT=3; // 输出内容
    const STAT_OUTPUT_END=4;     // 结束
    
    const HTML_CONTAINER_ID="html_container_id"; //HTML收集器id
    
	private $state=self::STAT_COLLECT_LAYOUT; //初始状态
	private $layoutHTML=null;      //布局结构
    private $layoutPriority=null;  //布局的优先级
    private $priorityList=null;    //优先级数组
    private $currentPriority=null; //当前优先级
    private $sessionId=0;          //此次会话ID,用于自动生成不重复id,第一次默认为0
    private $uniqIds=array();      //不重复id种子
    
    /**
     * __construct 构造函数
     * 
     * @access public
     * @return void
     */
    public function __construct(){
		$this->actionChain=array(
			'default'              => false,
			//收集阶段
			'collect_body_open'    => array('startCollect', true),
			'collect_body_close'   => array('collectLayout'),
			'collect_block_open'   => array('outputPlaceHolder', 'setBlockPriority', false),
			'collect_script_open'  => array('startCollect', true),
			'collect_script_close' => array('collectScript'),
			'collect_more'         => array('changeState', true),
			//输出布局阶段
			'layout_html_open'     => array('outputOpenTag', true),
			'layout_head_open'     => array('outputOpenTag', 'outputNoscriptFallback', true),
			'layout_title_open'    => array('outputOpenTag', true),
			'layout_title_close'   => array('outputCloseTag'),
			'layout_head_close'    => array('outputCloseTag'),
			'layout_body_open'     => array('outputOpenTag', 'outputLayout', false),
			'layout_more'          => array('changeState', true),
			//输出内容阶段
			'content_body_open'    => array('setCurrentPriority', false),
			'content_block_open'   => array('blockOpen'),
			'content_block_close'  => array('blockClose'),
			'content_script_open'  => array('startCollect', true),
			'content_script_close' => array('collectScript'),
			'content_more'         => array('changeState', true),
			//输出结束标签
			'end_body_close'       => array('outputCloseTag'),
			'end_html_close'       => array('outputCloseTag'),
			'end_more'             => false,
        );
	}

	/**
	 * collectLayout 收集body布局,保存优先级 {{{ 
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function collectLayout($context){
		$this->layoutHTML=ob_get_clean();
		$this->layoutPriority=$context->priority;
		$this->priorityList=array_filter(BigPipeContext::uniquePriority(), array(
			$this,
			'filterPriority'
		));
	} // }}}

	/**
	 * outputPlaceHolder 输出占位符 {{{
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function outputPlaceHolder($context){
        if($context->parent->opened) { // 如果父级标签处于输出状态，则输出最外层节点
			$this->outputBlockOpenTag($context);
			$this->outputCloseTag($context);
		}
	} // }}}

	protected function outputBlockOpenTag($context){
		if(!isset($context->config["id"]))
			$context->config["id"] = $this->sessionUniqId("__elm_");
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
        $uri=$_SERVER["REQUEST_URI"];
        $uri=$uri . (strpos($uri, "?")===false ? "?" : "&") . BigPipe::$nojsKey . "=1";
        
        echo "<noscript>";
        echo "<meta http-equiv=\"refresh\" content=\"0; URL=$uri\" />";
        echo "</noscript>";
	} // }}}

	/**
	 * outputLayout 输出布局 {{{
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function outputLayout($context){
		echo $this->layoutHTML;
	} // }}}

	/**
	 * setCurrentPriority 设置当前显示的优先级 {{{
	 * 
	 * @access protected
	 * @return void
	 */
	protected function setCurrentPriority(){
		//var_dump($this->priorityList);
		$this->currentPriority=array_pop($this->priorityList);
	} // }}}

	/**
	 * blockOpen 
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function blockOpen($context){
		$ret = false;
		if($context->parent->opened) {
			$this->outputBlockOpenTag($context);
		}
        
		if($this->currentPriority===$context->priority) {
			$id=$context->set(self::HTML_CONTAINER_ID, $this->sessionUniqId("__cnt_"));
			echo "<code id=\"$id\"><!--";
			//echo "<sctipt id=\"$id\" type=\"text/html\">/*<![CDATA[*/";
			$ret = true;
		}
		return $ret;
	}

	/**
	 * blockClose 
	 * 
	 * @param mixed $context 
	 * @access protected
	 * @return void
	 */
	protected function blockClose($context){
		if($context->opened) {
			echo "--></code>\n";
			//echo "/*]]>*/</script>\n";
			$id=$context->get(self::HTML_CONTAINER_ID);
			echo "<script>var a=(a+100)||0;setTimeout(function(){document.getElementById(", json_encode($context->getConfig("id")), ").innerHTML=document.getElementById(\"$id\").childNodes[0].nodeValue;},a);</script>\n";
			//var_dump($context->scripts);
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
	protected function changeState(){
		switch($this->state){
		case self::STAT_COLLECT_LAYOUT:
			$this->state=self::STAT_OUTPUT_LAYOUT;
			break;
		case self::STAT_OUTPUT_LAYOUT:
			$this->state=self::STAT_OUTPUT_CONTENT;
			break;
		case self::STAT_OUTPUT_CONTENT:
			if(empty($this->priorityList))
				$this->state = self::STAT_OUTPUT_END;
			break;
		case self::STAT_OUTPUT_END:
			break;
		default:
			break;
		}
/*
        switch($this->state) {
        case self::STAT_OUTPUT_LAYOUT:
            $this->priority_list=array_filter(BigPipeContext::uniquePriority(), array(
                $this,
                'filterPriority'
            ));
            
            $this->state=empty($this->priority_list) ? self::STAT_OUTPUT_END : self::STAT_OUTPUT_CONTENT;
            return true;
        
        case self::STAT_OUTPUT_CONTENT:
            
            $this->state=empty($this->priority_list) ? self::STAT_OUTPUT_END : self::STAT_OUTPUT_CONTENT;
            $this->current_priority=array_pop($this->priority_list);
            return true;
        
        case self::STAT_OUTPUT_END:
        default:
            return false;
        }
 */
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
	   case BigPipe::BLOCK:
	       $keys[]="block";
	       break;
	   case BigPipe::SCRIPT:
	       $keys[]="script";
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

		$key = join("_", $keys);
		if(!isset($this->actionChain[$key])){
			$key = 'default';
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
	private function sessionUniqId($prefix="") {
		if(!isset($this->uniqIds[$prefix])){
			$this->uniqIds[$prefix] = 0;
		}
		$this->uniqIds[$prefix]++;
        return $prefix . $this->sessionId . "_" . $this->uniqIds[$prefix];
        //sessionKey
    } // }}}

	/**
     * filterPriority {{{
     * 
     * @param mixed $value 
     * @access private
     * @return void
     */
    private function filterPriority($value){
        return $value < $this->layoutPriority;
    } // }}}
}

// vim600: sw=4 ts=4 fdm=marker syn=php

