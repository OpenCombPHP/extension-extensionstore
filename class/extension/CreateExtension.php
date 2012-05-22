<?php
namespace org\opencomb\extensionstore\extension;

use org\jecat\framework\util\Version;
use org\jecat\framework\util\VersionScope;

use org\opencomb\platform\ext\ExtensionMetainfo;

use org\jecat\framework\auth\IdManager;

use org\opencomb\coresystem\mvc\controller\Controller;
use org\jecat\framework\fs\File;
use org\jecat\framework\fs\archive\DateAchiveStrategy;
use org\jecat\framework\fs\Folder;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\mvc\model\db\Category;
use org\jecat\framework\mvc\view\DataExchanger;
use org\jecat\framework\message\Message;

class CreateExtension extends Controller
{
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=>'提交扩展',
			'view'=>array(
				'template'=>'ExtensionForm.html',
				'class'=>'form',
				'model'=>'extension',
				'widgets'=>array(
					array(
						'config'=>'widget/extension_cat'
					),
				),
			),
			'model:extension'=>array(
				'class'=>'model',
				'orm'=>array(
					'table'=>'extension',
					'hasMany:dependence' => array (
						'fromkeys' => array ( 'ext_name','ext_version_int'),
						'tokeys' => array ( 'ext_name','ext_version_int'),
						'table' => 'dependence',
					)
				),
			),
			'model:dependence'=>array(
				'class'=>'model',
				'list'=>true,
				'orm'=>array(
						'limit'=>20,
						'table'=>'dependence',
				),
			),
		);
	}
	
	public function process()
	{
		//权限
		$aId = $this->requireLogined();
		
		$this->view->variables()->set('page_h1',"提交扩展") ;
		$this->view->variables()->set('save_button',"发布扩展") ;
		
		$this->doActions();
	}
	
	public function actionSubmit()
	{	
		//$this->extension->printStruct();
		//记录创建时间
		$this->extension->setData('createTime',time());
		//记录作者
		$this->extension->setData('author',IdManager::singleton()->currentUserId());
		
		if($this->params->has('extension_files'))
		{
			$ExtensionFiles = $this->params->get('extension_files');

			$aStoreFolder = Extension::flyweight('extensionstore')->FilesFolder();
				
	
				$sFileTempName = $ExtensionFiles['tmp_name'];
				$sFileType = $ExtensionFiles['type'];
				$sFileSize = $ExtensionFiles['size'];
				//文件是否上传成功
				if( empty($sFileTempName) || empty($sFileType) || empty($sFileSize) )
				{
					return;
				}
				
				if($sFileType != 'application/zip')
				{
					$this->messageQueue ()->create ( Message::error, "上传的文件不是zip类型" );
					return;
				}
				
				$xml = simplexml_load_file('zip://'.$sFileTempName."#metainfo.xml") ;

				$aExtMetainfo = ExtensionMetainfo::loadFromXML($xml);

				$sExtensionVersionString = $aExtMetainfo->version()->toString();
				$nVersionInt = Version::fromString($sExtensionVersionString);
				$sExtensionName = $aExtMetainfo->name();
				$sExtensionTitle = $aExtMetainfo->title();
 				$sExtensionDec = $aExtMetainfo->description();
 				
 				
				//exit;
				if(!$sExtensionName || !$sExtensionVersionString || !$sExtensionDec){
					$this->messageQueue ()->create ( Message::error, "metainfo不完整 , 缺少扩展名称或版本号，内容描述" );
				}
				
				
				$nVersionInt = Version::fromString($sExtensionVersionString);
				
				
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
				$sSavedFile = $aStoreFolder->HttpUrl();

				// 创建保存目录
				$aFolderOfSavedFile = new Folder( $sSavedFile ) ;
				if( ! $aFolderOfSavedFile->exists() ){
					if (! $aFolderOfSavedFile->create() )
					{
						throw new Exception ( __CLASS__ . "的" . __METHOD__ . "在创建路径\"%s\"时出错", array ($aFolderOfSavedFile->path () ) );
					}
				}
				
				$sSavedFile = $sSavedFile . '/' . $sExtensionName . '-' . $sExtensionVersionString . '.' . 'zip';
				
				//转换成相对路径
				if( strpos($sSavedFile , $aStoreFolder->path()) === 0 ){
					$sSavedFileRelativePath = substr($sSavedFile,strlen($aStoreFolder->path()));
				}
				
				if(!move_uploaded_file($sFileTempName,$sSavedFile))
				{
					throw new Exception ( "上传文件失败,move_uploaded_file , 临时路径:" . $sFileTempName . ", 目的路径:" .$sSavedFile );
				}
				
				$arrIndexs = explode(',', $this->params->get('extension_files_index'));
				
			
				$this->extension->setData('version',$sExtensionVersionString);
				$this->extension->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$this->extension->setData('ext_name',$sExtensionName);
				$this->extension->setData('title',$sExtensionTitle);
				$this->extension->setData('description',$sExtensionDec);
				$this->extension->setData('orginname' , $sExtensionName . '-' . $sExtensionVersionString . '.' . 'zip');
				$this->extension->setData('pkgUrl' , $sSavedFile); 
				$this->extension->setData('size' , $sFileSize );
				$this->extension->setData('ty$aExtMetainfope' , $sFileType );
			}
		/*           end 处理附件             */
		try{
			if ($this->extension->save ())
			{
				$this->setDependence($aExtMetainfo,$sExtensionVersionString,$sExtensionName);
				$this->messageQueue ()->create ( Message::success, "扩展保存成功" );
			}
			else
			{
				$this->messageQueue ()->create ( Message::error, "扩展保存失败" );
			}
		}catch (Exception $e){
			$this->messageQueue ()->create ( Message::error, "已存在此扩展" );
		}
	}
	
	public function setDependence($aExtMetainfo,$sExtensionVersionString,$sExtensionName)
	{
		$aExtensionDependence = $aExtMetainfo->dependence();
		$arrExtensionDependence = array();
		
		$nVersionInt = Version::fromString($sExtensionVersionString);
		
		foreach($aExtensionDependence->iterator() as $item)
		{
			$this->dependence->load();
			switch ($item->type())
			{
				case 'language';
				$this->dependence->setData('did',null);
				$this->dependence->setData('ext_name',$sExtensionName);
				$this->dependence->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$this->dependence->setData('type',$item->type());
				$this->dependence->setData('itemname','php');
				$aLow=$item->versionScope()->low();
				$this->dependence->setData('low',empty($aLow)?null:$aLow->to32Integer());
				$aHigh=$item->versionScope()->high();
				$this->dependence->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
				$this->dependence->setData('lowcompare',$item->versionScope()->lowCompare());
				$this->dependence->setData('highcompare',$item->versionScope()->highCompare());
				$this->dependence->save ();
				break ;
				case 'language_module';
				$this->dependence->setData('did',null);
				$this->dependence->setData('ext_name',$sExtensionName);
				$this->dependence->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$this->dependence->setData('type',$item->type());
				$this->dependence->setData('itemname',$item->itemName());
				$aLow=$item->versionScope()->low();
				$this->dependence->setData('low',empty($aLow)?null:$aLow->to32Integer());
				$aHigh=$item->versionScope()->high();
				$this->dependence->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
				$this->dependence->setData('lowcompare',$item->versionScope()->lowCompare());
				$this->dependence->setData('highcouse org\opencomb\platform\ext\ExtensionMetainfo;
						mpare',$item->versionScope()->highCompare());
				$this->dependence->save ();
				break;
				case 'framework';
				$this->dependence->setData('did',null);
				$this->dependence->setData('ext_name',$sExtensionName);
				$this->dependence->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$this->dependence->setData('type',$item->type());
				$this->dependence->setData('itemname','framework');
				$aLow=$item->versionScope()->low();
				$this->dependence->setData('low',empty($aLow)?null:$aLow->to32Integer());
				$aHigh=$item->versionScope()->high();
				$this->dependence->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
				$this->dependence->setData('lowcompare',$item->versionScope()->lowCompare());
				$this->dependence->setData('highcompare',$item->versionScope()->highCompare());
				$this->dependence->save ();
				break;
				case 'platform';
				$this->dependence->setData('did',null);
				$this->dependence->setData('ext_name',$sExtensionName);
				$this->dependence->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$this->dependence->setData('type',$item->type());
				$this->dependence->setData('itemname','opencomb');
				$aLow=$item->versionScope()->low();
				$this->dependence->setData('low',empty($aLow)?null:$aLow->to32Integer());
				$aHigh=$item->versionScope()->high();
				$this->dependence->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
				$this->dependence->setData('lowcompare',$item->versionScope()->lowCompare());
				$this->dependence->setData('highcompare',$item->versionScope()->highCompare());
				$this->dependence->save ();
				break;
				case 'extension';
				$this->dependence->setData('did',null);
				$this->dependence->setData('ext_name',$sExtensionName);
				$this->dependence->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$this->dependence->setData('type',$item->type());
				$this->dependence->setData('itemname',$item->itemName());
				$aLow=$item->versionScope()->low();
				$this->dependence->setData('low',empty($aLow)?null:$aLow->to32Integer());
				$aHigh=$item->versionScope()->high();
				$this->dependence->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
				$this->dependence->setData('lowcompare',$item->versionScope()->lowCompare());
				$this->dependence->setData('highcompare',$item->versionScope()->highCompare());
				$this->dependence->save ();
				break;
			}
		}
	}
}