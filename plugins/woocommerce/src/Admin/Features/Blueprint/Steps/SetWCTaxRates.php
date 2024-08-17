<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

/**
 * Class SetWCTaxRates
 *
 * This class sets WooCommerce tax rates and extends the Step class.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Steps
 */
class SetWCTaxRates extends Step {

	/**
	 * Tax rates.
	 *
	 * @var array $rates Tax rates.
	 */
	private array $rates;

	/**
	 * Tax rate locations.
	 *
	 * @var array $locations Tax rate locations.
	 */
	private array $locations;

	/**
	 * Constructor.
	 *
	 * @param array $rates Tax rates.
	 * @param array $locations Tax rate locations.
	 */
	public function __construct( array $rates, array $locations ) {
		$this->rates     = $rates;
		$this->locations = $locations;
	}

	/**
	 * Prepare the JSON array for the step.
	 *
	 * @return array The JSON array.
	 */
	public function prepare_json_array(): array {
		return array(
			'step'   => static::get_step_name(),
			'values' => array(
				'rates'     => $this->rates,
				'locations' => $this->locations,
			),
		);
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public static function get_step_name(): string {
		return 'setWCTaxRates';
	}

	/**
	 * Get the schema for the step.
	 *
	 * @param int $version Optional version number of the schema.
	 * @return array The schema array.
	 */
	public static function get_schema( $version = 1 ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'step'   => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'values' => array(
					'type'       => 'object',
					'properties' => array(
						'rates'     => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'tax_rate_id'       => array( 'type' => 'string' ),
									'tax_rate_country'  => array( 'type' => 'string' ),
									'tax_rate_state'    => array( 'type' => 'string' ),
									'tax_rate'          => array( 'type' => 'string' ),
									'tax_rate_name'     => array( 'type' => 'string' ),
									'tax_rate_priority' => array( 'type' => 'string' ),
									'tax_rate_compound' => array( 'type' => 'string' ),
									'tax_rate_shipping' => array( 'type' => 'string' ),
									'tax_rate_order'    => array( 'type' => 'string' ),
									'tax_rate_class'    => array( 'type' => 'string' ),
								),
								'required'   => array(
									'tax_rate_id',
									'tax_rate_country',
									'tax_rate_state',
									'tax_rate',
									'tax_rate_name',
									'tax_rate_priority',
									'tax_rate_compound',
									'tax_rate_shipping',
									'tax_rate_order',
									'tax_rate_class',
								),
							),
						),
						'locations' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'location_id'   => array( 'type' => 'string' ),
									'location_code' => array( 'type' => 'string' ),
									'tax_rate_id'   => array( 'type' => 'string' ),
									'location_type' => array( 'type' => 'string' ),
								),
								'required'   => array( 'location_id', 'location_code', 'tax_rate_id', 'location_type' ),
							),
						),
					),
					'required'   => array( 'rates' ),
				),
			),
			'required'   => array( 'step', 'values' ),
		);
	}
}
