<?php
namespace org\opencomb\extensionstore\extension;


use org\opencomb\coresystem\mvc\controller\Controller;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;

class ExtensionList extends Controller
{
	public function createBeanConfig()
	{
		$arrBean = array(
			'title'=>'扩展列表',
			'view'=>array(
				'template'=>'ExtensionList.html',
				'class'=>'view',
				'model'=>'extensions',
				'widget:paginator' => array(
					'class' => 'paginator' ,
				) ,
			),
			'model:category'=>array(
				'orm'=>array(
					'columns' => array('title','lft','rgt') ,
					'table'=>'category',
				)
			),
			'model:extensions'=>array(
				'list'=>true,
				'orm'=>array(
					'table'=>'extension',
					'limit'=>20,
					'orderDesc'=>'createTime',
					'hasOne:category'=>array(
						'fromkeys'=>'cid',
						'tokeys'=>'cid',
						'columns' => array('title') ,
						'table'=>'category',
					) ,
				)
			)
		);
		
		//页面显示结果数,默认20
		if($this->params->get("limit")){
			$arrBean['model:extensions']['orm']['limit'] = (int)$this->params->get("limit");
		}
		
		if($this->params->get('order') == 'asc')
		{
			unset($arrBean['model:extensions']['orm']['orderDesc']);
			$arrBean['model:extensions']['orm']['orderBy'] = 'createTime';
		}
		
		return $arrBean;
	}
	
	public function process()
	{
		if($this->params->has("cid")){
			//准备分类信息
			if(!$this->category->load(array($this->params->get("cid")),array('cid'))){
				$this->messageQueue ()->create ( Message::error, "无效的分类编号" );
			}
			
			$this->setTitle($this->category->title . " - " . $this->title());
			
			$this->extensions->loadSql(
					"category.lft>=@1 and category.lft<=@2 and category.rgt>=@3 and category.rgt<=@4"
					,$this->category->lft
					,$this->category->rgt
					,$this->category->lft
					,$this->category->rgt
			) ;
			
			//把cid传给frame
			$this->params()->set('cid',$this->params->get("cid"));
			
		}else{
			$this->messageQueue ()->create ( Message::error, "未指定分类" );
		}
		
	}
	
	public function defaultFrameConfig()
	{
		return array('class'=>'org\\opencomb\\extensionstore\\frame\\ExtensionFrontFrame') ;
	}
}