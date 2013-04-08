<?php
/** 
 *           File:  compiler.ajax_html.php
 *           Path:  ~/public_html/www.hao123.com/libs/BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-03-27 15:44:43  
 *    Description:  页面Ajax输出域 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_ajax_html($params,  $smarty){
	$ajax_params = array();
	foreach($params as $key=>$value){
		if(strpos($key, BigPipe::ATTR_PREFIX) === 0){
			$ajax_params[$key] = $value;
			unset($params[$key]);
		}
	}
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'if(BigPipe::init(' . BigPipe::compileParamsArray($ajax_params) . ')){'.
	'do{'.
		'if(BigPipe::open(' . BigPipe::HTML . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_ajax_htmlclose($params,  $smarty){
	return 
'<?php '.
		'}'.
		'BigPipe::close(' . BigPipe::HTML . ');'.
	'}while(BigPipe::more());'.
'}'.
'?>';
}
