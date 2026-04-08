<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;
/**
 * Hooks into WordPress filters to handle product filters for the main query.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class MainQueryController implements RegisterHooksInterface {

	/**
	 * Instance of QueryClauses.
	 *
	 * @var QueryClauses
	 */
	private $query_clauses;

	/**
	 * Hold the filter params.
	 *
	 * @var Params
	 */
	private $params;

	/**
	 * Initialize dependencies.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @param QueryClauses $query_clauses Instance of QueryClauses.
	 * @param Params       $params        Instance of Params.
	 *
	 * @return void
	 */
	final public function init( QueryClauses $query_clauses, Params $params ): void {
		$this->query_clauses = $query_clauses;
		$this->params        = $params;
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses_for_main_query' ), 10, 2 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'request', array( $this, 'handle_request' ) );
	}

	/**
	 * Register custom query vars for our filters. Price, stock status, and attribute query vars are
	 * already registered at WC_Query.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function add_query_vars( array $query_vars ): array {
		return array_merge( $query_vars, $this->params->get_param_keys() );
	}

	/**
	 * Cap filter parameter values early in the request lifecycle so both the product
	 * query and the filter UI see the same truncated set, preventing a mismatch.
	 *
	 * Fires via the WordPress `request` filter, before WP_Query is built.
	 *
	 * @internal
	 *
	 * @param array $query_vars The parsed request query vars.
	 * @return array
	 */
	public function handle_request( array $query_vars ): array {
		$multi_value_params = array();
		foreach ( array( 'rating', 'status', 'attribute', 'taxonomy' ) as $type ) {
			$multi_value_params = array_merge( $multi_value_params, array_values( $this->params->get_param( $type ) ) );
		}

		foreach ( $multi_value_params as $param ) {
			if ( empty( $query_vars[ $param ] ) ) {
				continue;
			}

			$values = array_values(
				array_unique(
					array_filter(
						array_map( 'sanitize_title', explode( ',', (string) $query_vars[ $param ] ) ),
						fn( $v ) => '' !== $v
					)
				)
			);

			if ( empty( $values ) ) {
				continue;
			}

			/**
			 * Filters the maximum number of values allowed per filter parameter.
			 *
			 * Reduce this value to limit combinatorial query complexity from bots.
			 * Set to 0 or a negative number to disable the cap entirely.
			 *
			 * @param int    $max_values Maximum number of values to allow. Default 5.
			 * @param string $param      The URL parameter name (e.g. "filter_color", "filter_stock_status").
			 *
			 * @since 10.8.0
			 */
			$max_values = (int) apply_filters( 'woocommerce_product_filter_max_values_per_parameter', 5, $param );

			if ( $max_values > 0 && count( $values ) > $max_values ) {
				$values = array_slice( $values, 0, $max_values );
			}

			$query_vars[ $param ] = implode( ',', $values );
		}

		return $query_vars;
	}
}
