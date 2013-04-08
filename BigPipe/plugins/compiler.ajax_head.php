<?php
/** 
 *           File:  compiler.ajax_head.php
 *           Path:  src/libs/Ajax/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2012-07-20 23:30:21  
 *    Description:  页面头部标记
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_ajax_head($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(BigPipe::open(' . BigPipe::HEAD . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_ajax_headclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::HEAD . ')){'.
'?>';
}
