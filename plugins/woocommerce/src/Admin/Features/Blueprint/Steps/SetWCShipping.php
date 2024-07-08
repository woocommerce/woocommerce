<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

class SetWCShipping extends Step {
	private array $methods;
	private array $locations;
	private array $zones;

	public function __construct($methods, $locations, $zones) {
		$this->methods = $methods;
		$this->locations = $locations;
		$this->zones = $zones;
	}

	public function prepare_json_array() {
		return array(
			"step" => static::get_step_name(),
			"values" => array(
				"methods" => $this->methods,
				"locations" => $this->locations,
				"zones" => $this->zones,
			),
		);
	}

	public static function get_step_name() {
		return 'setWCShipping';
	}

	public static function get_schema($version = 1) {
		return array(
			"type" => "object",
			"properties" => [
				"step" => [
					"type" => "string",
					"enum" => [static::get_step_name()],
				],
				"values" => [
					"type" => "object",
					"properties" => [
						"classes" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"term_taxonomy_id" => ["type" => "string"],
									"term_id" => ["type" => "string"],
									"taxonomy" => ["type" => "string"],
									"description" => ["type" => "string"],
									"parent" => ["type" => "string"],
									"count" => ["type" => "string"]
								],
								"required" => ["term_taxonomy_id", "term_id", "taxonomy", "description", "parent", "count"]
							]
						],
						"terms" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"term_id" => ["type" => "string"],
									"name" => ["type" => "string"],
									"slug" => ["type" => "string"],
									"term_group" => ["type" => "string"]
								],
								"required" => ["term_id", "name", "slug", "term_group"]
							]
						],
						"local_pickup" => [
							"type" => "object",
							"properties" => [
								"general" => [
									"type" => "object",
									"properties" => [
										"enabled" => ["type" => "string"],
										"title" => ["type" => "string"],
										"tax_status" => ["type" => "string"],
										"cost" => ["type" => "string"]
									],
								],
								"locations" => [
									"type" => "array",
									"items" => [
										"type" => "object",
										"properties" => [
											"name" => ["type" => "string"],
											"address" => [
												"type" => "object",
												"properties" => [
													"address_1" => ["type" => "string"],
													"city" => ["type" => "string"],
													"state" => ["type" => "string"],
													"postcode" => ["type" => "string"],
													"country" => ["type" => "string"]
												],
											],
											"details" => ["type" => "string"],
											"enabled" => ["type" => "boolean"]
										],
									]
								]
							]
						],
						"shipping_methods" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"zone_id" => ["type" => "string"],
									"instance_id" => ["type" => "string"],
									"method_id" => ["type" => "string"],
									"method_order" => ["type" => "string"],
									"is_enabled" => ["type" => "string"]
								],
								"required" => ["zone_id", "instance_id", "method_id", "method_order", "is_enabled"]
							]
						],
						"shipping_locations" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"location_id" => ["type" => "string"],
									"zone_id" => ["type" => "string"],
									"location_code" => ["type" => "string"],
									"location_type" => ["type" => "string"]
								],
								"required" => ["location_id", "zone_id", "location_code", "location_type"]
							]
						],
						"shipping_zones" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"zone_id" => ["type" => "string"],
									"zone_name" => ["type" => "string"],
									"zone_order" => ["type" => "string"]
								],
								"required" => ["zone_id", "zone_name", "zone_order"]
							]
						]
					],
				]
			],
			"required" => ["step", "values"]
		);
	}
}
