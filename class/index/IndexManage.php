<?php
namespace org\opencomb\extensionstore\index;

use org\opencomb\coresystem\auth\PurviewQuery;

use org\opencomb\extensionstore\OpenCMS;

use org\opencomb\platform\system\PlatformFactory;
use org\opencomb\platform\service\Service;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\system\Application;

class IndexManage extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'首页管理',
			'view'=>array(
				'template' => 'IndexManage.html',
				'class' => 'form',
				'model' => 'categoryTree',
			),
			'model:categoryTree'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'limit'=>-1,
					'table'=>'category',
					'name'=>'category',
				)
			),
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
						'name' => 'purview:admin_category',
						'target'=>PurviewQuery::all
				) ,
			) ,
		);
	}
	
	public function process()
	{
		$this->checkPermissions('您没有这个功能的权限,无法继续浏览',array()) ;
		//准备分类信息
		$this->categoryTree->load();
		Category::buildTree($this->categoryTree);
		
		$aSetting = Application::singleton()->extensions()->extension('extensionstore')->setting() ;
		$arrTopLists = $aSetting->item('/index/toplist','toplist',array()) ;
		
		$this->view->variables()->set('arrTopLists',$arrTopLists) ;
		
		$this->doActions();
	}
	
	public function actionSubmit()
	{
		$arrTopLists = array();
		foreach( $this->params->get('cat') as $sCid => $arrTopList){
			if(isset($arrTopList['index_new']) || isset($arrTopList['index_hot'])){
				$arrTopLists[ (int)$sCid ] = $arrTopList;
			}
		}
		$aSetting = Application::singleton()->extensions()->extension('extensionstore')->setting() ;
		$aSetting->setItem('/index/toplist','toplist',$arrTopLists) ;
			
		$this->view->variables()->set('arrTopLists',$arrTopLists) ;
			
		$this->messageQueue ()->create(Message::success,"首页设置保存成功");
	}
}