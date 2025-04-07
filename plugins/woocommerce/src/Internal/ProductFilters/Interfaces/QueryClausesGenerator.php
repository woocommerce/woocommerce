<?php
/**
 * ClausesProviderInterface interface file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * QueryClausesGenerator interface.
 */
interface QueryClausesGenerator {

	/**
	 * Add conditional query clauses based on the filter params in query vars.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses( array $args, \WP_Query $wp_query );
}
