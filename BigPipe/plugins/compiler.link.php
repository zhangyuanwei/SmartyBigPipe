<?php
/** 
 *           File:  compiler.link.php
 *           Path:  BigPipe/plugins
 *         Author:  zhangyuanwei
 *       Modifier:  zhangyuanwei
 *       Modified:  2013-04-11 11:30:19  
 *    Description:  页面分块输出基本单位 
 *      Copyright:  (c) 2011 All Rights Reserved
 */
function smarty_compiler_link($params,  $smarty){
	$uniqid = BigPipe::compileUniqid();
	return
'<?php '.
'BigPipe::tag(' . BigPipe::LINK . ',BigPipe::has(' . $uniqid . ')?null:' . BigPipe::compileParamsArray($params) . ',' . $uniqid . ');'.
'?>';
}
