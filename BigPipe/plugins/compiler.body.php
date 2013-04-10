<?php
/** 
 *           File:  compiler.body.php
 *           Path:  ~/public_html/hao123/libs/BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-10 18:13:45  
 *    Description:  页面body标记
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_body($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(BigPipe::open(' . BigPipe::BODY . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_bodyclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::BODY . ')){'.
'?>';
}
