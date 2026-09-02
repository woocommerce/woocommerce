<?php
/**
 * ProductTermCacheInvalidator class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Invalidates cached product terms when taxonomy caches are cleaned.
 *
 * @since 11.2.0
 *
 * @internal
 */
class ProductTermCacheInvalidator {
	/**
	 * Register cache invalidation hooks.
	 *
	 * @since 11.2.0
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'clean_taxonomy_cache', array( $this, 'handle_clean_taxonomy_cache' ) );
	}

	/**
	 * Invalidate cached product terms after a taxonomy cache is cleaned.
	 *
	 * @since 11.2.0
	 *
	 * @internal
	 *
	 * @param mixed $taxonomy Taxonomy name.
	 */
	public function handle_clean_taxonomy_cache( $taxonomy ): void {
		if ( ! is_string( $taxonomy ) || ! is_object_in_taxonomy( 'product', $taxonomy ) ) {
			return;
		}

		WC_Cache_Helper::invalidate_cache_group( 'product_terms_' . $taxonomy );
	}
}
