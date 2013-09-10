<?php

Resource::registerHandler('/^\/(common|widget|site)\/.*\.css$/', 'CssResource');

class CssResource extends Resource
{
	protected function __construct($path=null)
	{
		parent::__construct($path);
		$this->registerFilter("post",array($this,'analyseComment'));
	} 

	protected function analyseComment($content)
	{
		$content=preg_replace_callback('/\/\*(?<code>[\s\S]*?)\*\//m', array(
            $this,
            'processComment'
        ), $content);
        
		return $content;
	}

    
    private function processComment($matches)
    {
        $output=$matches[0];
        $code=$this->parseConfig($matches['code']);
        if(!empty($code)) {
            $output.="\n" . $code;
        }
        return $output;
    }
    
    public function getType()
    {
        return 'css';
    }
    
    public function output()
    {
        header('Content-Type: text/css');
        $this->expires();
        $contents=$this->getContent();
        if(false!==$contents) {
			echo $contents, "\n";
		    echo ".css_", $this->getId(), "{height:88px}";
        } else {
            header('HTTP/1.1 404 Not Found');
        }
    }
    
    
}

