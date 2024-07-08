<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

class InstallPlugin extends Step {
	private string $slug;
	private string $resource;
	private array $options;

	public function __construct( $slug, $resource, array $options = array() ) {
		$this->slug     = $slug;
		$this->resource = $resource;
		$this->options  = $options;
	}

	public function prepare_json_array() {
		return array(
			'step'          => static::get_step_name(),
			'pluginZipFile' => array(
				'resource' => $this->resource,
				'slug'     => $this->slug,
			),
			'options'       => $this->options,
		);
	}

	public static function get_schema( $version = 1 ) {
		return array(
			'type'       => 'object',
			'properties' => array(
				'step'          => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'pluginZipFile' => array(
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
				'options'       => array(
					'type'       => 'object',
					'properties' => array(
						'activate' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'   => array( 'step', 'pluginZipFile' ),
		);
	}

	public static function get_step_name() {
		return 'installPlugin';
	}
}
