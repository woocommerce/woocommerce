<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class ActivatePlugin
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class ActivatePlugin extends Step {
	/**
	 * The name of the plugin to be activated.
	 *
	 * @var string The name of the plugin to be activated.
	 */
	private string $plugin_name;

	/**
	 * ActivatePlugin constructor.
	 *
	 * @param string $plugin_name The name of the plugin to be activated.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Returns the name of this step.
	 *
	 * @return string The step name.
	 */
	public static function get_step_name(): string {
		return 'activatePlugin';
	}

	/**
	 * Returns the schema for the JSON representation of this step.
	 *
	 * @param int $version The version of the schema to return.
	 * @return array The schema array.
	 */
	public static function get_schema( int $version = 1 ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'step'       => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'pluginName' => array(
					'type' => 'string',
				),
			),
			'required'   => array( 'step', 'pluginName' ),
		);
	}

	/**
	 * Prepares an associative array for JSON encoding.
	 *
	 * @return array Array of data to be encoded as JSON.
	 */
	public function prepare_json_array(): array {
		return array(
			'step'       => static::get_step_name(),
			'pluginName' => $this->plugin_name,
		);
	}
}
