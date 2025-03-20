<?php

return [
	"type" => "object",
	"properties" => [
		"resource" => [
			"type" => "string",
			"const" => "literal",
			"description" => "Identifies the file resource as a literal file"
		],
		"name" => [
			"type" => "string",
			"description" => "The name of the file"
		],
		"contents" => [
			"anyOf" => [
				[
					"type" => "string"
				],
				[
					"type" => "object",
					"properties" => [
						"BYTES_PER_ELEMENT" => [
							"type" => "number"
						],
						"buffer" => [
							"type" => "object",
							"properties" => [
								"byteLength" => [
									"type" => "number"
								]
							],
							"required" => [
								"byteLength"
							],
							"additionalProperties" => false
						],
						"byteLength" => [
							"type" => "number"
						],
						"byteOffset" => [
							"type" => "number"
						],
						"length" => [
							"type" => "number"
						]
					],
					"required" => [
						"BYTES_PER_ELEMENT",
						"buffer",
						"byteLength",
						"byteOffset",
						"length"
					],
					"additionalProperties" => [
						"type" => "number"
					]
				]
			],
			"description" => "The contents of the file"
		]
	],
	"required" => [
		"resource",
		"name",
		"contents"
	],
	"additionalProperties" => false
];
