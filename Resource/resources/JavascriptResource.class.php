<?php

Resource::registerHandler('/^.*\.js$/', 'JavascriptResource');

class JavascriptResource extends Resource
{
    protected function genContent()
    {
        if($file=$this->getFilePath()) {
            $content=file_get_contents($file);
            if(preg_match_all('/\/\*(?<code>[\s\S]*?)\*\//m', $content, $matches, PREG_SET_ORDER)) {
                foreach($matches as $item) {
                    $this->parseConfig($item["code"]);
                }
            }
            return $content;
        } else {
            throw new Exception("Js file \"$this->path\" not found.");
        }
    }
}

