<?php

Resource::registerHandler('/^.*\.css$/', 'CssResource');

class CssResource extends Resource
{
    protected function genContent()
    {
        $content=parent::genContent();
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
		    echo "#css_", $this->getId(), "{height:88px}";
        } else {
            header('HTTP/1.1 404 Not Found');
        }
    }
    
    
}

