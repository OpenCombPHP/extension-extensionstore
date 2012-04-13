<?php
return array(
	'item:ExtensionStore' => array(
		'title'=>'扩展商店管理',
		'link'=>'?c=org.opencomb.extensionstore.extension.ExtensionManage',
		'direction'=>'v',
		
		'menu'=> 1 ,
		'item:ExtensionManage' => array(
			'title'=>'扩展管理',
			'link'=>'?c=org.opencomb.extensionstore.extension.ExtensionManage',
			'query'=>array(  //query可以让以下几个页面也带有侧边菜单
				'c=org.opencomb.extensionstore.extension.ExtensionManage' ,
				'c=org.opencomb.extensionstore.extension.CreateExtension' ,
				'c=org.opencomb.extensionstore.extension.EditExtension' ,
			)
		),
		'item:CategoryManage' => array(
			'title'=>'类型管理',
			'link'=>'?c=org.opencomb.extensionstore.category.CategoryManage',
			'query'=> array(
				'c=org.opencomb.extensionstore.category.CategoryManage' ,
				'c=org.opencomb.extensionstore.category.CreateCategory' ,
				'c=org.opencomb.extensionstore.category.EditCategory' ,
				'c=org.opencomb.extensionstore.category.DeleteCategory' ,
			)
		),
		'item:IndexManage' => array(
			'title'=>'首页设置',
			'link'=>'?c=org.opencomb.extensionstore.index.IndexManage',
			'query'=>'c=org.opencomb.extensionstore.index.IndexManage'
		),
		'item:MenuManage' => array(
			'title'=>'菜单设置',
			'link'=>'?c=org.opencomb.extensionstore.menu.MenuManage',
			'query'=>'c=org.opencomb.extensionstore.menu.MenuManage'
		),
	)
);
