<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Process the query data for filtering purposes.
 *
 * Do not delete this file until https://github.com/woocommerce/woocommerce/issues/52311 is resolved.
 * The upgrade flow can still load stale autoloader manifests that point to this file.
 *
 * @deprecated 11.0.0 Use QueryClauses and FilterDataProvider instead. This class will be removed in WooCommerce 12.0.
 */
final class QueryFilters {
	/**
	 * Constructor.
	 *
	 * @deprecated 11.0.0 Use QueryClauses and FilterDataProvider instead. This class will be removed in WooCommerce 12.0.
	 */
	public function __construct() {
		wc_deprecated_function(
			__CLASS__,
			'11.0.0',
			QueryClauses::class . ' and ' . FilterDataProvider::class . '. This class will be removed in WooCommerce 12.0'
		);
	}

	/**
	 * Initialization method.
	 *
	 * @internal
	 */
	public function init() {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			QueryClauses::class . ' and ' . FilterDataProvider::class . '. This class will be removed in WooCommerce 12.0'
		);
	}

	/**
	 * Filter the posts clauses of the main query to support global filters.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function main_query_filter( $args, $wp_query ) {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			QueryClauses::class . '::add_query_clauses_for_main_query. This class will be removed in WooCommerce 12.0'
		);

		return wc_get_container()->get( QueryClauses::class )->add_query_clauses_for_main_query( $args, $wp_query );
	}

	/**
	 * Add conditional query clauses based on the filter params in query vars.
	 *
	 * @param array     $args     Query args.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return array
	 */
	public function add_query_clauses( $args, $wp_query ) {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			QueryClauses::class . '::add_query_clauses. This class will be removed in WooCommerce 12.0'
		);

		return wc_get_container()->get( QueryClauses::class )->add_query_clauses( $args, $wp_query );
	}

	/**
	 * Get price data for current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return object
	 */
	public function get_filtered_price( $query_vars ) {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			FilterDataProvider::class . '::with( ' . QueryClauses::class . ' )->get_filtered_price. This class will be removed in WooCommerce 12.0'
		);

		$container = wc_get_container();

		return (object) $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_filtered_price( $query_vars );
	}

	/**
	 * Get stock status counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array status=>count pairs.
	 */
	public function get_stock_status_counts( $query_vars ) {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			FilterDataProvider::class . '::with( ' . QueryClauses::class . ' )->get_stock_status_counts. This class will be removed in WooCommerce 12.0'
		);

		$container = wc_get_container();

		return $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_stock_status_counts( $query_vars, array_keys( wc_get_product_stock_status_options() ) );
	}

	/**
	 * Get rating counts for the current products.
	 *
	 * @param array $query_vars The WP_Query arguments.
	 * @return array rating=>count pairs.
	 */
	public function get_rating_counts( $query_vars ) {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			FilterDataProvider::class . '::with( ' . QueryClauses::class . ' )->get_rating_counts. This class will be removed in WooCommerce 12.0'
		);

		$container = wc_get_container();

		return $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_rating_counts( $query_vars );
	}

	/**
	 * Get attribute counts for the current products.
	 *
	 * @param array  $query_vars         The WP_Query arguments.
	 * @param string $attribute_to_count Attribute taxonomy name.
	 * @return array termId=>count pairs.
	 */
	public function get_attribute_counts( $query_vars, $attribute_to_count ) {
		wc_deprecated_function(
			__METHOD__,
			'11.0.0',
			FilterDataProvider::class . '::with( ' . QueryClauses::class . ' )->get_attribute_counts. This class will be removed in WooCommerce 12.0'
		);

		$container = wc_get_container();

		return $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_attribute_counts( $query_vars, $attribute_to_count );
	}
}
