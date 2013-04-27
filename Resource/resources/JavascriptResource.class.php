<?php

Resource::registerHandler('/^.*\.js$/', 'JavascriptResource');
class JavascriptResource extends Resource
{
    protected function genContent()
    {
        $content=parent::genContent();
        $content=preg_replace_callback(array(
            '/\/\*(?<code>[\s\S]*?)\*\//m',
            '/\/\/(?<code>.*)/'
        ), array(
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
        return 'js';
    }
    
    public function output()
    {
        header('Content-Type: text/javascript');
        $this->expires();
        $contents=$this->getContent();
        if(false!==$contents) {
            echo $contents, "\n";
        } else {
            header('HTTP/1.1 404 Not Found');
        }
    }
    
}

