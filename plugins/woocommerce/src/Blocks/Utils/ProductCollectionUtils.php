<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use WP_Query;

/**
 * Utility methods used for the Product Collection block.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductCollectionUtils {

	/**
	 * Parse the front-end context for the Product Collection block.
	 * 
	 * TODO: Parse block-level context
	 * TODO: Parse ancestor-level context (e.g. Single Product block.)
	 * 
	 * @param WP_Block $block Block instance.
	 * @return array Parsed context.
	 */
	public static function parse_context( $block ) {
		global $wp_query;

		// 1. Parse block-level context.
		// ...

		// 2. Parse ancestor-level context.
		// ...

		// 3. Parse global context.
		$type        = 'site';
		$source_data = array();

		 // As more areas are blockified, expected future contexts include:
		 // - is_checkout_pay_page()
		 // - is_view_order_page()
		if ( is_order_received_page() ) {

			$type        = 'order';
			$source_data = array( 'order_id' => absint( $wp_query->query_vars['order-received'] ) );

		} elseif ( ( is_cart() || is_checkout() ) && isset( WC()->cart ) && is_a( WC()->cart, 'WC_Cart' ) ) {

			$type  = 'cart';
			$items = array();
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$items[] = absint( $cart_item[ 'product_id' ] );
			}
			$items       = array_unique( array_filter( $items ) );
			$source_data = array( 'product_ids' => $items );

		} elseif ( is_shop() ) {

			$type        = 'archive';
			$source_data = array();

		} elseif ( is_product_taxonomy() ) {

			$source      = $wp_query->get_queried_object();
			$taxonomy    = is_a( $source, 'WP_Term' ) ? $source->taxonomy : '';
			$term_id     = is_a( $source, 'WP_Term' ) ? $source->term_id : '';
			$type        = 'archive';
			$source_data = array(
				'taxonomy' => wc_clean( $taxonomy ),
				'term_id'  => absint( $term_id ),
			);

		} elseif ( is_product() ) {

			$source      = $wp_query->get_queried_object();
			$product_id  = is_a( $source, 'WP_Post' ) ? absint( $source->ID ) : 0;
			$type        = 'product';
			$source_data = array( 'product_id' => $product_id );
		}

		$context = array(
			'type'        => $type,
			'source_data' => $source_data,
		);

		// Perhaps add a 3PD filter here.
		return $context;
	}

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
}
