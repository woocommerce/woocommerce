<?php

return array(
	'type'                 => 'object',
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'wordpress.org/themes',
			'description' => 'Identifies the file resource as a WordPress Core theme',
		),
		'slug'     => array(
			'type'        => 'string',
			'description' => 'The slug of the WordPress Core theme',
		),
	),
	'required'             => array(
		'resource',
		'slug',
	),
	'additionalProperties' => false,
);
