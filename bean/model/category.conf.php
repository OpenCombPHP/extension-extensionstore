<?php
return array(
	'class'=>'model',
	'orm'=>array(
		'table'=>'category',
		'hasMany:extension'=>array(
				'fromkeys'=>'cid',
				'tokeys'=>'cid',
				'config'=>'model/orm/extension'
		)
	)
);