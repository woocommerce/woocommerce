<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class DeletePlugin
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class DeletePlugin extends Step {
	/**
	 * The name of the plugin to be deleted.
	 *
	 * @var string The name of the plugin to be deleted.
	 */
	private string $plugin_name;

	/**
	 * DeletePlugin constructor.
	 *
	 * @param string $plugin_name The name of the plugin to be deleted.
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
		return 'deletePlugin';
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
	 * @return array Array representation of this step.
	 */
	public function prepare_json_array(): array {
		return array(
			'step'       => static::get_step_name(),
			'pluginName' => $this->plugin_name,
		);
	}
}
