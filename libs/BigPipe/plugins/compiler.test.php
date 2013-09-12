<?php
/** 
 *           File:  compiler.test.php
 *           Path:  BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  luis
 *       Modified:  2013-04-10 18:14:43  
 *    Description:  页面抽样插件 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_test($params,  $smarty){
	/*
	$id = 'null';
	if(isset($params['id'])){
		$id = $params['id'];
	}else{
		throw new Exception('missing "id" attribute'); 
	}
	 */
	$uniqid = BigPipe::compileUniqid();
	return 
'<?php '.
'}'.
'if(!BigPipe::test('. BigPipe::compileParamsArray($params) .',$_smarty_tpl,true)){'.
	'if(BigPipe::opened()){'.
		'BigPipe::test(' . BigPipe::compileParamsArray($params) .',$_smarty_tpl);'.
	'}'.
'}else{'.
	'if(BigPipe::opened()){'.
'?>';
}

function smarty_compiler_testclose($params,  $smarty){
	return 
'<?php '.
	'}'.
'}'.
'if(BigPipe::opened()){'.
'?>';
}
