<?php

return [
	"type" => "object",
	"properties" => [
		"resource" => [
			"type" => "string",
			"const" => "wordpress.org/themes",
			"description" => "Identifies the file resource as a WordPress Core theme"
		],
		"slug" => [
			"type" => "string",
			"description" => "The slug of the WordPress Core theme"
		]
	],
	"required" => [
		"resource",
		"slug"
	],
	"additionalProperties" => false
];
