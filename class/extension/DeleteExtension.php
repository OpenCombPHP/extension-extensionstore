<?php
namespace org\opencomb\extensionstore\extension;

use org\opencomb\platform\ext\Extension;
use org\jecat\framework\mvc\model\db\Extension;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class DeleteExtension extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'删除文章',
			'view'=>array(
				'template'=>'DeleteExtension.html',
				'class'=>'view'
			),
			'model:extension'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'table'=>'extension',
					'limit'=>-1,
					'hasMany:attachments' => array (
							'fromkeys' => array ( 'aid',),
							'tokeys' => array ( 'aid', ),
							'table' => 'attachment',
					)
				)
			)
		);
	}
	
	public function process()
	{
		//权限
		$this->requirePurview('purview:admin_category','extension',$this->extension->cid,'您没有这个分类的管理权限,无法继续浏览');
		
		//要删除哪些项?把这些项数组一起删除,如果只有一项,也把也要保证它是数组
		if ($this->params->get ( "aid" ))
		{
			$arrAids = explode(',', $this->params->get ( "aid" ));
			$sSql = 'aid in ( ';
			foreach($arrAids as $nKey=>$sValue)
			{
				if($nKey)
				{
					$sSql.=',';
				}
				$sSql.= '@'.($nKey+1);
			}
			$sSql.=  " )";
			
			$this->extension->loadSql ( $sSql , $arrAids);
			
			//删除附件
			$arrFilePaths = array();
			foreach($this->extension->child('attachments') as $aAttaModel)
			{
				$arrFilePaths[] = $aAttaModel['storepath'];
			}
			
			if ($this->extension->delete ())
			{
				$this->deleteAttachments($arrFilePaths);
				$this->messageQueue ()->create ( Message::success, "删除文章成功" );
			}
			else
			{
				$this->messageQueue ()->create ( Message::error, "删除文章失败" );
			}
		}else{
			$this->messageQueue ()->create ( Message::error, "未指定文章" );
		}
		
		$this->location('/?c=org.opencomb.extension.extension.ExtensionManage');
	}
	
	/**
	 * 批量附件删除
	 * 
	 * @param array $arrFilePaths 文件的相对路径数组,
	 * 
	 * @return boolean 如果有一个文件删除失败就返回false
	 */
	static public function deleteAttachments(array $arrFilePaths , $sExtension = 'extension'){
		if(!$arrFilePaths)
		{
			return true;
		}
		$sStorePath = Extension::flyweight($sExtension)->FilesFolder()->path();
		$bSuccess = true;
		foreach($arrFilePaths as $sFilePath)
		{
			$bSuccess = $bSuccess && @unlink( $sStorePath . $sFilePath );
		}
		
		return $bSuccess;
	}
}