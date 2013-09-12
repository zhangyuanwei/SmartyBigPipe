<?php
require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'common.php');
$smarty = getSmarty();
$smarty->display("index.tpl");
