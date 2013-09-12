<?php
/** 
 *           File:  ResourcePackage.class.php
 *           Path:  Resource
 *         Author:  HuangYi
 *    Description: 资源打包处理类 
 */

class ResourcePackage
{
	private $deps = array();//资源包的依赖
	private $id = null;//资源包的ID,唯一标示
	private $res = array();//资源包所包含的资源，手动选择的资源
	private $extends = array();//资源包所包含的所有资源，包括手动选和程序自动选择的
	private static $packageMap = array();//资源包的映射表
	private static $idMap = array();//资源对应资源包id的映射表
	/**
	* __construct 构造函数 {{{ 
	* 
	* @param Resource $res
	* @access protected
	* @return void
	*/
	public function __construct($res,$id=null)
	{
		$this->res = $res;
		$this->id = $id;
		$this->extend();
		if($id!==null)
		{
			$this->setupIdMaps();
			self::$packageMap[$this->id] = $this;
		}
	} 

	public function extend()
	{
		foreach($this->res as $res)
		{
			$this->selectDepends($res);
		}
	}

	public function selectDepends($res)
	{
		$dependSelected = false;
		foreach($res->getDepends() as $dep)
		{
			$dependSelected = $this->selectDepends($dep) || $dependSelected;
		}
		
		if(in_array($res,$this->res) || in_array($res,$this->extends))
		{
			if(!in_array($res,$this->extends))
				$this->extends[] = $res;
			return true;	
		}
		if($dependSelected)
		{
			if(!in_array($res,$this->extends))
				$this->extends[] = $res;
		}
		return $dependSelected;
	}

	/**
	*从资源包里的所有资源得到资源包的依赖资源包
	* 
	* @access private
	* @return void
	*/
	public function getDeps()
	{
		if(!$this->deps)
		{
			foreach($this->extends as $res)
			{
				
				foreach($res->getDepends() as $depends)
				{
					$id = self::$idMap[$depends->getPath()];
					if($id != $this->id && !in_array($id,$this->deps))
						$this->deps[] = $id;
				}	
			}
		}
		return $this->deps;
	}

	private function setupIdMaps()
	{
		foreach($this->extends as $res)
		{
			self::$idMap[$res->getPath()] = $this->id;
		}
	}

	public static function getResourcePackage()
	{
	    return self::$packageMap;
	}

	public static function getIdMap()
	{
		return self::$idMap;
	}

	public static function clearResourcePackage()
	{
		self::$packageMap = array();
	}

	public static function clearIdMap()
	{
		self::$idMap = array();
	}

	/**
	*合并用户选择的资源包
	* 
	* @access private
	* @return void
	*/
	public static function mergePackage($ids)
	{
		$ress = array();
		foreach($ids as $id)
		{
			$package = self::$packageMap[$id];
			foreach($package->getResource() as $res)
			{
				$ress[] = $res;
			}
		}
		return $ress;
	}

	public function getOutputName()
	{
		$name = "";
		foreach($this->extends as $res)
		{
			$name .= $res->getPath()."|";
		}
		return chop($name,"|");
	}

	public function getId()
	{
		return $this->id;
	}

	public function getResource()
	{
		return $this->res;
	}

	public function getExtends()
	{
		return $this->extends;
	}

}


