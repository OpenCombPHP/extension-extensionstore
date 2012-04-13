<?php
namespace org\opencomb\extensionstore\category;

use org\opencomb\coresystem\auth\PurviewQuery;

use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class CreateCategory extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'新建分类',  //视图的名称
			'view'=>array( //view的name属性,格式是view:(name属性)
				'template'=>'CategoryForm.html',
				'class'=>'form',    //指定初始化view对象的类,这里使用Formview类,form是Formview的别名
				'model'=>'category', //绑定model
				'widgets'=>array(
					array(
						'config'=>'widget/category_title'  //引用外部的bean配置文件,以便重复使用相同的配置
					),
					array(
						'config'=>'widget/category_dec'
					),
					array(
						'id'=>'category_parent',
						'class'=>'select',    //select widget的bean配置
						'title'=>'分类关系',
						/*
						'options'=>array(     //options 配置方法
						  	array('0','请选择..',true),
						     array('value1','text1',false),
						     array('value2','text2',false),
						     array('value3','text3',false)
						)
						*/
					)
				)
			),
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
						'name' => 'purview:admin_category',
						'target' => PurviewQuery::all,
				) ,
			) ,
			'model:categoryTree'=>array(   //model的bean,model的name是categoryTree
				'config'=>'model/categoryTree',
			),
			'model:category'=>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'category',
				)
			)
		);
	}
	
	public function process()
	{
		$this->checkPermissions('您没有新建分类的权限,无法继续浏览',array()) ;
		//准备分类信息
		$this->categoryTree->load();
		
		Category::buildTree($this->categoryTree);
		
		$aCatSelectWidget = $this->view->widget("category_parent");
		$aCatSelectWidget->addOption("顶级分类",null,true);
		foreach($this->categoryTree->childIterator() as $aCat)
		{
			$bSelect = $aCat->rgt == $this->params->get('target') ? true : false;
			$aCatSelectWidget->addOption(str_repeat("--", Category::depth($aCat)).$aCat->title,$aCat->cid.":".$aCat->rgt,$bSelect);
		}
		
		$this->view->variables()->set('sPageTitle','新建类型') ;
		
		$this->doActions();
	}
	
	public function actionSubmit()
	{
		//加载所有控件的值
		if (! $this->view->loadWidgets ( $this->params ) )
		{
			return;
		}
		$this->view->exchangeData ( DataExchanger::WIDGET_TO_MODEL );
		if ($this->category->save ())
		{
			$target = explode(":",$this->view->widget("category_parent")->value());
			$aCategory = new Category($this->category);
			if(count($target) == 1){
				//添加顶级类型
				$aCategory->insertCategoryToPoint();
			}else{
				//添加子类型
				$aCategory->insertCategoryToPoint((int)$target[1]);
			}
			// 					$this->view->hideForm ();
			$this->messageQueue ()->create ( Message::success, "类型保存成功" );
		}
		else
		{
			$this->messageQueue ()->create ( Message::error, "类型保存失败" );
		}
	}
}