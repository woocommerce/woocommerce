<?php
namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Admin\WCAdminHelper;

/**
 * Provides helper methods for coming soon functionality.
 */
class ComingSoonHelper {

	/**
	 * Returns true when the entire site is live.
	 */
	public function is_site_live(): bool {
		return 'yes' !== get_option( 'woocommerce_coming_soon' );
	}

	/**
	 * Returns true when the entire site is coming soon mode.
	 */
	public function is_site_coming_soon(): bool {
		return 'yes' === get_option( 'woocommerce_coming_soon' ) && 'yes' !== get_option( 'woocommerce_store_pages_only' );
	}

	/**
	 * Returns true when only the store pages are in coming soon mode.
	 */
	public function is_store_coming_soon(): bool {
		return 'yes' === get_option( 'woocommerce_coming_soon' ) && 'yes' === get_option( 'woocommerce_store_pages_only' );
	}

	/**
	 * Return true if the current page should be shown in coming soon mode.
	 */
	public function is_current_page_coming_soon(): bool {
		// Early exit if coming soon mode not active.
		if ( $this->is_site_live() ) {
			return false;
		}

		if ( $this->is_site_coming_soon() ) {
			return true;
		}

		// Check the current page is a store page when in "store coming soon" mode.
		if ( $this->is_store_coming_soon() && WCAdminHelper::is_current_page_store_page() ) {
			return true;
		}

		// Default to false.
		return false;
	}
}
