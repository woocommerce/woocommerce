<?php
/**
 * Helper class to handle term queries for the API.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Term Query class.
 *
 * @since 2.5.0
 */
class TermQuery {
	/**
	 * Prepare query args to pass to WP_Query for a REST API request.
	 *
	 * @param \WP_REST_Request $request Request data.
	 * @return array
	 */
	public function prepare_objects_query( $request ) {
		$args               = array();
		$args['order']      = $request['order'];
		$args['orderby']    = $request['orderby'];
		$args['taxonomy']   = $request['taxonomy'];
		$args['hide_empty'] = (bool) $request['hide_empty'];
		return $args;
	}

	/**
	 * Get objects.
	 *
	 * @param \WP_REST_Request $request Request data.
	 * @return array
	 */
	public function get_objects( $request ) {
		$query_args = $this->prepare_objects_query( $request );
		$query      = new \WP_Term_Query();

		return $query->query( $query_args );
	}
}
