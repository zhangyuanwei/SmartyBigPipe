<?php
require(BIGPIPE_BASE_DIR . 'FirstController.class.php');
class QuicklingController extends FirstController
{
	const STAT_SCAN=1; // 扫描，
	const STAT_OUTPUT=2; // 输出内容
	const STAT_END=3;     // 结束

	private $state=self::STAT_SCAN; //初始状态
	private $request_framents = null; //异步刷新模块
	
	public function __construct($sessionId, $request_framents)
	{
		$this->sessionId = $sessionId;
		$this->request_framents = $request_framents;
		$this->actionChain=array(
			'default' => false,
			// 扫描
			'scan_pagelet_open' => array("setPageletPriority","setPageletShow",false),
			'scan_body_close' => array("handdlePriority"),
			'scan_more'=>  array('changeState', true),
			'scan_html_open' =>array("outputHtmlTag",true),
			'scan_body_open' =>array("outputHtmlTag","outputPageletHanddler","sessionStart",false),
			// 输出
			'output_body_open'   => array("setCurrentPriority",false),
			'output_pagelet_open' => array("pageletOpen"),
			'output_pagelet_close'=> array("pageletClose"),
			'output_more'         => array("changeState", true),
			//结束
			'end_body_close'       => array("sessionEnd","outputCloseTag"),
			'end_html_close'       => array("outputCloseTag"),
			'end_more'             => false,
			);
	}

	protected function outputHtmlTag($context)
	{
		echo $context->getOpenHTML(false);
	}

	protected function sessionStart($context)
	{
		echo "<script>parent.BigPipe.sessionStart(", json_encode($this->sessionId), ");</script>\n";
	}

	protected function sessionEnd($context)
	{
		echo "<script>parent.BigPipe.sessionEnd(", json_encode($this->sessionId), ");</script>\n";
	}

	/**
	* pageletOpen 
	* 
	* @param mixed $context 
	* @access protected
	* @return void
	*/
	protected function pageletOpen($context){
		$ret = false;
		if($context->parent->opened) {
			$this->outputPageletOpenTag($context);
		}
		if($context->shouldShow && $this->currentPriority===$context->getPriority()) {
			ob_start();
			$ret = true;
		}
		return $ret;
	}

	/**
	* setCurrentPriority 设置当前显示的优先级 {{{
    * 
	* @access protected
	* @return void
	*/
	protected function setCurrentPriority(){
		$this->currentPriority=array_pop($this->priorityList);
	}


	/**
	* pageletClose 
	* 
	* @param mixed $context 
	* @access protected
	* @return void
	*/
	protected function pageletClose($context){
		if($context->opened) {
			$config = $this->outputResources($context);
			$this->outputPageletArrive($config);
		}

		if($context->parent->opened) {
			$this->outputCloseTag($context);
		}
	}

	private function outputPageletArrive($config)
	{
		echo "<script>PageletArrive(",json_encode($config), ");</script>\n";
	}
	

	protected function outputPageletHanddler($context)
	{
		echo "\n<script>function PageletArrive(config){var obj = config;obj.doc=document;parent.BigPipe.onPageletArrive(obj)}</script>\n";
	}


	/**
	* collectLayout 收集body布局,保存优先级 {{{ 
	* 
	* @param mixed $context 
	* @access protected
	* @return void
	*/
	protected function handdlePriority($context){
		$this->priorityList=BigPipeContext::uniquePriority();
	}

	protected function setPageletShow($context)
	{
		if($context->parent->shouldShow || in_array($context->getConfig("id"),$this->request_framents))
		{
			$context->shouldShow = true;
		}
	}

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
			case self::STAT_SCAN:
				$keys[]="scan";
				break;
			case self::STAT_OUTPUT:
				$keys[]="output";
				break;
			case self::STAT_END:
				$keys[]="end";
				break;
			default:
		}

		switch($type) {
			case BigPipe::HTML:
				$keys[]="html";
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

		$key = join("_", $keys);
		if(!isset($this->actionChain[$key])){
			$key = 'default';
		}
		return $key;
	} 

	/**
	 * changeState 改变状态 {{{ 
	 * 
	 * @access protected
	 * @return void
	 */
	protected function changeState(){
		switch($this->state){
		case self::STAT_SCAN:
			$this->state=self::STAT_OUTPUT;
			break;
		case self::STAT_OUTPUT:
			if(empty($this->priorityList))
				$this->state = self::STAT_END;
			break;
		case self::STAT_END:
			break;
		default:
			break;
		}
	} 
}

?>
