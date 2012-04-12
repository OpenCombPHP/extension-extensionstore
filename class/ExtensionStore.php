<?php 
namespace org\opencomb\extensionstore;

use org\jecat\framework\system\AccessRouter;
use org\opencomb\platform\ext\Extension ;

class ExtensionStore extends Extension 
{
	/**
	 * 载入扩展
	 */
	public function load()
	{
		$aAccessRouter = AccessRouter::singleton() ;
		//设置首页控制器
		$aAccessRouter->setDefaultController("org\\opencomb\\extensionstore\\Index") ;
	}
}