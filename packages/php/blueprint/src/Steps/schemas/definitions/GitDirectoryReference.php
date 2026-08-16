<?php

return array(
	'type'                 => 'object',
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'git:directory',
			'description' => 'Identifies the file resource as a git directory',
		),
		'url'      => array(
			'type'        => 'string',
			'description' => 'The URL of the git repository',
		),
		'ref'      => array(
			'type'        => 'string',
			'description' => 'The branch of the git repository',
		),
		'path'     => array(
			'type'        => 'string',
			'description' => 'The path to the directory in the git repository',
		),
	),
	'required'             => array(
		'resource',
		'url',
		'ref',
		'path',
	),
	'additionalProperties' => false,
);
