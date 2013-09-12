<?php

define("BIGPIPE_SMARTY_BASE_DIR", dirname(__FILE__));
define("LIB_BIGPIPE_DIR", BIGPIPE_SMARTY_BASE_DIR . DIRECTORY_SEPARATOR . "BigPipe");
define("LIB_SMARTY_DIR", BIGPIPE_SMARTY_BASE_DIR . DIRECTORY_SEPARATOR . "Smarty");
define("LIB_RESOURCE_DIR", BIGPIPE_SMARTY_BASE_DIR . DIRECTORY_SEPARATOR . "Resource");

require(LIB_SMARTY_DIR . DIRECTORY_SEPARATOR . "libs/Smarty.class.php");

class BigPipeSmarty extends Smarty
{
    public function __construct()
    {
        parent::__construct();
        $this->setupBigPipe();
        $this->setupResource(BIGPIPE_SMARTY_BASE_DIR);
    }
    
    private function setupBigPipe()
    {
        if(!class_exists("BigPipe", false)) {
            require(LIB_BIGPIPE_DIR . "/BigPipe.class.php");
		}
        $this->addPluginsDir(LIB_BIGPIPE_DIR . '/plugins');
    }
    
    public function setupResource($resourceDir, $urlGenerator = null)
    {
        if(!class_exists("Resource", false)) {
            require(LIB_RESOURCE_DIR . "/Resource.class.php");
        }
        Resource::setRootDir($resourceDir);
		Resource::setURLGenerator($urlGenerator);
    }
}

