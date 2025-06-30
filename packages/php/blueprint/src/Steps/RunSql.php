<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class RunSql
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class RunSql extends Step {
	/**
	 * Sql code to run.
	 *
	 * @var string
	 */
	protected string $sql = '';

	/**
	 * Name of the sql file.
	 *
	 * @var string
	 */
	protected string $name = 'schema.sql';

	/**
	 * Constructor.
	 *
	 * @param string $sql Sql code to run.
	 * @param string $name Name of the sql file.
	 */
	public function __construct( string $sql, $name = 'schema.sql' ) {
		$this->sql  = $sql;
		$this->name = $name;
	}

	/**
	 * Returns the name of this step.
	 *
	 * @return string The step name.
	 */
	public static function get_step_name(): string {
		return 'runSql';
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
				'step' => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'sql'  => array(
					'type'       => 'object',
					'required'   => array( 'contents', 'resource', 'name' ),
					'properties' => array(
						'resource' => array(
							'type' => 'string',
							'enum' => array( 'literal' ),
						),
						'name'     => array(
							'type' => 'string',
						),
						'contents' => array(
							'type' => 'string',
						),
					),
				),
			),
			'required'   => array( 'step', 'sql' ),
		);
	}

	/**
	 * Prepares an associative array for JSON encoding.
	 *
	 * @return array Array of data to be encoded as JSON.
	 */
	public function prepare_json_array(): array {
		return array(
			'step' => static::get_step_name(),
			'sql'  => array(
				'resource' => 'literal',
				'name'     => $this->name,
				'contents' => $this->sql,
			),
		);
	}
}
