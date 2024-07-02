<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

class InstallTheme extends Step {
	private string $slug;
	private string $resource;
	private array $options;

	public function __construct($slug, $resource, array $options = array()) {
		$this->slug = $slug;
		$this->resource = $resource;
		$this->options = $options;
	}

	public function prepare_json_array() {
		return array(
			"step" => static::get_step_name(),
			"themeZipFile" => array(
				"resource" => $this->resource,
				"slug" => $this->slug,
			),
			"options" => $this->options,
		);
	}

	public function get_schema($version = 1) {
	    return array(
		    '$id' => 1,
		    "type" => "object",
		    "properties" => [
			    "step" => [
				    "type" => "string",
				    "enum" => [static::get_step_name()],
			    ],
			    "themeZipFile" => [
				    "type" => "object",
				    "properties" => [
					    "resource" => [
						    "type" => "string",
					    ],
					    "slug" => [
						    "type" => "string",
					    ],
				    ],
				    "required" => ["resource", "slug"],
			    ],
			    "options" => [
				    "type" => "object",
				    "properties" => [
					    "activate" => [
						    "type" => "boolean",
					    ],
				    ],
			    ],
		    ],
		    "required" => ["step", "pluginZipFile"],
	    );
	}

	public static function get_step_name() {
		return 'installTheme';
	}
}
