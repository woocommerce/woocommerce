<?php

return array(
	'type'                 => 'object',
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'vfs',
			'description' => 'Identifies the file resource as Virtual File System (VFS)',
		),
		'path'     => array(
			'type'        => 'string',
			'description' => 'The path to the file in the VFS',
		),
	),
	'required'             => array( 'resource', 'path' ),
	'additionalProperties' => false,
);
