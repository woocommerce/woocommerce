<?php

return [
	"type" => "object",
	"properties" => [
		"resource" => [
			"type" => "string",
			"const" => "git:directory",
			"description" => "Identifies the file resource as a git directory"
		],
		"url" => [
			"type" => "string",
			"description" => "The URL of the git repository"
		],
		"ref" => [
			"type" => "string",
			"description" => "The branch of the git repository"
		],
		"path" => [
			"type" => "string",
			"description" => "The path to the directory in the git repository"
		]
	],
	"required" => [
		"resource",
		"url",
		"ref",
		"path"
	],
	"additionalProperties" => false
];
