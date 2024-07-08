<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

class SetWCPaymentGateways extends Step {
	protected array $payment_gateways = array();
	public function __construct(array $payment_gateways = array()) {
		$this->payment_gateways = $payment_gateways;
	}

	public function add_payment_gateway($id, $title, $description, $enabled) {
	    $this->payment_gateways[$id] = array(
	        'title' => $title,
	        'description' => $description,
	        'enabled' => $enabled,
	    );
	}

	public static function get_step_name() {
		return 'setWCPaymentGateways';
	}

	public static function get_schema($version = 1) {
		return array(
			'type' => 'object',
			'properties' => [
				'step' => [
					'type' => 'string',
					'enum' => ['setWCPaymentGateways']
				],
				'payment_gateways' => [
					'type' => 'object',
					'patternProperties' => [
						'^[a-zA-Z0-9_]+$' => [
							'type' => 'object',
							'properties' => [
								'title' => [
									'type' => 'string'
								],
								'description' => [
									'type' => 'string'
								],
								'enabled' => [
									'type' => 'string',
									'enum' => ['yes', 'no']
								]
							],
							'required' => ['title', 'description', 'enabled']
						]
					],
					'additionalProperties' => false
				]
			],
			'required' => ['step', 'payment_gateways']
		);
	}

	public function prepare_json_array() {
		return array(
			'step' => static::get_step_name(),
			'payment_gateways' => $this->payment_gateways,
		);
	}
}
