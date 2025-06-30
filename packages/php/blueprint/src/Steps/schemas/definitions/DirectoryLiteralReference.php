<?php

return array(
	'type'                 => 'object',
	'additionalProperties' => false,
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'literal:directory',
			'description' => 'Identifies the file resource as a git directory',
		),
		'files'    => array(
			'$ref' => '#/definitions/FileTree',
		),
		'name'     => array(
			'type' => 'string',
		),
	),
	'required'             => array(
		'files',
		'name',
		'resource',
	),
);
