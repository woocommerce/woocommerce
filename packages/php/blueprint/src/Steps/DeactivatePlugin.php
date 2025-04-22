<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class DeactivatePlugin
 */
class DeactivatePlugin extends Step {
	/**
	 * The plugin name.
	 *
	 * @var string $plugin_name The plugin name.
	 */
	private string $plugin_name;

	/**
	 * DeactivatePlugin constructor.
	 *
	 * @param string $plugin_name string The plugin name.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Get the step name.
	 *
	 * @return string
	 */
	public static function get_step_name(): string {
		return 'deactivatePlugin';
	}

	/**
	 * Get the schema for this step.
	 *
	 * @param int $version The schema version.
	 *
	 * @return array
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
	 * Prepare the JSON array for this step.
	 *
	 * @return array
	 */
	public function prepare_json_array(): array {
		return array(
			'step'       => static::get_step_name(),
			'pluginName' => $this->plugin_name,
		);
	}
}
