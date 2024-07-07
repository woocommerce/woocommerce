<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

class SetWCTaxRates extends Step {
	private array $rates;
	private array $locations;

	public function __construct(array $rates, array $locations) {
		$this->rates = $rates;
		$this->locations = $locations;
	}

	public function prepare_json_array() {
		return array(
			"step" => static::get_step_name(),
			"values" => array(
				"rates" => $this->rates,
				"locations" => $this->locations,
			),
		);
	}

	public static function get_step_name() {
		return 'setWCTaxRates';
	}

	public static function get_schema($version = 1) {
		return array(
			'$id' => 1,
			"type" => "object",
			"properties" => [
				"step" => [
					"type" => "string",
					"enum" => [static::get_step_name()],
				],
				"values" => [
					"type" => "object",
					"properties" => [
						"rates" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"tax_rate_id" => ["type" => "string"],
									"tax_rate_country" => ["type" => "string"],
									"tax_rate_state" => ["type" => "string"],
									"tax_rate" => ["type" => "string"],
									"tax_rate_name" => ["type" => "string"],
									"tax_rate_priority" => ["type" => "string"],
									"tax_rate_compound" => ["type" => "string"],
									"tax_rate_shipping" => ["type" => "string"],
									"tax_rate_order" => ["type" => "string"],
									"tax_rate_class" => ["type" => "string"]
								],
								"required" => [
									"tax_rate_id",
									"tax_rate_country",
									"tax_rate_state",
									"tax_rate",
									"tax_rate_name",
									"tax_rate_priority",
									"tax_rate_compound",
									"tax_rate_shipping",
									"tax_rate_order",
									"tax_rate_class"
								]
							]
						],
						"locations" => [
							"type" => "array",
							"items" => [
								"type" => "object",
								"properties" => [
									"location_id" => ["type" => "string"],
									"location_code" => ["type" => "string"],
									"tax_rate_id" => ["type" => "string"],
									"location_type" => ["type" => "string"]
								],
								"required" => ["location_id", "location_code", "tax_rate_id", "location_type"]
							]
						]
					],
					"required" => ["rates"]
				]
			],
			"required" => ["step", "values"]
		);
	}
}
