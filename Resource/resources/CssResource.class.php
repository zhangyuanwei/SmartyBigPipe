<?php

Resource::registerHandler('/^.*\.css$/', 'CssResource');

class CssResource extends Resource
{
    protected function genContent()
    {
        $content=parent::genContent();
        if(preg_match_all('/\/\*(?<code>[\s\S]*?)\*\//m', $content, $matches, PREG_SET_ORDER)) {
            foreach($matches as $item) {
                $this->parseConfig($item["code"]);
            }
        }
        return $content;
    }
}

