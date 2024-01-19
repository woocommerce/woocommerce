<?php
/**
 * MainQueryFilters class file.
 */

namespace Automattic\WooCommerce\Internal\ProductQueryFilters;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

defined( 'ABSPATH' ) || exit;

/**
 * MainQueryFilters class.
 */
class MainQueryFilters {
	use AccessiblePrivateMethods;

	/**
	 * Instance of FilterClauses.
	 *
	 * @var FilterClauses
	 */
	private $filter_clauses;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FilterClauses $filter_clauses Instance of FilterClauses.
	 *
	 * @return void
	 */
	final public function init( FilterClauses $filter_clauses ): void {
		$this->filter_clauses = $filter_clauses;
		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		self::add_filter( 'posts_clauses', array( $this, 'main_query_filter' ), 10, 2 );
	}

	/**
	 * Filter the posts clauses of the main query to suport global filters.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	private function main_query_filter( $args, $wp_query ) {
		if (
			! $wp_query->is_main_query() ||
			'product_query' !== $wp_query->get( 'wc_query' )
		) {
			return $args;
		}

		if ( $wp_query->get( 'filter_stock_status' ) ) {
			$args = $this->filter_clauses->add_stock_clauses( $args, $wp_query );
		}

		return $args;
	}
}
