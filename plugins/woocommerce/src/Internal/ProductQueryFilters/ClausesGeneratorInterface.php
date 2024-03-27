<?php
/**
 * ClausesProviderInterface interface file.
 */

namespace Automattic\WooCommerce\Internal\ProductQueryFilters;

defined( 'ABSPATH' ) || exit;

/**
 * ClausesProviderInterface interface.
 */
interface ClausesGeneratorInterface {

	/**
	 * Add conditional query clauses based on the filter params in query vars.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses( array $args, \WP_Query $wp_query );
}
