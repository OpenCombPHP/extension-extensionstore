jQuery(function(){
	jQuery('.topList').each(function(b,v){
		resetTitleLen(jQuery(v));
	});
	
	function resetTitleLen(aTopList){
		//字符容器的宽度
		var arrLis = aTopList.find('ul>li');
		var nLiLen = arrLis.first().width();
		
		//时间栏目的宽度
		var arrDataTimes = arrLis.find('>span');
		var nDataTimeLen = arrDataTimes.first().width();
		
		//获取所有的链接
		var arrAs = arrLis.find(">a");
		
		arrAs.each(function(b,v){
			jQuery(v).attr('title',jQuery(v).text());
			jQuery(v).width(nLiLen - nDataTimeLen - 8);
		});
	}
});