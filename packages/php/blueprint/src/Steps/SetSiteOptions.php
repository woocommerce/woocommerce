<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

class SetSiteOptions extends Step {
	private array $options;

	public function __construct( array $options = array() ) {
		$this->options = $options;
	}

	public static function get_step_name() {
		return 'setSiteOptions';
	}

	public static function get_schema( $version = 1 ) {
		return array(
			'type'       => 'object',
			'properties' => array(
				'step'    => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'options' => array(
					'type'                 => 'object',
					'additionalProperties' => new \stdClass(),
				),
			),
			'required'   => array( 'step', 'options' ),
		);
	}

	public function prepare_json_array() {
		return array(
			'step'    => static::get_step_name(),
			'options' => $this->options,
		);
	}
}
