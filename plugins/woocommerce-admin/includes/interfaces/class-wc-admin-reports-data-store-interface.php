<?php
/**
 * Reports Data Store Interface
 *
 * @package  WooCommerce Admin/Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Reports data store interface.
 *
 * @since 3.5.0
 */
interface WC_Admin_Reports_Data_Store_Interface {

	/**
	 * Get the data based on args.
	 *
	 * @param array $args Query parameters.
	 * @return stdClass|WP_Error
	 */
	public function get_data( $args );
}
