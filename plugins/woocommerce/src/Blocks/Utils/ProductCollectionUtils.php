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
			$query      = new WP_Query( $query_args );
		}

		return $query;
	}
}
