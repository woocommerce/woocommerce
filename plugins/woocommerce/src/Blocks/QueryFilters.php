<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClausesGenerator;
use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterDataProvider;

/**
 * Process the query data for filtering purposes.
 *
 * @deprecated 8.7.0
 */
final class QueryFilters {
	/**
	 * Initialization method.
	 *
	 * @deprecated 8.7.0 No longer used by internal code and not recommended.
	 * @internal
	 */
	public function init() {
		wc_deprecated_function( 'QueryFilters::init', '8.7.0' );
	}

	/**
	 * Filter the posts clauses of the main query to suport global filters.
	 *
	 * @deprecated 8.7.0 No longer used by internal code and not recommended.
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function main_query_filter( $args, $wp_query ) {
		wc_deprecated_function( 'QueryFilters::main_query_filter', '8.7.0' );
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

			$args = wc_get_container()->get( FilterClausesGenerator::class )->add_stock_clauses( $args, $wp_query );
		}

		return $args;
	}

	/**
	 * Add conditional query clauses based on the filter params in query vars.
	 *
	 * @deprecated 8.7.0 Use FilterClausesGenerator::add_query_clauses instead.
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses( $args, $wp_query ) {
		wc_deprecated_function( 'QueryFilters::add_query_clauses', '8.7.0', 'FilterClausesGenerator::add_query_clauses' );
		return wc_get_container()->get( FilterClausesGenerator::class )->add_query_clauses( $args, $wp_query );
	}

	/**
	 * Get price data for current products.
	 *
	 * @deprecated 8.7.0 Use FilterDataProvider::get_filtered_price instead.
	 * @param array $query_vars The WP_Query arguments.
	 * @return object
	 */
	public function get_filtered_price( $query_vars ) {
		wc_deprecated_function( 'QueryFilters::get_filtered_price', '8.7.0', 'FilterDataProvider::get_filtered_price' );
		return wc_get_container()->get( FilterDataProvider::class )->get_filtered_price( $query_vars );
	}

	/**
	 * Get stock status counts for the current products.
	 *
	 * @deprecated 8.7.0 Use FilterDataProvider::get_stock_status_counts instead.
	 * @param array $query_vars The WP_Query arguments.
	 * @return array status=>count pairs.
	 */
	public function get_stock_status_counts( $query_vars ) {
		wc_deprecated_function( 'QueryFilters::get_stock_status_counts', '8.7.0', 'FilterDataProvider::get_stock_status_counts' );
		return wc_get_container()->get( FilterDataProvider::class )->get_stock_status_counts( $query_vars );
	}

	/**
	 * Get rating counts for the current products.
	 *
	 * @deprecated 8.7.0 Use FilterDataProvider::get_rating_counts instead.
	 * @param array $query_vars The WP_Query arguments.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( $query_vars ) {
		wc_deprecated_function( 'QueryFilters::get_rating_counts', '8.7.0', 'FilterDataProvider::get_rating_counts' );
		return wc_get_container()->get( FilterDataProvider::class )->get_rating_counts( $query_vars );
	}

	/**
	 * Get attribute counts for the current products.
	 *
	 * @deprecated 8.7.0 Use FilterDataProvider::get_attribute_counts instead.
	 * @param array  $query_vars         The WP_Query arguments.
	 * @param string $attribute_to_count Attribute taxonomy name.
	 * @return array termId=>count pairs.
	 */
	public function get_attribute_counts( $query_vars, $attribute_to_count ) {
		wc_deprecated_function( 'QueryFilters::get_attribute_counts', '8.7.0', 'FilterDataProvider::get_attribute_counts' );
		return wc_get_container()->get( FilterDataProvider::class )->get_attribute_counts( $query_vars, $attribute_to_count );
	}
}
