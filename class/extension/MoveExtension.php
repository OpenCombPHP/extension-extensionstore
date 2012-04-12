<?php
namespace org\opencomb\extensionstore\extension;

use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class MoveExtension extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'转移文章',
			'view'=>array(
				'template'=>'MoveExtensions.html',
				'class'=>'view',
				'model'=>'extensions',
			),
			'model:extensions'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'table'=>'extension',
				)
			)
		);
	}
	
	public function process()
	{

		//权限
		$this->requirePurview('purview:admin_category','extension',$this->params->get('from'),'您没有这个分类的管理权限,无法继续浏览');
		$this->requirePurview('purview:admin_category','extension',$this->params->get('to'),'您没有这个分类的管理权限,无法继续浏览');
		
		if(!$this->params->has('from') || !$this->params->has('to')){
			$this->messageQueue ()->create ( Message::error, "提供的参数不完整" );
			return;
		}
			
		$arrFromCategorys = explode('_', $this->params->get('from'));
		$nToCategory = (int)$this->params->get('to');
		
		if(DB::singleton()->execute("UPDATE `extension_extension` SET  `cid` = '{$nToCategory}' WHERE `cid` in (" . implode(',', $arrFromCategorys) . ");")){
			$this->messageQueue ()->create ( Message::success, "成功转移了文章" );
		}else{
			$this->messageQueue ()->create ( Message::error, "没有转移任何文章,可能是因为没有找到文章或者目标栏目不存在" );
			return;
		}
	}
}
?>