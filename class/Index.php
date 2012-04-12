<?php 
namespace org\opencomb\extensionstore;

use org\opencomb\coresystem\mvc\controller\Controller;

class Index extends Controller 
{
	public function createBeanConfig()
	{
		$arrBean = array(
				'title'=>'首页',
				'view'=>array(
						'template'=>'Index.html',
						'class'=>'view',
				),
				'model:extensions'=>array(
					'list'=>true,
					'orm'=>array(
						'table'=>'extension',
						'limit'=>20,
						'orderDesc'=>'createTime',
						'hasOne:category'=>array(
							'fromkeys'=>'eid',
							'tokeys'=>'eid',
							'table'=>'category',
					) ,
				)
			)
		);
	}
	
	public function process()
	{
		
	}
}