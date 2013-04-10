<?php
/** 
 *           File:  compiler.script.php
 *           Path:  ~/public_html/hao123/libs/BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-10 18:14:48  
 *    Description:  页面分块输出基本单位 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_script($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return
'<?php '.
'if(BigPipe::open(' . BigPipe::SCRIPT . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_scriptclose($params,  $smarty){
	return
'<?php '.
'}'.
'BigPipe::close(' . BigPipe::SCRIPT . ');'.
'?>';
}
