<?php
/** 
 *           File:  compiler.title.php
 *           Path:  BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-10 18:14:53  
 *    Description:  页面分块输出基本单位 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_title($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(BigPipe::open(' . BigPipe::TITLE . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_titleclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::TITLE . ')){'.
'?>';
}
