<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;

use WP_Query;

/**
 * Utility methods used for the Product Collection block.
 * {@internal This class and its methods are not intended for public use.}
 */
class Utils {

	/**
	 * Prepare and execute a query for the Product Collection block.
	 * This method is used by the Product Collection block and the No Results block.
	 *
	 * @param WP_Block $block Block instance.
	 */
	public static function prepare_and_execute_query( $block ) {
		$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
		// phpcs:ignore WordPress.Security.NonceVerification
		$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];

		// Use global query if needed.
		$use_global_query = ( isset( $block->context['query']['inherit'] ) && $block->context['query']['inherit'] );
		if ( $use_global_query ) {
			global $wp_query;
			$query = clone $wp_query;
		} else {
			$query_args = build_query_vars_from_query_block( $block, $page );
			$query      = new WP_Query( $query_args );
		}

		return $query;
	}

	/**
	 * Helper function that constructs a WP_Query args array from
	 * a Product Collection or global query.
	 *
	 * @param WP_Block $block Block instance.
	 * @param int      $page  Current query's page.
	 *
	 * @return array Returns the constructed WP_Query arguments.
	 */
	public static function get_query_vars( $block, $page ) {
		if ( ! empty( $block->context['query'] ) && ! $block->context['query']['inherit'] ) {
			return build_query_vars_from_query_block( $block, $page );
		}

		global $wp_query;
		return array_filter( $wp_query->query_vars );
	}

	/**
	 * Remove query array from tax or meta query by searching for arrays that
	 * contain exact key => value pair.
	 *
	 * @param array  $queries tax_query or meta_query.
	 * @param string $key     Array key to search for.
	 * @param mixed  $value   Value to compare with search result.
	 *
	 * @return array
	 */
	public static function remove_query_array( $queries, $key, $value ) {
		if ( ! is_array( $queries ) || empty( $queries ) ) {
			return $queries;
		}

		foreach ( $queries as $query_key => $query ) {
			if ( isset( $query[ $key ] ) && $query[ $key ] === $value ) {
				unset( $queries[ $query_key ] );
			}

			if ( isset( $query['relation'] ) || ! isset( $query[ $key ] ) ) {
				$queries[ $query_key ] = self::remove_query_array( $query, $key, $value );
			}
		}

		return self::remove_empty_array_recursive( $queries );
	}

	/**
	 * Parse WP Query's front-end context for the Product Collection block.
	 *
	 * The sourceData structure depends on the context type as follows:
	 * - site:    [ ]
	 * - order:   [ 'orderId'    => int ]
	 * - cart:    [ 'productIds' => int[] ]
	 * - archive: [ 'taxonomy'   => string, 'termId' => int ]
	 * - product: [ 'productId'  => int ]
	 *
	 * @return array $context {
	 *     @type string  $type        The context type. Possible values are 'site', 'order', 'cart', 'archive', 'product'.
	 *     @type array   $sourceData  The context source data. Can be the product ID of the viewed product, the order ID of the current order, etc.
	 * }
	 */
	public static function parse_frontend_location_context() {
		global $wp_query;

		// Default context.
		// Hint: The Shop page uses the default context.
		$type        = 'site';
		$source_data = array();

		if ( ! ( $wp_query instanceof WP_Query ) ) {

			return array(
				'type'       => $type,
				'sourceData' => $source_data,
			);
		}

		// As more areas are blockified, expected future contexts include:
		// - is_checkout_pay_page().
		// - is_view_order_page().
		if ( is_order_received_page() ) {

			$type        = 'order';
			$source_data = array( 'orderId' => absint( $wp_query->query_vars['order-received'] ) );

		} elseif ( ( is_cart() || is_checkout() ) && isset( WC()->cart ) && is_a( WC()->cart, 'WC_Cart' ) ) {

			$type  = 'cart';
			$items = array();
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( ! isset( $cart_item['product_id'] ) ) {
					continue;
				}

				$items[] = absint( $cart_item['product_id'] );
			}
			$items       = array_unique( array_filter( $items ) );
			$source_data = array( 'productIds' => $items );

		} elseif ( is_product_taxonomy() ) {

			$source      = $wp_query->get_queried_object();
			$is_valid    = is_a( $source, 'WP_Term' );
			$taxonomy    = $is_valid ? $source->taxonomy : '';
			$term_id     = $is_valid ? $source->term_id : '';
			$type        = 'archive';
			$source_data = array(
				'taxonomy' => wc_clean( $taxonomy ),
				'termId'   => absint( $term_id ),
			);

		} elseif ( is_product() ) {

			$source      = $wp_query->get_queried_object();
			$product_id  = is_a( $source, 'WP_Post' ) ? absint( $source->ID ) : 0;
			$type        = 'product';
			$source_data = array( 'productId' => $product_id );
		}

		$context = array(
			'type'       => $type,
			'sourceData' => $source_data,
		);

		return $context;
	}

	/**
	 * Remove falsy item from array, recursively.
	 *
	 * @param array $array The input array to filter.
	 */
	private static function remove_empty_array_recursive( $array ) {
		$array = array_filter( $array );
		foreach ( $array as $key => $item ) {
			if ( is_array( $item ) ) {
				$array[ $key ] = self::remove_empty_array_recursive( $item );
			}
		}
		return $array;
	}
}
