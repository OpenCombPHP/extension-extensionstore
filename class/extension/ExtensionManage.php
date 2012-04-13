<?php
namespace org\opencomb\extensionstore\extension;

use org\jecat\framework\auth\IdManager;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class ExtensionManage extends ControlPanel
{
	public function createBeanConfig()
	{
		$arrBean = array(
			'title'=>'扩展管理',
			'view'=>array(
				'template'=>'ExtensionManage.html',
				'class'=>'view',
				'model'=>'extensions',
				'widget:paginator' => array(  //分页器bean
					'class' => 'paginator' ,
					'count' => 10, //每页10项
					'nums' => 5   //显示5个页码
				) ,
			),
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
					'name' => 'purview:admin_category',
				) ,
			) ,
			'model:extensions'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'table'=>'extension',
					'name'=>'extension',
					'belongsTo:category'=>array(
						'fromkeys'=>'cid',
						'tokeys'=>'cid',
						'table'=>'category',
						'name'=>'category',
// 						'where'=>array("category.cid=@1",$this->params->get('cid'))
					)
				)
			),
			'model:categoryTree'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'limit'=>-1,
					'table'=>'category',
					'name'=>'category',
				)
			)
		);
		
		if($this->params->get('cid'))
		{
			$arrBean['model:extensions']['orm']['where'] = array("category.cid=@1",$this->params->get('cid'));
		}
		
// 		var_dump($arrBean);
		return $arrBean;
	}
	
	public function process()
	{
		$this->checkPermissions('您没有这个分类的管理权限,无法继续浏览',array()) ;
		
		//准备分类信息
		$this->categoryTree->load();
		
		Category::buildTree($this->categoryTree);
		$this->view->variables ()->set ( 'aCatIter', $this->categoryTree );
		
		//搜索扩展用的title模糊检索
		if($this->params->get('title'))
		{
			$this->extensions->loadSql("`title` like @1", '%'. $this->params->get('title').'%' );
		}else{
			$this->extensions->load ();
		}
		
// 		DB::singleton()->executeLog();
		
		$this->view->variables()->set('aArtIter',$this->extensions->childIterator()) ;
	}
}