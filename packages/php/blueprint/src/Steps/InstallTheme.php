<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class InstallTheme
 *
 * This class represents a step in the installation process of a WooCommerce theme.
 * It includes methods to prepare the data for the theme installation step and to provide
 * the schema for the JSON representation of this step.
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class InstallTheme extends Step {
	/**
	 * The slug of the theme to be installed.
	 *
	 * @var string The slug of the theme to be installed.
	 */
	private string $slug;

	/**
	 * The resource URL or path to the theme's ZIP file.
	 *
	 * @var string The resource URL or path to the theme's ZIP file.
	 */
	private string $resource;

	/**
	 * Additional options for the theme installation.
	 *
	 * @var array Additional options for the theme installation.
	 */
	private array $options;

	/**
	 * InstallTheme constructor.
	 *
	 * @param string $slug The slug of the theme to be installed.
	 * @param string $resource The resource URL or path to the theme's ZIP file.
	 * @param array  $options Additional options for the theme installation.
	 */
	// phpcs:ignore
	public function __construct( $slug, $resource, array $options = array() ) {
		$this->slug     = $slug;
		$this->resource = $resource;
		$this->options  = $options;
	}

	/**
	 * Prepares an associative array for JSON encoding.
	 *
	 * @return array The JSON-encoded array representing this installation step.
	 */
	public function prepare_json_array(): array {
		return array(
			'step'         => static::get_step_name(),
			'themeZipFile' => array(
				'resource' => $this->resource,
				'slug'     => $this->slug,
			),
			'options'      => $this->options,
		);
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
				'step'         => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'themeZipFile' => array(
					'type'       => 'object',
					'properties' => array(
						'resource' => array(
							'type' => 'string',
						),
						'slug'     => array(
							'type' => 'string',
						),
					),
					'required'   => array( 'resource', 'slug' ),
				),
				'options'      => array(
					'type'       => 'object',
					'properties' => array(
						'activate' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'   => array( 'step', 'themeZipFile' ),
		);
	}

	/**
	 * Returns the name of this step.
	 *
	 * @return string The step name.
	 */
	public static function get_step_name(): string {
		return 'installTheme';
	}
}
