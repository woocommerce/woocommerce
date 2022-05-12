<?php
/**
 * OrdersTableDataStoreHelper class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

/**
 * Provides helper functions for the implementation of the OrdersTableDataStore data store.
 * Specifically, includes methods for converting between order and column data types.
 */
class OrdersTableDataStoreHelper {

	/**
	 * Placeholder types for column types.
	 *
	 * @var array
	 */
	private static $wpdb_placeholder_for_type = array(
		'int'        => '%d',
		'decimal'    => '%f',
		'string'     => '%s',
		'date'       => '%s',
		'date_epoch' => '%s',
		'bool'       => '%d',
	);

	/**
	 * Produces an array with keys 'row' and 'format' that can be passed to `$wpdb->update()` as the `$data` and
	 * `$format` parameters. Values are taken from the order changes array and properly formatted for inclusion in the
	 * database.
	 *
	 * @param array $changes
	 * @param array $column_mapping
	 * @return array
	 */
	public function get_db_row_from_order_changes( $changes, $column_mapping ) {
		global $wpdb;

		$row        = array();
		$row_format = array();

		foreach ( $column_mapping as $column => $details ) {
			if ( ! isset( $details['name'] ) || ! array_key_exists( $details['name'], $changes ) ) {
				continue;
			}

			$row[ $column ] = $this->format_value_for_db( $changes[ $details['name'] ], $details['type'] );
			$row_format[ $column ] = $this->get_wpdb_format_for_type( $details['type'] );
		}

		return ( ! empty( $row ) ) ? array( 'row' => $row, 'format' => $row_format ) : false;
	}

	/**
	 * Formats a value of type `$type` for inclusion in the database.
	 *
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
	public function format_value_for_db( $value, string $type ) {
		switch ( $type ) {
			case 'decimal':
				$value = (float) $value;
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'bool':
				$value = wc_string_to_bool( $value );
				break;
			case 'string':
				$value = strval( $value );
				break;
			case 'date':
				$value = $value ? (new \DateTime( $value ) )->format( 'Y-m-d H:i:s' ) : null;
				break;
			case 'date_epoch':
				$value = $value ? (new \DateTime( "@{$value}" ) )->format( 'Y-m-d H:i:s' ) : null;
				break;
			default:
				throw new \Exception( 'Invalid type received: ' . $type );
				break;
		}

		return $value;
	}

	/**
	 * Returns the `$wpdb` placeholder to use for data type `$type`.
	 *
	 * @param string $type
	 * @return string
	 */
	private function get_wpdb_format_for_type( string $type ) {
		if ( ! isset( self::$wpdb_placeholder_for_type[ $type ] ) ) {
			throw new \Exception('Invalid column type: ' . $type);
		}

		return self::$wpdb_placeholder_for_type[ $type ];
	}

}
