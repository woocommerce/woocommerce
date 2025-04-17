<?php

return array(
	'type'                 => 'object',
	'properties'           => array(
		'resource' => array(
			'type'        => 'string',
			'const'       => 'literal',
			'description' => 'Identifies the file resource as a literal file',
		),
		'name'     => array(
			'type'        => 'string',
			'description' => 'The name of the file',
		),
		'contents' => array(
			'anyOf'       => array(
				array(
					'type' => 'string',
				),
				array(
					'type'                 => 'object',
					'properties'           => array(
						'BYTES_PER_ELEMENT' => array(
							'type' => 'number',
						),
						'buffer'            => array(
							'type'                 => 'object',
							'properties'           => array(
								'byteLength' => array(
									'type' => 'number',
								),
							),
							'required'             => array(
								'byteLength',
							),
							'additionalProperties' => false,
						),
						'byteLength'        => array(
							'type' => 'number',
						),
						'byteOffset'        => array(
							'type' => 'number',
						),
						'length'            => array(
							'type' => 'number',
						),
					),
					'required'             => array(
						'BYTES_PER_ELEMENT',
						'buffer',
						'byteLength',
						'byteOffset',
						'length',
					),
					'additionalProperties' => array(
						'type' => 'number',
					),
				),
			),
			'description' => 'The contents of the file',
		),
	),
	'required'             => array(
		'resource',
		'name',
		'contents',
	),
	'additionalProperties' => false,
);
