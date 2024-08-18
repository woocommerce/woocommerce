<?php
/**
 * Class for parameter-based Reports querying
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Admin\API\Reports\Query
 *
 * @deprecated x.x.x Query class is deprecated. Currently GenericController gets the dats directly from DataStore using its `prepare_reports_query` to get the defaults.
 */
abstract class Query extends \WC_Object_Query {

	/**
	 * Create a new query.
	 *
	 * @deprecated x.x.x The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @param array $args Criteria to query on in a format similar to WP_Query.
	 */
	public function __construct( $args = array() ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'Use GenericQuery or \WC_Object_Query instead' );
		parent::__construct( $args );
	}

	/**
	 * Get report data matching the current query vars.
	 *
	 * @deprecated x.x.x The Query class is deprecated. Please get data from the store directly in your Controller..
	 *
	 * @return array|object of WC_Product objects
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, 'x.x.x', 'The Query class is deprecated. Please get data from the store directly in your Controller.' );
		/* translators: %s: Method name */
		return new \WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'woocommerce' ), __METHOD__ ), array( 'status' => 405 ) );
	}
}
