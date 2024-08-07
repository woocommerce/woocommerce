<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

/**
 * Class SetWCPaymentGateways
 *
 * This class sets WooCommerce payment gateways and extends the Step class.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Steps
 */
class SetWCPaymentGateways extends Step {

	/**
	 * Payment gateways.
	 *
	 * @var array $payment_gateways Array of payment gateways.
	 */
	protected array $payment_gateways = array();

	/**
	 * Constructor.
	 *
	 * @param array $payment_gateways Optional array of payment gateways.
	 */
	public function __construct( array $payment_gateways = array() ) {
		$this->payment_gateways = $payment_gateways;
	}

	/**
	 * Add a payment gateway.
	 *
	 * @param string $id The ID of the payment gateway.
	 * @param string $title The title of the payment gateway.
	 * @param string $description The description of the payment gateway.
	 * @param string $enabled Whether the payment gateway is enabled ('yes' or 'no').
	 */
	public function add_payment_gateway( $id, $title, $description, $enabled ) {
		$this->payment_gateways[ $id ] = array(
			'title'       => $title,
			'description' => $description,
			'enabled'     => $enabled,
		);
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public static function get_step_name(): string {
		return 'setWCPaymentGateways';
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
				'step'             => array(
					'type' => 'string',
					'enum' => array( 'setWCPaymentGateways' ),
				),
				'payment_gateways' => array(
					'type'                 => 'object',
					'patternProperties'    => array(
						'^[a-zA-Z0-9_]+$' => array(
							'type'       => 'object',
							'properties' => array(
								'title'       => array(
									'type' => 'string',
								),
								'description' => array(
									'type' => 'string',
								),
								'enabled'     => array(
									'type' => 'string',
									'enum' => array( 'yes', 'no' ),
								),
							),
							'required'   => array( 'title', 'description', 'enabled' ),
						),
					),
					'additionalProperties' => false,
				),
			),
			'required'   => array( 'step', 'payment_gateways' ),
		);
	}

	/**
	 * Prepare the JSON array for the step.
	 *
	 * @return array The JSON array.
	 */
	public function prepare_json_array(): array {
		return array(
			'step'             => static::get_step_name(),
			'payment_gateways' => $this->payment_gateways,
		);
	}
}
