<?php
namespace org\opencomb\extensionstore\extension;

use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller;

class TopList extends Controller
{
	public function createBeanConfig()
	{
		$arrBean = array(
			'view'=>array(
				'template'=>'TopList.html',
				'class'=>'view',
				'model'=>'extension',
			),
			'model:extension'=>array(
				'list'=>true,
				'orm'=>array(
					//'where'=>array(array('eq','cid',$this->params->get('cid'))),
					'table'=>'extension',
				)
			),
		);
		return $arrBean;
	}
	
	public function process()
	{
		//exit;
		$this->extension->load($this->params->get("cid"),'cid') ;
		//first是第一个数组，将model中所有行集合成一个数组
		$arrModelFirst=array();
		foreach($this->extension->childIterator() as $key=>$aModel)
		{
			$arrModelFirst[$key]=array('eid'=>$aModel->data('eid'),'from'=>$aModel->data('from'),'cid'=>$aModel->data('cid')
								,'title'=>$aModel->data('title'),'description'=>$aModel->data('description')
								,'createtime'=>$aModel->data('createTime'),'version'=>$aModel->data('version')
								,'version_init'=>$aModel->data('version_init'),'author'=>$aModel->data('author')
								,'views'=>$aModel->data('views'),'recommend'=>$aModel->data('recommend')
								,'title_bold'=>$aModel->data('title_bold'),'title_italic'=>$aModel->data('title_italic')
								,'title_strikethrough'=>$aModel->data('title_strikethrough'),'title_color'=>$aModel->data('title_color')
								);
		}
// 		for($i=0;$i<count($arrModelFirst);$i++)
// 		{
// 			$arrModelSecond[]
// 			for($i=0;$i<count($arrModelFirst);$i++)
// 			{
// 				;
// 			}
// 		}
// 		var_dump($arrModelFirst);exit;
		
		if(!$this->params->has("cid")){
			$this->messageQueue ()->create ( Message::error, "未指定分类" );
			return;
		}
		
		//准备分类信息
		if(!$this->category->load(array($this->params->get("cid")),array('cid'))){
			$this->messageQueue ()->create ( Message::error, "无效的分类编号" );
		}
		$this->view->variables()->set('sCategoryTitle',$this->category->data('title')) ;
		$this->view->variables()->set('nCid',$this->params->get("cid")) ;
				
		//遍历范围,仅第一层
		if($this->params->has('subCat') and $this->params->get('subCat') == 1)
		{
			$this->extensions->loadSql("`cid`=@1",$this->params->get('cid')) ;
		}
		
		//遍历范围,所有层
		else
		{
			$this->extensions->loadSql(
				"category.lft>=@1 and category.lft<=@2 and category.rgt>=@3 and category.rgt<=@4"
					,$this->category->lft
					,$this->category->rgt
					,$this->category->lft
					,$this->category->rgt
			) ;
		}
		//$this->extensions->printStruct();exit;
		
	}
}