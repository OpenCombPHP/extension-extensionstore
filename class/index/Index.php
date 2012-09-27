<?php
namespace org\opencomb\extensionstore\index;

use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\Controller;
use org\jecat\framework\system\Application;
use org\opencomb\extensionstore\extension\TopList;
use org\jecat\framework\util\Version;
use org\opencomb\platform\ext\ExtensionManager;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\mvc\model\Model;
use org\jecat\framework\mvc\view\View;

class Index extends Controller
{
	protected $arrConfig = array(
					'title'=>'首页',
					'view'=>array(
						'template'=>'Index.html',
						'class'=>'view',
						'widgets' =>array(
							array(
									'id' => 'paginator',
									'class' => 'paginator' ,
	// 								'count'=>2, //每页5项
	// 								'nums' =>5, //显示5个页码
							),
						)
					),
				);

	public function process()
	{
		$extensionModel = Model::create('extensionstore:extension')
		->hasMany('extensionstore:dependence',array('ext_name','ext_version_int'),array('ext_name','ext_version_int'));
		
		if(strchr($_SERVER['REQUEST_URI'],'&setupHost'))
		{
			$bFlagBack=true;
			$this->view->variables()->set('BackAddress',$this->params->get('setupHost')) ;
			$this->view->variables()->set('bFlagBack',$bFlagBack) ;
		};
		
		$extensionModel->load();//var_dump($extensionModel);exit;
		$aModelIterator = $extensionModel;
		//$aModelIterator = $this->extension->childIterator();
		$arrModelSecond=$this->createModelExtension($aModelIterator);
		
		$arrSecond = $this->extensionVersionSort($arrModelSecond);
		$arrSecond = $this->createVersionSelect($arrSecond);
		$this->view->variables()->set('arrSecond',$arrSecond) ;
		
		$this->setPaginator(count($arrSecond));
		
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
			$arrModelFirst[$key]=array('eid'=>$aModel['eid'],'extname'=>$aModel['ext_name'],'title'=>$aModel['title']
					,'description'=>$aModel['description'],'createtime'=>$aModel['createTime']
					,'version'=>$aModel['version'],'extversion_int'=>Version::from32Integer($aModel['ext_version_int'])->toString()
					,'32version'=>$aModel['ext_version_int']
					,'author'=>$aModel['author'],'orginname'=>$aModel['orginname']
					,'pkgUrl'=>$aModel['pkgUrl'],'size'=>$aModel['size']
					,'type'=>$aModel['type']
					,'dependence'=>array(),'descriptionless'=>mb_substr($aModel['description'], 0,13,'utf-8')
			);
			
			foreach($aModel['dependence'] as $adependence)
			{
				switch($adependence['type'])
				{
					case 'language';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence['type'],'itemname'=>$adependence['itemname']
								,'low'=>Version::from32Integer($adependence['low'])->toString()
								,'high'=>$adependence['high']==null ?null :Version::from32Integer($adependence['high'])->toString()
								,'lowcompare'=>$adependence['lowcompare'],'highcompare'=>$adependence['highcompare']
								,'typeCh'=>'语言','itemnameCh'=>null
						);
						break;
					case 'language_module';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence['type'],'itemname'=>$adependence['itemname']
								,'low'=>Version::from32Integer($adependence['low'])->toString()
								,'high'=>$adependence['high']==null ?null :Version::from32Integer($adependence['high'])->toString()
								,'lowcompare'=>$adependence['lowcompare'],'highcompare'=>$adependence['highcompare']
								,'typeCh'=>'语言模块','itemnameCh'=>null
						);
						break;
					case 'framework';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence['type'],'itemname'=>$adependence['itemname']
								,'low'=>Version::from32Integer($adependence['low'])->toString()
								,'high'=>$adependence['high']==null ?null :Version::from32Integer($adependence['high'])->toString()
								,'lowcompare'=>$adependence['lowcompare'],'highcompare'=>$adependence['highcompare']
								,'typeCh'=>'支持框架','itemnameCh'=>'蜂巢框架'
						);
						break;
					case 'platform';
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence['type'],'itemname'=>$adependence['itemname']
								,'low'=>Version::from32Integer($adependence['low'])->toString()
								,'high'=>$adependence['high']==null ?null :Version::from32Integer($adependence['high'])->toString()
								,'lowcompare'=>$adependence['lowcompare'],'highcompare'=>$adependence['highcompare']
								,'typeCh'=>'平台','itemnameCh'=>'蜂巢平台'
						);
						break;
					case 'extension';
						
						$aExt = Extension::flyweight($adependence['itemname']);
						$arrModelFirst[$key]['dependence'][]=array('type'=>$adependence['type'],'itemname'=>$adependence['itemname']
								,'low'=>Version::from32Integer($adependence['low'])->toString()
								,'high'=>$adependence['high']==null ?null :Version::from32Integer($adependence['high'])->toString()
								,'lowcompare'=>$adependence['lowcompare'],'highcompare'=>$adependence['highcompare']
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
	
	public function setPaginator($nTotal)
	{
		$nPerPageRowNumber = 10 ;
		$this->view()->widget('paginator')->setTotalCount($nTotal);
		$this->view()->widget('paginator')->setPerPageCount($nPerPageRowNumber);
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