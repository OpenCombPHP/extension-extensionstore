<?php
return array(
	'id'=>'extension_title',
	'class'=>'text',
	'title'=>'扩展名称',
	'exchange'=>'title',
	'verifier:notempty'=>array(),
	'verifier:length'=>array(
			'min'=>2,
			'max'=>255)
);