<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class ActivateTheme
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class ActivateTheme extends Step {
	/**
	 * The name of the theme to be activated.
	 *
	 * @var string The name of the theme to be activated.
	 */
	private string $theme_name;

	/**
	 * ActivateTheme constructor.
	 *
	 * @param string $theme_name The name of the theme to be activated.
	 */
	public function __construct( $theme_name ) {
		$this->theme_name = $theme_name;
	}

	/**
	 * Returns the name of this step.
	 *
	 * @return string The step name.
	 */
	public static function get_step_name(): string {
		return 'activateTheme';
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
				'step'      => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'themeName' => array(
					'type' => 'string',
				),
			),
			'required'   => array( 'step', 'themeName' ),
		);
	}

	/**
	 * Prepares an associative array for JSON encoding.
	 *
	 * @return array Array of data to be encoded as JSON.
	 */
	public function prepare_json_array(): array {
		return array(
			'step'      => static::get_step_name(),
			'themeName' => $this->theme_name,
		);
	}
}
