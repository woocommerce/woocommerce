<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

use Automattic\WooCommerce\Blueprint\Util;

/**
 * Class RunSql
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class RunSql extends Step {
	/**
	 * Placeholder for the database table prefix.
	 *
	 * Exported SQL uses this placeholder in place of the source site's table
	 * prefix. When the Blueprint is imported, the placeholder is replaced with
	 * the importing site's prefix, so Blueprints remain portable across sites
	 * that use different database table prefixes.
	 *
	 * @var string
	 */
	public const TABLE_PREFIX_PLACEHOLDER = '{WC_BLUEPRINT_TABLE_PREFIX}';

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
	 * Build a RunSql step for a database row, using the portable table-prefix
	 * placeholder in place of the live database prefix.
	 *
	 * Pass the unprefixed table name (e.g. 'woocommerce_shipping_zones'). The
	 * placeholder is prepended for you, so exported SQL stays portable across
	 * sites with different table prefixes. On import, ImportRunSql resolves the
	 * placeholder back to the importing site's prefix.
	 *
	 * @param array  $row   Row data keyed by column name.
	 * @param string $table Unprefixed table name.
	 * @param string $type  One of insert, insert ignore, replace into.
	 * @return self
	 */
	public static function from_table_row( array $row, string $table, string $type = 'replace into' ): self {
		return new self( (string) Util::array_to_insert_sql( $row, self::TABLE_PREFIX_PLACEHOLDER . $table, $type ) );
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
