<?php
/** 
 *           File:  NoScriptController.class.php
 *           Path:  ~/public_html/hao123/libs/BigPipe
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-03-28 18:52:53  
 *    Description:  没有Js时的控制器
 */
class NoScriptController extends PageController
{
    const STAT_COLLECT=1;
    const STAT_OUTPUT=2;
    
	private $state=self::STAT_COLLECT;
	private $bodyHTML=null;
    
    public function __construct()
    {
		$this->actionChain=array(
			'default' => false,
			// 收集
			'collect_body_open'  => array('startCollect', true),
			'collect_block_open' => array('outputOpenTag', true),
			'collect_body_close' => array('collectBody'),
			'collect_more'       => array('changeState', true),
			// 输出
			'output_html_open'   => array('outputOpenTag', true),
			'output_head_open'   => array('outputOpenTag', 'outputScriptReload', true),
			'output_title_open'   => array('outputOpenTag', true),
			'output_title_close'  => array('outputCloseTag'),
			'output_head_close'  => array('outputCloseTag'),
			'output_body_open'   => array('outputOpenTag', 'outputBody',false),
			'output_body_close'  => array('outputCloseTag'),
			'output_html_close'  => array('outputCloseTag'),
			'output_more'        => false,
		);
    }

	protected function collectBody($context){
		$this->bodyHTML = ob_get_clean();
	}

	protected function outputScriptReload($context){
		echo '<script type="text/javascript">';
		echo '(function(d,l,r){';
		//echo 'd.cookie="', BigPipe::$nojsKey, '=0;expires="+(new Date(new Date-1).toGMTString());';
		echo 'd.cookie="', BigPipe::$nojsKey, '=0;expires="+(new Date(0).toGMTString());';
		echo 'l[r](d.URL[r](/^(.*?)(([?&])', BigPipe::$nojsKey, '=[^&]*(&|$))(.*)/,"$1$3$4$5"));';
		echo '})(document,location,"replace")';
		echo '</script>';
	}

	protected function outputBody($context){
		echo $this->bodyHTML;
	}

	protected function changeState(){
		switch($this->state){
		case self::STAT_COLLECT:
			$this->state=self::STAT_OUTPUT;
			break;
		case self::STAT_OUTPUT:
			break;
		default:
			break;
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
        case self::STAT_COLLECT:
            $keys[]="collect";
            break;
        case self::STAT_OUTPUT:
            $keys[]="output";
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
        
        $key=join("_", $keys);
        if(!isset($this->actionChain[$key])) {
            $key='default';
        }
        return $key;
    } // }}}
}

// vim600: sw=4 ts=4 fdm=marker syn=php

