<?php
namespace org\opencomb\extensionstore\frame ;

use org\jecat\framework\mvc\controller\WebpageFrame;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\view\View;

class ExtensionFrontFrame extends WebpageFrame
{
	public function createBeanConfig()
	{
		$arrParentBean = parent::createBeanConfig();
		$arrBean =  array(
			'frameview:ExtensionStoreFrameView' => array(
				'template' => 'ExtensionStoreFrame.html' ,
			) ,
			// 控制器类型内最新内容
			'controller:topListNew' => array(
				'class' => 'org\\opencomb\\extensionstore\\extension\\TopList' ,
				'params' => array('orderby'=>'createTime'),
			) ,
			// 控制器类型内最热内容
			'controller:topListHot' => array(
				'class' => 'org\\opencomb\\extensionstore\\extension\\TopList' ,
				'params' => array('orderby'=>'views'),
			) ,
			'model:categoryList' =>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'table'=>'category',
					'name'=>'category',
				)
			),
			'model:category' =>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'category',
					'name'=>'category',
				)
			),
		);
		BeanFactory::mergeConfig( $arrParentBean ,$arrBean );
		return $arrParentBean;
	}
	
	public function process(){
		$this->category->load($this->params->get('cid'),'cid');
		
		$this->categoryList->loadSql("lft < @1 and rgt > @2"  , $this->category->data('lft') ,$this->category->data('rgt'));
		
		$arrBreadcrumbNavigation = array();
		foreach($this->categoryList->childIterator() as $aCat){
			$arrBreadcrumbNavigation[$aCat->title] = "?c=org.opencomb.extensionstore.extension.ExtensionList&cid=".$aCat->cid;
		}
		
		$this->frameView->viewExtensionStoreFrameView->variables()->set('arrBreadcrumbNavigation',$arrBreadcrumbNavigation) ;
	}
}