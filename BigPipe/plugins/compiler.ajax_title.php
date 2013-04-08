<?php
/** 
 *           File:  compiler.ajax_script.php
 *           Path:  ~/workspace/ting/project/tool/libs/xAjax/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2011-09-08 11:09:47  
 *    Description:  页面分块输出基本单位 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_ajax_title($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(BigPipe::open(' . BigPipe::TITLE . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_ajax_titleclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::TITLE . ')){'.
'?>';
}
