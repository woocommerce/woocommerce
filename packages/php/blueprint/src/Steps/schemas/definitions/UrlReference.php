<?php

return array(
	'type'                 => 'object',
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'url',
			'description' => 'Identifies the file resource as a URL',
		),
		'url'      => array(
			'type'        => 'string',
			'description' => 'The URL of the file',
		),
		'caption'  => array(
			'type'        => 'string',
			'description' => 'Optional caption for displaying a progress message',
		),
	),
	'required'             => array(
		'resource',
		'url',
	),
	'additionalProperties' => false,
);
