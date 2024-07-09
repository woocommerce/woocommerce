<?php
/**
 * Reports Data Store Interface
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Reports data store interface.
 * It's no longer in use, as of 9.2.0 DataStore implements `get_data` method directly.
 *
 * @deprecated 9.2.0
 *
 * @since 3.5.0
 */
interface DataStoreInterface {

	/**
	 * Get the data based on args.
	 *
	 * @param array $args Query parameters.
	 * @return stdClass|WP_Error
	 */
	public function get_data( $args );
}
