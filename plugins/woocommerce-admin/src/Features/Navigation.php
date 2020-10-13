<?php
/**
 * Navigation Experience
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

/**
 * Contains logic for the Navigation
 */
class Navigation {
	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_filter( 'woocommerce_admin_preload_options', array( $this, 'preload_options' ) );
		add_filter( 'woocommerce_admin_features', array( $this, 'maybe_remove_nav_feature' ), 0 );
	}

		/**
		 * Overwrites the allowed features array using a local `feature-config.php` file.
		 *
		 * @param array $features Array of feature slugs.
		 */
	public function maybe_remove_nav_feature( $features ) {
		if ( in_array( 'navigation', $features, true ) && 'yes' !== get_option( 'woocommerce_navigation_enabled', 'no' ) ) {
			$features = array_diff( $features, array( 'navigation' ) );
		}
		return $features;
	}

	/**
	 * Preload options to prime state of the application.
	 *
	 * @param array $options Array of options to preload.
	 * @return array
	 */
	public function preload_options( $options ) {
		$options[] = 'woocommerce_navigation_enabled';

		return $options;
	}
}
