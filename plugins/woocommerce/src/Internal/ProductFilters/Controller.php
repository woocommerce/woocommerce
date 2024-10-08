<?php
/**
 * MainQueryFilters class file.
 */

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * MainQueryFilters class.
 */
class Controller implements RegisterHooksInterface {
	use AccessiblePrivateMethods;

	const TRANSIENT_GROUP = 'filter_data';

	/**
	 * Instance of QueryClauses.
	 *
	 * @var QueryClauses
	 */
	private $query_clauses;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param QueryClauses $query_clauses Instance of QueryClauses.
	 *
	 * @return void
	 */
	final public function init( QueryClauses $query_clauses ): void {
		$this->filter_clauses = $query_clauses;
	}

	/**
	 * Hook into actions and filters.
	 */
	public function register() {
		self::add_filter( 'posts_clauses', array( $this, 'main_query_filter' ), 10, 2 );
		self::add_action( 'woocommerce_after_product_object_save', array( $this, 'clear_filter_data_cache' ) );
		self::add_action( 'woocommerce_delete_product_transients', array( $this, 'clear_filter_data_cache' ) );
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
			$stock_statuses = trim( $wp_query->get( 'filter_stock_status' ) );
			$stock_statuses = explode( ',', $stock_statuses );
			$stock_statuses = array_filter( $stock_statuses );

			$args = $this->filter_clauses->add_stock_clauses( $args, $stock_statuses );
		}

		return $args;
	}

	/**
	 * Invalidate all cache under filter data group.
	 */
	private function clear_filter_data_cache() {
		WC_Cache_Helper::get_transient_version( self::TRANSIENT_GROUP, true );
	}
}
