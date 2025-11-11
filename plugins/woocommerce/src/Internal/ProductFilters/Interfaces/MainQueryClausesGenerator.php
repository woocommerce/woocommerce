<?php
/**
 * MainQueryClausesGenerator interface file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * MainQueryClausesGenerator interface.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
interface MainQueryClausesGenerator {

	/**
	 * Add conditional query clauses for main query based on the filter params in query vars.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses_for_main_query( array $args, \WP_Query $wp_query ): array;
}
