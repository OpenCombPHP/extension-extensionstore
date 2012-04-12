<?php
namespace org\opencomb\extensionstore\extension;

use org\jecat\framework\fs\File;

use org\jecat\framework\fs\archive\DateAchiveStrategy;
use org\jecat\framework\fs\Folder;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class CreateExtension extends ControlPanel
{
	/**
	 * @example /校验器/字符长度校验器(Length):Bean格式演示[1]
	 * @forwiki /校验器/字符长度校验器(Length)
	 * @forwiki /MVC模式/视图/表单控件/文件上传(File)
	 *
	 * 字符长度校验器的bean配置数组的写法
	 */
	public function createBeanConfig()
	{
		return array(
			'title'=>'新建文章',
			'view'=>array(
				'template'=>'ExtensionForm.html',
				'class'=>'form',
				'model'=>'extension',
				'widgets'=>array(
					array(
						'id'=>'extension_title',
						'class'=>'text',
						'title'=>'文章标题',
						'exchange'=>'title',
						'verifier:notempty'=>array(),
						'verifier:length'=>array(
								'min'=>2,
								'max'=>255)
					),
					array(
						'id'=>'extension_title_bold',
						'class'=>'checkbox',
						'title'=>'标题加粗',
						'exchange'=>'title_bold',
					),
					array(
						'id'=>'extension_title_italic',
						'class'=>'checkbox',
						'title'=>'标题斜体',
						'exchange'=>'title_italic',
					),
					array(
						'id'=>'extension_title_strikethrough',
						'class'=>'checkbox',
						'title'=>'标题删除线',
						'exchange'=>'title_strikethrough',
					),
					array(
						'id'=>'extension_title_color',
						'class'=>'text',
						'title'=>'标题颜色',
						'value'=>'#09C',
						'exchange'=>'title_color',
					),
					array(
						'id'=>'extension_url',
						'class'=>'text',
						'title'=>'文章链接',
						'exchange'=>'url',
					),
					array(
						'config'=>'widget/extension_cat'
					),
					array(
						'config'=>'widget/extension_content'
					),
						/*
					array(
						'id'=>'extension_img',    //文件控件bean设置的例子
						'class'=>'file',
						'folder'=>Extension::flyweight('extension')->filesFolder()->path(),  //取得扩展专用的文件保存路径,作为文件上传控件初始化的参数之一,这样控件就会知道应该把文件放在服务器的哪个文件夹下
						'title'=>'文章图片',
					)*/
				),
			),
			'model:extension'=>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'extension',
					'hasMany:attachments' => array (
						'fromkeys' => array ( 'aid',),
						'tokeys' => array ( 'aid', ),
						'table' => 'attachment',
					)
				),
			),
			'model:categoryTree'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
					'limit'=>-1,
					'table'=>'category',
					'name'=>'category',
				),
			),
		);
	}
	
	public function process()
	{
		//权限
		$this->requirePurview('purview:admin_category','extension',$this->view->widget('extension_cat')->value(),'您没有这个分类的管理权限,无法继续浏览');
		
		//为分类select添加option
		$aCatSelectWidget = $this->view->widget("extension_cat");
		
		$aCatSelectWidget->addOption("文章分类...",null,true);
		
		$this->categoryTree->load();
		
		Category::buildTree($this->categoryTree);
		
		foreach($this->categoryTree->childIterator() as $aCat)
		{
			$aCatSelectWidget->addOption(str_repeat("--", Category::depth($aCat)).$aCat->title,$aCat->cid,false);
		}
		
		$this->view->variables()->set('page_h1',"新建文章") ;
		$this->view->variables()->set('save_button',"发布文章") ;
		
		$this->doActions();
	}
	
	public function actionSubmit()
	{
		//加载所有控件的值
		if (! $this->view->loadWidgets ( $this->params ))
		{
			return;
		}

		//记录创建时间
		$this->extension->setData('createTime',time());

		$this->view->exchangeData ( DataExchanger::WIDGET_TO_MODEL );

		/*           处理附件             */
		if($this->params->has('extension_files'))
		{
			$arrExtensionFiles = $this->params->get('extension_files');
			$arrExtensionFilesList = $this->params->get('extension_list');
			if(!$arrExtensionFilesList)
			{
				$arrExtensionFilesList = array();
			}
			$aStoreFolder = Extension::flyweight('extension')->FilesFolder();
			$aAchiveStrategy = DateAchiveStrategy::flyweight ( Array (true, true, true ) );
				
			$aAttachmentsModel = $this->extension->child('attachments');
				
			foreach($arrExtensionFiles['name'] as $nKey=>$sFileName)
			{
				$sFileTempName = $arrExtensionFiles['tmp_name'][$nKey];
				$sFileType = $arrExtensionFiles['type'][$nKey];
				$sFileSize = $arrExtensionFiles['size'][$nKey];
				//文件是否上传成功
				if( empty($sFileTempName) || empty($sFileType) || empty($sFileSize) )
				{
					continue;
				}
					
				//移动文件
				if (empty ( $aStoreFolder ))
				{
					throw new Exception ( "非法的路径属性,无法依赖此路径属性创建对应的文件夹对象" );
				}
					
				if (! $aStoreFolder->exists ())
				{
					$aStoreFolder = $aStoreFolder->create ();
				}
					
				// 保存文件
				$sSavedFile = $aAchiveStrategy->makeFilePath ( array(), $aStoreFolder );
				// 创建保存目录
				$aFolderOfSavedFile = new Folder( $sSavedFile ) ;
				if( ! $aFolderOfSavedFile->exists() ){
					if (! $aFolderOfSavedFile->create() )
					{
						throw new Exception ( __CLASS__ . "的" . __METHOD__ . "在创建路径\"%s\"时出错", array ($aFolderOfSavedFile->path () ) );
					}
				}
				$sSavedFile = $sSavedFile . $aAchiveStrategy->makeFilename ( array('tmp_name'=> $sFileTempName, 'name'=> $sFileName) ) ;

				//转换成相对路径
				if( strpos($sSavedFile , $aStoreFolder->path()) === 0 ){
					$sSavedFileRelativePath = substr($sSavedFile,strlen($aStoreFolder->path()));
				}

				if(!move_uploaded_file($sFileTempName,$sSavedFile))
				{
					throw new Exception ( "上传文件失败,move_uploaded_file , 临时路径:" . $sFileTempName . ", 目的路径:" .$sSavedFile );
				}

				$arrIndexs = explode(',', $this->params->get('extension_files_index'));

				$aNewFileModel = $aAttachmentsModel->createChild();
				$aNewFileModel->setData('orginname' , $sFileName);
				$aNewFileModel->setData('storepath' , $sSavedFileRelativePath); //httpURL()
				$aNewFileModel->setData('size' , $sFileSize );
				$aNewFileModel->setData('type' , $sFileType );
				$aNewFileModel->setData('index' , $arrIndexs[$nKey] );
				if(!in_array((string)( $arrIndexs[$nKey]), $arrExtensionFilesList))
				{
					$aNewFileModel->setData('displayInList' , 0);
				}
			}
		}
		/*           end 处理附件             */

		if ($this->extension->save ())
		{
			// 					$this->view->hideForm ();
			$this->messageQueue ()->create ( Message::success, "文章保存成功" );
		}
		else
		{
			$this->messageQueue ()->create ( Message::error, "文章保存失败" );
		}
	}
}