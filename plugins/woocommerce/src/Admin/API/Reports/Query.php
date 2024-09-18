<?php
/**
 * Class for parameter-based Reports querying
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Admin\API\Reports\Query
 *
 * @deprecated 9.3.0 Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
 */
abstract class Query extends \WC_Object_Query {

	/**
	 * Create a new query.
	 *
	 * @deprecated 9.3.0 Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @param array $args Criteria to query on in a format similar to WP_Query.
	 */
	public function __construct( $args = array() ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );
		parent::__construct( $args );
	}

	/**
	 * Get report data matching the current query vars.
	 *
	 * @deprecated 9.3.0 Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
	 *
	 * @return array|object of WC_Product objects
	 */
	public function get_data() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use' );
		/* translators: %s: Method name */
		return new \WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'woocommerce' ), __METHOD__ ), array( 'status' => 405 ) );
	}
}
