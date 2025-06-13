<?php

return array(
	'type'                 => 'object',
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'wordpress.org/plugins',
			'description' => 'Identifies the file resource as a WordPress Core plugin',
		),
		'slug'     => array(
			'type'        => 'string',
			'description' => 'The slug of the WordPress Core plugin',
		),
	),
	'required'             => array(
		'resource',
		'slug',
	),
	'additionalProperties' => false,
);
