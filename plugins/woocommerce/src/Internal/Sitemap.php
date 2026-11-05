<?php
/**
 * Core WordPress sitemap integration.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Excludes non-indexable WooCommerce pages from the core WordPress sitemap.
 *
 * @since 11.4.0
 * @internal
 */
class Sitemap {

	/**
	 * Register the sitemap query filter.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'handle_wp_sitemaps_posts_query_args' ), 10, 2 );
	}

	/**
	 * Exclude pages that WooCommerce marks noindex or redirects on direct visits.
	 *
	 * @internal
	 *
	 * @param mixed  $args      Query arguments from earlier filters.
	 * @param string $post_type Sitemap post type.
	 * @return mixed Filtered query arguments.
	 */
	public function handle_wp_sitemaps_posts_query_args( $args, $post_type ) {
		if ( 'page' !== $post_type || ! is_array( $args ) ) {
			return $args;
		}

		// Prime caches to reduce future queries.
		wp_prime_option_caches(
			array(
				'woocommerce_cart_page_id',
				'woocommerce_checkout_page_id',
				'woocommerce_myaccount_page_id',
				'woocommerce_review_order_page_id',
			)
		);

		$excluded_ids = array();
		foreach ( array( 'cart', 'checkout', 'myaccount', 'review_order' ) as $page ) {
			$page_id = wc_get_page_id( $page );
			if ( $page_id > 0 ) {
				$excluded_ids[] = $page_id;
			}
		}

		if ( $excluded_ids ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $excluded_ids );
		}

		return $args;
	}
}
