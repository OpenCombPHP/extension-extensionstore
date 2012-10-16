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
use org\jecat\framework\mvc\model\Model;

class CreateExtension extends Controller
{
	protected $arrConfig = array(
				'title'=>'提交扩展',
				'view'=>array(
					'template'=>'ExtensionForm.html',
					'class'=>'view',
					'widgets'=>array(
						array(
							'config'=>'widget/extension_cat'
						),
					),
				)
			);
	
	
	public function process()
	{	
		//权限
		$aId = $this->requireLogined();
		
		$this->doActions() ;
		
		$this->view->variables()->set('page_h1',"提交扩展") ;
		$this->view->variables()->set('save_button',"发布扩展") ;
	}
	
	public function form()
	{	
		//权限
		$aId = $this->requireLogined();
		
		$this->view->variables()->set('page_h1',"提交扩展") ;
		$this->view->variables()->set('save_button',"发布扩展") ;
		
		
		$extensionModel = Model::create('extensionstore:extension')
		->hasMany('extensionstore:dependence',array('ext_name','ext_version_int'),array('ext_name','ext_version_int'));
		
		//记录创建时间
		$extensionModel->setData('createTime',time());
		
		//记录作者
		$extensionModel->setData('author',IdManager::singleton()->currentUserId());
		
		if($this->params->has('extension_files'))
		{	//exit;
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
				
			
				$extensionModel->setData('version',$sExtensionVersionString);
				$extensionModel->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$extensionModel->setData('ext_name',$sExtensionName);
				$extensionModel->setData('title',$sExtensionTitle);
				$extensionModel->setData('description',$sExtensionDec);
				$extensionModel->setData('orginname' , $sExtensionName . '-' . $sExtensionVersionString . '.' . 'zip');
				$extensionModel->setData('pkgUrl' , $sSavedFile); 
				$extensionModel->setData('size' , $sFileSize );
				$extensionModel->setData('ty$aExtMetainfope' , $sFileType );
			}
		/*           end 处理附件             */
		try{
			if ($extensionModel->insert ())
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
		
		$dependenceModel = Model::create('extensionstore:dependence');
		
		foreach($aExtensionDependence->iterator() as $item)
		{
			$dependenceModel->load();
			switch ($item->type())
			{
				case 'language';
				$dependenceModel->setData('did',null);
				$dependenceModel->setData('ext_name',$sExtensionName);
				$dependenceModel->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$dependenceModel->setData('type',$item->type());
				$dependenceModel->setData('itemname','php');
				$aLow=$item->versionScope()->low();
				$aHigh=$item->versionScope()->high();
				if($aLow && $aHigh){
					if($aLow->to32Integer() == $aHigh->to32Integer()){
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',null);
						$dependenceModel->setData('lowcompare','=');
						$dependenceModel->setData('highcompare',null);
					}else{
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
						$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
						$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
					}
				}else{
					$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
					$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
					$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
					$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
				}
				$dependenceModel->insert();
				break ;
				case 'language_module';
				$dependenceModel->setData('did',null);
				$dependenceModel->setData('ext_name',$sExtensionName);
				$dependenceModel->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$dependenceModel->setData('type',$item->type());
				$dependenceModel->setData('itemname',$item->itemName());
				$aLow=$item->versionScope()->low();
				$aHigh=$item->versionScope()->high();
				if($aLow && $aHigh){
					if($aLow->to32Integer() == $aHigh->to32Integer()){
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',null);
						$dependenceModel->setData('lowcompare','=');
						$dependenceModel->setData('highcompare',null);
					}else{
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
						$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
						$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
					}
				}else{
					$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
					$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
					$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
					$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
				}
				/*看不懂
				$dependenceModel->setData('highcouse org\opencomb\platform\ext\ExtensionMetainfo;
						mpare',$item->versionScope()->highCompare());
				*/
				$dependenceModel->insert();
				break;
				case 'framework';
				$dependenceModel->setData('did',null);
				$dependenceModel->setData('ext_name',$sExtensionName);
				$dependenceModel->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$dependenceModel->setData('type',$item->type());
				$dependenceModel->setData('itemname','framework');
				$aLow=$item->versionScope()->low();
				$aHigh=$item->versionScope()->high();
				if($aLow && $aHigh){
					if($aLow->to32Integer() == $aHigh->to32Integer()){
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',null);
						$dependenceModel->setData('lowcompare','=');
						$dependenceModel->setData('highcompare',null);
					}else{
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
						$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
						$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
					}
				}else{
					$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
					$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
					$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
					$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
				}
				$dependenceModel->insert();
				break;
				case 'platform';
				$dependenceModel->setData('did',null);
				$dependenceModel->setData('ext_name',$sExtensionName);
				$dependenceModel->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$dependenceModel->setData('type',$item->type());
				$dependenceModel->setData('itemname','opencomb');
				$aLow=$item->versionScope()->low();
				$aHigh=$item->versionScope()->high();
				if($aLow && $aHigh){
					if($aLow->to32Integer() == $aHigh->to32Integer()){
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',null);
						$dependenceModel->setData('lowcompare','=');
						$dependenceModel->setData('highcompare',null);
					}else{
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
						$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
						$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
					}
				}else{
					$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
					$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
					$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
					$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
				}
				$dependenceModel->insert();
				break;
				case 'extension';
				$dependenceModel->setData('did',null);
				$dependenceModel->setData('ext_name',$sExtensionName);
				$dependenceModel->setData('ext_version_int',$aExtMetainfo->version()->to32Integer());
				$dependenceModel->setData('type',$item->type());
				$dependenceModel->setData('itemname',$item->itemName());
				$aLow=$item->versionScope()->low();
				$aHigh=$item->versionScope()->high();
				if($aLow && $aHigh){
					if($aLow->to32Integer() == $aHigh->to32Integer()){
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',null);
						$dependenceModel->setData('lowcompare','=');
						$dependenceModel->setData('highcompare',null);
					}else{
						$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
						$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
						$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
						$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
					}
				}else{
					$dependenceModel->setData('low',empty($aLow)?null:$aLow->to32Integer());
					$dependenceModel->setData('high',empty($aHigh)?null:$aHigh->to32Integer());
					$dependenceModel->setData('lowcompare',$item->versionScope()->lowCompare());
					$dependenceModel->setData('highcompare',$item->versionScope()->highCompare());
				}

				$dependenceModel->insert();
				break;
			}
		}
	}
}