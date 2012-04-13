<?php
return array(
	'id'=>'extension_content',
	'class'=>'richText',
	'title'=>'扩展内容',
	'configuration'=>' toolbar : "Full" ',
	'exchange'=>'text',
	'verifier:notempty'=>array(),
	'verifier:length'=>array(
		'min'=>6
	)
);