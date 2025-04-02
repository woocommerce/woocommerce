<?php

return [
	"type" => "object",
	"properties" => [
		"resource" => [
			"type" => "string",
			"const" => "wordpress.org/plugins",
			"description" => "Identifies the file resource as a WordPress Core plugin"
		],
		"slug" => [
			"type" => "string",
			"description" => "The slug of the WordPress Core plugin"
		]
	],
	"required" => [
		"resource",
		"slug"
	],
	"additionalProperties" => false
];
