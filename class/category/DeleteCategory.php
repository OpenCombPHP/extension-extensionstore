<?php
namespace org\opencomb\extensionstore\category;

use org\jecat\framework\db\DB;

use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class DeleteCategory extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'删除分类',
			'view'=>array(
				'template'=>'DeleteCategory.html',
				'class'=>'view'
			),
			'model:category'=>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'category',
				)
			),
			'model:extension'=>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'extension'
				)
			),
		);
	}
	
	public function process()
	{
		//权限
		$this->requirePurview('purview:admin_category','extensionstore',$this->params->get('cid'),'您没有这个分类的管理权限,无法继续浏览');
		
		//要删除哪些项?把这些项数组一起删除,如果只有一项,也把也要保证它是数组
		if ($this->params->get ( "cid" ))
		{
			$arrToDelete = explode(',', $this->params->get ( "cid" )); 
			if($arrToDelete === false){
				$this->messageQueue ()->create ( Message::error, "未指定类型" );
			}
			
			foreach($arrToDelete as $nCatIdToDelete){
				$this->delCat($nCatIdToDelete);
			}
			
		}else{
			$this->messageQueue ()->create ( Message::error, "未指定类型" );
		}
		
		$this->location('/?c=org.opencomb.extensionstore.category.CategoryManage');
	}
	
	public function delCat($nCatIdToDelete)
	{
		if ($this->category->load( (int)$nCatIdToDelete , 'cid'))
		{
			//保证正在删除的分类没有扩展
			if($this->extension->load (array($this->category->data('cid')),array('cid'))){
				$this->messageQueue ()->create ( Message::error, "类型中有扩展,请先转移扩展再删除类型" );
				return;
			}
		
			//保证正在删除的分类没有子分类
			if(Category::rightPoint($this->category) - Category::leftPoint($this->category) > 1){
				$this->messageQueue ()->create ( Message::error, "类型中有子类型,请先转移子类型再试" );
				return;
			}
			$aCategory = new Category($this->category);
			$aCategory->delete();
			$this->messageQueue ()->create ( Message::success, "删除类型成功" );
		}
		else
		{
			$this->messageQueue ()->create ( Message::error, "删除类型失败" );
		}
	}
}