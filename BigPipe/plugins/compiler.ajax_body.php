<?php
/** 
 *           File:  compiler.ajax_body.php
 *           Path:  src/libs/Ajax/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2012-07-20 23:47:49  
 *    Description:  页面body标记
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_ajax_body($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(BigPipe::open(' . BigPipe::BODY . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_ajax_bodyclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::BODY . ')){'.
'?>';
}
