<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use WP_Query;

/**
 * Utility methods used for the Product Collection block.
 * {@internal This class and its methods are not intended for public use.}
 */
class ProductCollectionUtils {
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

			/**
			 * We are adding these extra query arguments to be used in `posts_clauses`
			 * because there are 2 special edge cases we wanna handle for Price range filter:
			 * Case 1: Prices excluding tax are displayed including tax
			 * Case 2: Prices including tax are displayed excluding tax
			 *
			 * Both of these cases require us to modify SQL query to get the correct results.
			 */
			$query_args['isProductCollection'] = true;
			$price_range                       = $block->context['query']['priceRange'] ?? null;
			if ( $price_range ) {
				$query_args['priceRange'] = $price_range;
			}

			$query = new WP_Query( $query_args );
		}

		return $query;
	}
}
