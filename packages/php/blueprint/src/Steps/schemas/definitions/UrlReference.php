<?php

return [
	"type" => "object",
	"properties" => [
		"resource" => [
			"type" => "string",
			"const" => "url",
			"description" => "Identifies the file resource as a URL"
		],
		"url" => [
			"type" => "string",
			"description" => "The URL of the file"
		],
		"caption" => [
			"type" => "string",
			"description" => "Optional caption for displaying a progress message"
		]
	],
	"required" => [
		"resource",
		"url"
	],
	"additionalProperties" => false
];
