<?php

Resource::registerHandler('/^\/(((common|widget|site|libs)\/)|\.).*\.js$/', 'JavascriptResource');
class JavascriptResource extends Resource
{
	private $requireModules = array();
	
	protected function __construct($path=null)
	{
		parent::__construct($path);
		$this->registerFilter("post",array($this,'analyseComment'));
		$this->registerFilter("post",array($this,'analyseRequire'));
	
	} 

	protected function analyseComment($content)
	{
		$content=preg_replace_callback(array(
            '/\/\*(?<code>[\s\S]*?)\*\//m',
            '/\/\/(?<code>.*)/',
        ), array(
            $this,
            'processComment'
        ), $content);
		return $content;
	}

	protected function analyseRequire($content)
	{
		if($this->cmd)
		{
			$content=preg_replace_callback(array(
				'/([^\$\.]|^)\brequire\s*\(\s*(?<name>[^\)]*?)\)/'
			),array(
				$this,
				'processRequire'
			),$content);

			$content=preg_replace_callback(array(
				'/([^\$\.]|^)\b(requireAsync|requireLazy)\s*\(\s*\[(?<depends>[^\]]*?)\]/'
				),array(
				$this,
				'processRequireAsync'
			),$content);
			
			$content = "__d(\"".$this->getModuleName()."\",".json_encode($this->requireModules).",function(global, module, exports, require, requireAsync, requireLazy){".$content."\nreturn exports;});";
		}
		return $content;
	}

	private function processRequire($matches)
	{

		if(preg_match('/^\s*(["\'])(?<name>[^\1]*?)\1\s*$/',$matches["name"],$results))
		{
			$name = $results["name"];
			$this->depend($this->nameToPath($name));
		    $this->requireModules[] = $name;
		}	
		else
		{
			trigger_error("\"" . $this->getPath() .  "\" require format error:" .$matches[0]);
		}	
		return $matches[0];
	}

	private function processRequireAsync($matches)
	{
		$depends = explode(",",$matches["depends"]);
		foreach($depends as $dep)
		{
			if(preg_match('/^\s*(["\'])(?<name>[^\1]*?)\1\s*$/',$dep,$results))
			{
				$name = $results["name"];
				$this->async($name,$this->nameToPath($name));	
			}	
			else
			{
				trigger_error("\"" . $this->getPath() . "\" requireAsync format error:" .$dep);
			}	
		}	
		return $matches[0];
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

	private function getModuleName()
	{
		$path = $this->getPath();
		$len =  strrpos($path,".")-1;
		$path = substr($path,1,$len);
		return str_replace("/",".",$path);
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
			$hookName = json_encode("js_" . $this->getId());
			echo '"use strict";', $contents, "\n".
				"window[$hookName] && window[$hookName](true);";
        } else {
            header('HTTP/1.1 404 Not Found');
        }
    }
    
}

