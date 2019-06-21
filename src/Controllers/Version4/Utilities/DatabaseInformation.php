<?php
/**
 * Database information for status report.
 *
 * @package Automattic/WooCommerce/Utilities
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities;

/**
 * DatabaseInformation class.
 */
class DatabaseInformation {
	/**
	 * Add prefix to table.
	 *
	 * @param string $table Table name.
	 * @return string
	 */
	protected function add_db_table_prefix( $table ) {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Get array of database information. Version, prefix, and table existence.
	 *
	 * @return array
	 */
	public function get_database_info() {
		global $wpdb;

		$database_table_information = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				    table_name AS 'name',
					engine,
				    round( ( data_length / 1024 / 1024 ), 2 ) 'data',
				    round( ( index_length / 1024 / 1024 ), 2 ) 'index'
				FROM information_schema.TABLES
				WHERE table_schema = %s
				ORDER BY name ASC;",
				DB_NAME
			)
		);

		// WC Core tables to check existence of.
		$core_tables = apply_filters(
			'woocommerce_database_tables',
			array(
				'woocommerce_sessions',
				'woocommerce_api_keys',
				'woocommerce_attribute_taxonomies',
				'woocommerce_downloadable_product_permissions',
				'woocommerce_order_items',
				'woocommerce_order_itemmeta',
				'woocommerce_tax_rates',
				'woocommerce_tax_rate_locations',
				'woocommerce_shipping_zones',
				'woocommerce_shipping_zone_locations',
				'woocommerce_shipping_zone_methods',
				'woocommerce_payment_tokens',
				'woocommerce_payment_tokenmeta',
				'woocommerce_log',
			)
		);

		/**
		 * Adding the prefix to the tables array, for backwards compatibility.
		 *
		 * If we changed the tables above to include the prefix, then any filters against that table could break.
		 */
		$core_tables = array_map( array( $this, 'add_db_table_prefix' ), $core_tables );

		/**
		 * Organize WooCommerce and non-WooCommerce tables separately for display purposes later.
		 *
		 * To ensure we include all WC tables, even if they do not exist, pre-populate the WC array with all the tables.
		 */
		$tables = array(
			'woocommerce' => array_fill_keys( $core_tables, false ),
			'other'       => array(),
		);

		$database_size = array(
			'data'  => 0,
			'index' => 0,
		);

		$site_tables_prefix = $wpdb->get_blog_prefix( get_current_blog_id() );
		$global_tables      = $wpdb->tables( 'global', true );
		foreach ( $database_table_information as $table ) {
			// Only include tables matching the prefix of the current site, this is to prevent displaying all tables on a MS install not relating to the current.
			if ( is_multisite() && 0 !== strpos( $table->name, $site_tables_prefix ) && ! in_array( $table->name, $global_tables, true ) ) {
				continue;
			}
			$table_type = in_array( $table->name, $core_tables, true ) ? 'woocommerce' : 'other';

			$tables[ $table_type ][ $table->name ] = array(
				'data'   => $table->data,
				'index'  => $table->index,
				'engine' => $table->engine,
			);

			$database_size['data']  += $table->data;
			$database_size['index'] += $table->index;
		}

		// Return all database info. Described by JSON Schema.
		return array(
			'wc_database_version'    => get_option( 'woocommerce_db_version' ),
			'database_prefix'        => $wpdb->prefix,
			'maxmind_geoip_database' => \WC_Geolocation::get_local_database_path(),
			'database_tables'        => $tables,
			'database_size'          => $database_size,
		);
	}
}
