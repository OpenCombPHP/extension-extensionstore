<?php
namespace org\opencomb\extensionstore\menu;

use org\opencomb\coresystem\auth\PurviewQuery;

use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\system\Application;

class MenuManage extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'菜单管理',
			'view'=>array(
				'template' => 'MenuManage.html',
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
		$arrMenus = $aSetting->item('/menu/mainmenu','mainmenu',array()) ;
		
		$this->view->variables()->set('arrMenus',$arrMenus) ;
		
		$this->doActions();
	}
	
	public function actionSubmit()
	{
		$arrMenus = array();
		foreach( $this->params->get('cat') as $sCid => $arrMenu){
			if(isset($arrMenu['mainmenu'])){
				$arrMenus[ 'item:'.(int)$sCid ] = array(
						'title'=>$this->categoryTree->findChildBy($sCid,"cid")->data('title'),
						'link'=>'?c=org.opencomb.extensionstore.extension.ExtensionList&cid='.$sCid ,
				);
			}
		}
		$aSetting = Application::singleton()->extensions()->extension('extensionstore')->setting() ;
		$aSetting->setItem('/menu/mainmenu','mainmenu',$arrMenus) ;
			
		$this->view->variables()->set('arrMenus',$arrMenus) ;
			
		$this->messageQueue ()->create(Message::success,"菜单列表设置保存成功");
	}
}