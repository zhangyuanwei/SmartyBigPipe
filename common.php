<?php

define("WEB_ROOT", dirname(__FILE__));

function getSmarty()
{
    if(!class_exists("BigPipeSmarty", false)) {
        require(WEB_ROOT . "/libs/BigPipeSmarty.class.php");
    }
    $smarty=new BigPipeSmarty();
    $smarty->setupResource(WEB_ROOT, '__path2url');
    return $smarty;
}

function __path2url($path)
{
    return 'rsrc.php?uri=' . urlencode($path);
}


function getResource($path)
{
    if(!class_exists("Resource", false)) {
        require(WEB_ROOT . "/libs/Resource/Resource.class.php");
        Resource::setRootDir(WEB_ROOT);
    }
    return Resource::getResource($path);
}

