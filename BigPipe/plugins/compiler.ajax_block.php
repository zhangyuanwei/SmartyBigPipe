<?php
/** 
 *           File:  compiler.ajax_area.php
 *           Path:  ~/public_html/hao123/libs/BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-03-28 17:43:38  
 *    Description:  页面分块输出基本单位 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_ajax_block($params,  $smarty){
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
'if(BigPipe::open(' . BigPipe::BLOCK . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ')){'.
'?>';
}

function smarty_compiler_ajax_blockclose($params,  $smarty){
	return 
'<?php '.
'}'.
'if(BigPipe::close(' . BigPipe::BLOCK . ')){'.
'?>';
}
