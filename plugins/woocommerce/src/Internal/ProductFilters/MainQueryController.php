<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;
/**
 * Hooks into WordPress filters to handle product filters for the main query.
 */
class MainQueryController implements RegisterHooksInterface {

	/**
	 * Instance of QueryClauses.
	 *
	 * @var QueryClauses
	 */
	private $query_clauses;

	/**
	 * Initialize dependencies.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param QueryClauses $query_clauses Instance of QueryClauses.
	 *
	 * @return void
	 */
	final public function init( QueryClauses $query_clauses ): void {
		$this->query_clauses = $query_clauses;
	}

	/**
	 * Hook into actions and filters.
	 */
	public function register() {
		add_filter( 'posts_clauses', array( $this, 'main_query_filter' ), 10, 2 );
	}

	/**
	 * Filter the posts clauses of the main query to suport global filters.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function main_query_filter( $args, $wp_query ) {
		if (
			! $wp_query->is_main_query() ||
			'product_query' !== $wp_query->get( 'wc_query' )
		) {
			return $args;
		}

		if ( $wp_query->get( 'filter_stock_status' ) ) {
			$stock_statuses = trim( $wp_query->get( 'filter_stock_status' ) );
			$stock_statuses = explode( ',', $stock_statuses );
			$stock_statuses = array_filter( $stock_statuses );

			$args = $this->query_clauses->add_stock_clauses( $args, $stock_statuses );
		}

		return $args;
	}
}
