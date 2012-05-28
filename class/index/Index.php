<?php
namespace org\opencomb\extensionstore\index;

use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller;
use org\jecat\framework\system\Application;
use org\opencomb\extensionstore\extension\TopList;
use org\jecat\framework\util\Version;
use org\opencomb\platform\ext\ExtensionManager;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\mvc\view\widget\paginator\PaginaltorTester;

class Index extends Controller
{
	public function createBeanConfig()
	{
		$arrBean = array(
				'title'=>'首页',
				'view'=>array(
					'template'=>'Index.html',
					'class'=>'view',
					//'model'=>'extension',
					'widget:paginator' => array(
								'class' => 'paginator' ,
// 								'count'=>2, //每页5项
// 								'nums' =>5, //显示5个页码
					) ,
				),
				'model:extension'=>array(
						'class'=>'model',
						'list'=>'true',
						'orm'=>array(
								'limit'=>20,
								'table'=>'extension',
								//建立hasMany关系
								'hasMany:dependence'=>array(
										'fromkeys'=>array( 'ext_name','ext_version_int'),
										'tokeys'=>array( 'ext_name','ext_version_int'),
										'table'=>'dependence',
								)
						)
				),
		);	
		
		//$this->location('http://app.qs.local.com/?c=index',0);
		$this->setCatchOutput(false) ;
		return $arrBean;
	}

	public function process()
	{
		if(strchr($_SERVER['REQUEST_URI'],'&setupHost'))
		{
			$bFlagBack=true;
			$this->view->variables()->set('BackAddress',$this->params->get('setupHost')) ;
			$this->view->variables()->set('bFlagBack',$bFlagBack) ;
		};
		
		$this->modelExtension->load();
		$aModelIterator = $this->extension->childIterator();
		$arrModelSecond=$this->createModelExtension($aModelIterator);
		
		$arrSecond = $this->extensionVersionSort($arrModelSecond);
		$arrSecond = $this->createVersionSelect($arrSecond);
		
		
		$arrExtensionsChunk = array();
		$arrExtensionsChunk = $this->getExtensionsChunk($arrSecond,$nPerPageRowNumber=10);
		
		$arrExtensionsPerPage = $this->getExtensionsPerPage($arrExtensionsChunk,1);
		$this->view->variables()->set('arrSecond',$arrExtensionsPerPage) ;
		
		$this->setPaginatorTester(count($arrSecond));
		
		if($this->params['paginator'])
		{
			$nPerPageRowNumber = 10;
			$iCurrentPageNum = $this->params['paginator'];
			$arrExtensionsChunk = array();
			$arrExtensionsChunk = $this->getExtensionsChunk($arrSecond,$nPerPageRowNumber);
			
			$arrExtensionsPerPage = $this->getExtensionsPerPage($arrExtensionsChunk,$iCurrentPageNum);
			$this->view->variables()->set('arrSecond',$arrExtensionsPerPage) ;
		}
	}
	
	//扩展排序，最新版本在前
	public function extensionVersionSort($arrModelSecond)
	{
		$arrSecond = array();
		$arrSecond2 = array();
		
		//转化成下标为数字的数组,方便数组排序
		foreach($arrModelSecond as $key=>$item)
		{
			$i=0;
			foreach($item as $key1=>$item1)
			{
				$arrSecond[$key][$i]=$item1;
				$i++;
			}
		}
		
		//对相同扩展的不同版本进行由高到低的排序
		foreach($arrSecond as $key=>$item)
		{
			for($i=0;$i<count($item);$i++)
			{
				for($j=$i+1;$j<count($item);$j++)
				{
						$temp = '';
					if($item[$i]['32version']<$item[$j]['32version'])
					{
						$temp = $item[$j];
						$item[$j] = $item[$i];
						$item[$i] = $temp;
					}
			
				}
			}
			$arrSecond2[$key]=$item;
		};
		
		$arrSecond3=array();
		// 将下标为数字的数组转化为下标为字符的数组。
		foreach($arrSecond2 as $key=>$item)
		{
			foreach($item as $key1=>$item1)
			{
				$arrSecond3[$key][$item1['version']]=$item1;
			}
		}
		return $arrSecond3;
		
	}
	
	//创建版本选项数组
	public function createVersionSelect($arrSecond)
	{
		$arrVersionOption=array();
		foreach($arrSecond as $key=>$item)
		{
			foreach($item as $key1=>$item1)
			{
				$arrVersionOption[$key][$key.'.'.$item1['extversion_int']]=$item1['version'];
			}
		}
		
		foreach($arrSecond as $key=>&$item)
		{
			foreach($item as &$item1)
			{
				$item1['option']=$arrVersionOption[$key];
			}
		}
		
		return $arrSecond;
	}
	
	
	//读取扩展model创建数组
	public function createModelExtension($aModelIterator)
	{
		$arrModelFirst=array();
		foreach($aModelIterator as $key=>$aModel)
		{
			$arrModelFirst[$key]=array('eid'=>$aModel->data('eid'),'extname'=>$aModel->data('ext_name'),'title'=>$aModel->data('title')
					,'description'=>$aModel->data('description'),'createtime'=>$aModel->data('createTime')
					,'version'=>$aModel->data('version'),'extversion_int'=>Version::from32Integer($aModel->data('ext_version_int'))->toString()
					,'32version'=>$aModel->data('ext_version_int')
					,'author'=>$aModel->data('author'),'orginname'=>$aModel->data('orginname')
					,'pkgUrl'=>$aModel->data('pkgUrl'),'size'=>$aModel->data('size')
					,'type'=>$aModel->data('type')
					,'dependence'=>array(),'descriptionless'=>mb_substr($aModel->data('description'), 0,13,'utf-8')
			);
			
			foreach($aModel->child('dependence')->childIterator() as $adependence)
			{
				
				switch($adependence->data('type'))
				{
					case 'language';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence->data('type'),'itemname'=>$adependence->data('itemname')
								,'low'=>Version::from32Integer($adependence->data('low'))->toString()
								,'high'=>$adependence->data('high')==null ?null :Version::from32Integer($adependence->data('high'))->toString()
								,'lowcompare'=>$adependence->data('lowcompare'),'highcompare'=>$adependence->data('highcompare')
								,'typeCh'=>'语言','itemnameCh'=>null
						);
						break;
					case 'language_module';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence->data('type'),'itemname'=>$adependence->data('itemname')
								,'low'=>Version::from32Integer($adependence->data('low'))->toString()
								,'high'=>$adependence->data('high')==null ?null :Version::from32Integer($adependence->data('high'))->toString()
								,'lowcompare'=>$adependence->data('lowcompare'),'highcompare'=>$adependence->data('highcompare')
								,'typeCh'=>'语言模块','itemnameCh'=>null
						);
						break;
					case 'framework';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence->data('type'),'itemname'=>$adependence->data('itemname')
								,'low'=>Version::from32Integer($adependence->data('low'))->toString()
								,'high'=>$adependence->data('high')==null ?null :Version::from32Integer($adependence->data('high'))->toString()
								,'lowcompare'=>$adependence->data('lowcompare'),'highcompare'=>$adependence->data('highcompare')
								,'typeCh'=>'支持框架','itemnameCh'=>'蜂巢框架'
						);
						break;
					case 'platform';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence->data('type'),'itemname'=>$adependence->data('itemname')
								,'low'=>Version::from32Integer($adependence->data('low'))->toString()
								,'high'=>$adependence->data('high')==null ?null :Version::from32Integer($adependence->data('high'))->toString()
								,'lowcompare'=>$adependence->data('lowcompare'),'highcompare'=>$adependence->data('highcompare')
								,'typeCh'=>'平台','itemnameCh'=>'蜂巢平台'
						);
						break;
					case 'extension';
						
						$aExt=Extension::flyweight($adependence->data('itemname'));
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence->data('type'),'itemname'=>$adependence->data('itemname')
								,'low'=>Version::from32Integer($adependence->data('low'))->toString()
								,'high'=>$adependence->data('high')==null ?null :Version::from32Integer($adependence->data('high'))->toString()
								,'lowcompare'=>$adependence->data('lowcompare'),'highcompare'=>$adependence->data('highcompare')
								,'typeCh'=>'扩展','itemnameCh'=>$aExt->metainfo()->title()==null ? null :$aExt->metainfo()->title()
						);
						break;	
				};
			}
		}

		//first是第一个数组，将model中所有数据行集合成一个数组
		//second重新组合first，数组下标由extname和extversionint组成
		$arrModelSecond=array();
		$arrVersionOption=array();
		
		for($i=0;$i<count($arrModelFirst);$i++)
		{
			$arrModelFirst[$i]['extname'];
			$arrModelSecond[$arrModelFirst[$i]['extname']][$arrModelFirst[$i]['extversion_int']]=array(
					'eid'=>$arrModelFirst[$i]['eid'],'extname'=>$arrModelFirst[$i]['extname'],'title'=>$arrModelFirst[$i]['title']
					,'description'=>$arrModelFirst[$i]['description'],'createtime'=>$arrModelFirst[$i]['createtime']
					,'version'=>$arrModelFirst[$i]['version'],'extversion_int'=>$arrModelFirst[$i]['extversion_int']
					,'32version'=>$arrModelFirst[$i]['32version']
					,'author'=>$arrModelFirst[$i]['author'],'orginname'=>$arrModelFirst[$i]['orginname']
					,'pkgUrl'=>$arrModelFirst[$i]['pkgUrl'],'size'=>$arrModelFirst[$i]['size']
					,'type'=>$arrModelFirst[$i]['type'],'dependence'=>$arrModelFirst[$i]['dependence']
					,'descriptionless'=>$arrModelFirst[$i]['descriptionless']
			);
			for($h=$i+1;$h<count($arrModelFirst);$h++)
			{
				if($arrModelFirst[$i]['extname']==$arrModelFirst[$h]['extname']){
					$arrModelSecond[$arrModelFirst[$i]['extname']][$arrModelFirst[$h]['extversion_int']]=array(
								'eid'=>$arrModelFirst[$h]['eid'],'extname'=>$arrModelFirst[$h]['extname'],'title'=>$arrModelFirst[$h]['title']
								,'description'=>$arrModelFirst[$i]['description'],'createtime'=>$arrModelFirst[$h]['createtime']
								,'version'=>$arrModelFirst[$h]['version'],'extversion_int'=>$arrModelFirst[$h]['extversion_int']
								,'32version'=>$arrModelFirst[$i]['32version']
								,'author'=>$arrModelFirst[$h]['author'],'orginname'=>$arrModelFirst[$h]['orginname']
								,'pkgUrl'=>$arrModelFirst[$h]['pkgUrl'],'size'=>$arrModelFirst[$h]['size']
								,'type'=>$arrModelFirst[$h]['type'],'dependence'=>$arrModelFirst[$h]['dependence']
								,'descriptionless'=>$arrModelFirst[$i]['descriptionless']
					);
				}
			}
		}
		
		return $arrModelSecond;
	}
	
	public function getExtentionsChunk($arrModelSecondNumber,$nPerPageRowNumber=15)
	{
		return array_chunk($arrModelSecondNumber,$nPerPageRowNumber);
	}
	
	public function setSelectPageExtension($arrModelSecondNumber,$nNumberRow,$nPerPageRowNumber=20)
	{
		foreach($arrModelSecondNumber[$nNumberRow/$nPerPageRowNumber] as $key=>$value)
		{
			$arrModelSecondPerPage[$value['index']] = $value['ext'] ;
		}
		return $arrModelSecondPerPage;
	}
	
	public function setPaginatorTester($nTotal)
	{
		$nPerPageRowNumber = 10 ;
	
		$aPaginaltorTester = new PaginaltorTester();
		$aPaginaltorTester->setTotalCount($nTotal);
	
		$this->view->widget('paginator')->setPaginal($aPaginaltorTester);
		$this->view->widget('paginator')->setPerPageCount($nPerPageRowNumber);
	}
	
	public function getExtensionsChunk($arrLangTranslationSelect,$nPerPageRowNumber=10)
	{
		return array_chunk($arrLangTranslationSelect,$nPerPageRowNumber);
	}
	
	public function getExtensionsPerPage($arrExtensionsChunk,$iCurrentPageNum)
	{
		return $arrExtensionsChunk[$iCurrentPageNum-1];
	}
}