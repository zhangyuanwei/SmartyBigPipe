<?php
/** 
 *           File:  compiler.html.php
 *           Path:  BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-10 18:13:50  
 *    Description:  页面Ajax输出域 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_html($params,  $smarty){
	$bigpipe_params = array();
	foreach($params as $key=>$value){
		if(strpos($key, BigPipe::ATTR_PREFIX) === 0){
			$bigpipe_params[$key] = $value;
			unset($params[$key]);
		}
	}
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'if(BigPipe::init(' . BigPipe::compileParamsArray($bigpipe_params) . ')){'.
	'do{'.
		'if(BigPipe::open(' . BigPipe::HTML . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_htmlclose($params,  $smarty){
	return 
'<?php '.
		'}'.
		'BigPipe::close(' . BigPipe::HTML . ');'.
	'}while(BigPipe::more());'.
'}'.
'?>';
}
