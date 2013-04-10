<?php
/** 
 *           File:  compiler.head.php
 *           Path:  ~/public_html/hao123/libs/BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-10 18:14:00  
 *    Description:  页面头部标记
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_head($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(BigPipe::open(' . BigPipe::HEAD . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_headclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::HEAD . ')){'.
'?>';
}
