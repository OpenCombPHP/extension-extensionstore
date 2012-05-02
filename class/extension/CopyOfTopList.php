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
				'model'=>'extensions',
			),
			'model:category'=>array(
				'orm'=>array(
					'columns' => array('title','lft','rgt') ,
					'table'=>'category',
				)
			),
		);
		
		//遍历范围,仅第一层
		if($this->params->has('subCat') and $this->params->get('subCat') == 1){
			$arrBean['model:extensions'] = array(
				'list'=>true,
				'orm'=>array(
					'table'=>'extension',
					'limit'=>20
				)
			);
		}else{  //遍历范围,所有层
			$arrBean['model:extensions'] = array(
				'list'=>true,
				'orm'=>array(
					'table'=>'extension',
					'limit'=>20,
					'hasOne:category'=>array(
						'fromkeys'=>'cid',
						'tokeys'=>'cid',
						'columns' => array('title') ,
						'table'=>'category',
					) ,
				)
			); 
		}
		
		//排序,默认按照时间反序排列
		$sOrder = 'orderDesc';
		$this->setTitle("最新扩展");
		if($this->params->has('order') and $this->params->get('order') == "asc"){
			$sOrder = 'orderAsc';
			$this->setTitle("最热扩展");
		}
		$arrBean['model:extensions']['orm'][$sOrder] = 'createTime' ;
		
		//排序
		if($this->params->has("limit")){
			$arrBean['model:extensions']['orm']['limit'] = $this->params->get("limit");
		}
		
		return $arrBean;
	}
	
	public function process()
	{
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
	}
}