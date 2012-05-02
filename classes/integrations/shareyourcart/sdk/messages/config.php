<?php
/**
 * This is the configuration for generating message translations
 * for the app. It is used by the 'yiic message' command.
 */
return array(
	'sourcePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..', //the "sdk" folder's parent ( the actual plugin )
	'messagePath'=>dirname(__FILE__),  //put in this directory
	'translator' => 'SyC::t',  //the functions name that we use to translate the SDK
	'languages'=>array('ro','fr','cs'),
	'fileTypes'=>array('php'),
	'overwrite' => true,  //override old translation file
	'removeOld' => true,  //remove old translations
	'exclude'=>array(
		'.svn',
		'/messages',
	),
);
