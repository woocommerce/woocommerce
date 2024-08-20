<?php
/**
 * Class for parameter-based Reports querying
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Admin\API\Reports\Query
 *
 * @deprecated x.x.x Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
 */
abstract class Query extends \WC_Object_Query {

	/**
	 * Create a new query.
	 *
	 * @deprecated x.x.x Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
	 *
	 * @param array $args Criteria to query on in a format similar to WP_Query.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * Get report data matching the current query vars.
	 *
	 * @deprecated x.x.x Query class is deprecated, please use GenericQuery or \WC_Object_Query instead.
	 *
	 * @return array|object of WC_Product objects
	 */
	public function get_data() {
		/* translators: %s: Method name */
		return new \WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'woocommerce' ), __METHOD__ ), array( 'status' => 405 ) );
	}
}
