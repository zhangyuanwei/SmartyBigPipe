<?php

Resource::registerHandler('/^.*\.css$/', 'CssResource');
//Resource::registerHandler('!^/libs/BigPipe/.*\.css$!', 'CssResource');

class CssResource extends Resource
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
		return 'css';
	}
}

