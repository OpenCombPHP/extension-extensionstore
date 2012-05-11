<?php
namespace org\opencomb\extensionstore\extension;

use org\opencomb\platform\ext\Extension;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\fs\File;
use org\opencomb\coresystem\mvc\controller\Controller;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\message\Message;

class ExtensionContent extends Controller
{
	public function createBeanConfig()
	{
		return array(
			'title'=> '扩展内容',
			'view'=>array(
				'template'=>'ExtensionContent.html',
				'class'=>'view',
				'model'=>'extension',
			),
			'model:extension'=>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'extension',
					'hasMany:attachments' => array (
						'fromkeys' => array ( 'eid' ),
						'tokeys' => array ( 'eid' ),
						'table' => 'attachment',
						'orderby' => 'index'
					)
				)
			),
		);
	}
	
	public function process()
	{
		if($this->params->has("eid"))
		{
			if(!$this->extension->load(array($this->params->get("eid")),array('eid')))
			{
				$this->messageQueue ()->create ( Message::error, "错误的扩展编号" );
			}
		}else{
			$this->messageQueue ()->create ( Message::error, "未指定扩展" );
		}
		//浏览次数
		$this->extension->setData( "views",(int)$this->extension->data("views") + 1 );
		$this->extension->save();
		
		$this->view->variables()->set('extension',$this->extension) ;
		
		$this->setTitle($this->extension->title);
		
		//把cid传给frame
		$this->frame()->params()->set('cid',$this->extension->cid);
	}
	
	public function defaultFrameConfig()
	{
		return array('class'=>'org\\opencomb\\extensionstore\\frame\\ExtensionFrontFrame') ;
	}
	
	static public function getHttpUrl($sFilePath)
	{
		return Extension::flyweight('extensionstore')->FilesFolder()->httpUrl() . $sFilePath;
	}
	
	static public function getContentWithAttachmentUrl( $sContent , $aAttachmentModel )
	{
		foreach($aAttachmentModel as $aModel)
		{
			$sReplace = '';
			//如果是图片就直接显示图片
			if(strpos( $aModel['type'] , 'image' ) !== false)
			{
				$sReplace = '<img src="' . self::getHttpUrl($aModel['storepath']) . '"/>';
			}else{//不是图片就显示超链接
				$sReplace = '<a href="' . self::getHttpUrl($aModel['storepath']) . '">' . $aModel['orginname'] . '</a>';
			}
			$sContent = str_replace("[attachment {$aModel['index']}]", $sReplace, $sContent);
		}
		return $sContent;
	}
}