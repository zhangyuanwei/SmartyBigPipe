<?php

Resource::registerHandler('/^.*\.js$/', 'JavascriptResource');
//Resource::registerHandler('!^/libs/BigPipe/facebook/output/.*\.js$!', 'JavascriptResource');
//Resource::registerHandler('!^/viz/.*\.js$!', 'JavascriptResource');
//Resource::registerHandler('!^/common/js/jquery-1.7.2.js$!', 'JavascriptResource');
class JavascriptResource extends Resource
{
	protected function genDepends(){
        $content=$this->getContent();
        if(preg_match_all('/\/\*(?<code>[\s\S]*?)\*\//m', $content, $matches, PREG_SET_ORDER)) {
            foreach($matches as $item) {
                $this->parseConfig($item["code"]);
            }
        }
	}
	
	public function getType(){
		return 'js';
	}
}

