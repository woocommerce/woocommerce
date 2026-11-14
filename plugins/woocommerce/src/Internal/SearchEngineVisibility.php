<?php
/**
 * Search engine visibility for WooCommerce pages.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Controls robots directives and core sitemap exclusions for WooCommerce pages.
 *
 * @since 11.4.0
 * @internal
 */
class SearchEngineVisibility {

	/**
	 * Register the sitemap filter.
	 *
	 * @internal
	 */
	final public function init(): void {
		// Keep wp_robots registered in wc-template-functions.php through wc_page_no_robots() so extensions can remove it.
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'handle_wp_sitemaps_posts_query_args' ), 10, 2 );
	}

	/**
	 * Add noindex to the robots directives for transactional pages.
	 *
	 * @internal
	 *
	 * @param mixed $robots Robots directives from earlier filters.
	 * @return mixed Filtered robots directives.
	 */
	public function handle_wp_robots( $robots ) {
		return is_array( $robots ) && $this->is_noindex_page() ? wp_robots_no_robots( $robots ) : $robots;
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

		$excluded_ids = $this->get_excluded_page_ids( true );
		if ( $excluded_ids ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $excluded_ids );
		}

		return $args;
	}

	/**
	 * Check whether the current page should receive a noindex directive.
	 *
	 * @return bool
	 */
	private function is_noindex_page(): bool {
		$page_ids = $this->get_excluded_page_ids();

		return $page_ids && is_page( $page_ids );
	}

	/**
	 * Resolve transactional page IDs, optionally including the Review Order host page.
	 *
	 * @param bool $include_review_order Whether to include the redirecting host page for sitemap exclusions.
	 * @return int[]
	 */
	private function get_excluded_page_ids( bool $include_review_order = false ): array {
		$pages = array( 'cart', 'checkout', 'myaccount' );
		if ( $include_review_order ) {
			$pages[] = 'review_order';
		}

		// Prime caches to reduce future queries.
		wp_prime_option_caches(
			array_map(
				static fn( string $page ): string => 'woocommerce_' . $page . '_page_id',
				$pages
			)
		);

		$excluded_ids = array();
		foreach ( $pages as $page ) {
			$page_id = wc_get_page_id( $page );
			if ( $page_id > 0 ) {
				$excluded_ids[] = $page_id;
			}
		}

		return $excluded_ids;
	}
}
