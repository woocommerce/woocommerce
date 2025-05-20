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
	 * The path to the plugin file relative to the plugins directory.
	 *
	 * @var string  The path to the plugin file relative to the plugins directory.
	 */
	private string $plugin_path;

	/**
	 * ActivatePlugin constructor.
	 *
	 * @param string $plugin_path Path to the plugin file relative to the plugins directory.
	 * @param string $plugin_name The name of the plugin to be activated.
	 */
	public function __construct( $plugin_path, $plugin_name = '' ) {
		$this->plugin_name = $plugin_name;
		$this->plugin_path = $plugin_path;
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
				'pluginPath' => array(
					'type' => 'string',
				),
			),
			'required'   => array( 'step', 'pluginPath' ),
		);
	}

	/**
	 * Prepares an associative array for JSON encoding.
	 *
	 * @return array Array of data to be encoded as JSON.
	 */
	public function prepare_json_array(): array {
		$data = array(
			'step'       => static::get_step_name(),
			'pluginPath' => $this->plugin_path,
		);

		if ( ! empty( $this->plugin_name ) ) {
			$data['pluginName'] = $this->plugin_name;
		}

		return $data;
	}
}
