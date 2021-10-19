<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions\Init as RemoteFreeExtensions;

/**
 * Marketing Task
 */
class Marketing {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'          => 'marketing',
			'title'       => __( 'Set up marketing tools', 'woocommerce' ),
			'content'     => __(
				'Add recommended marketing tools to reach new customers and grow your business',
				'woocommerce'
			),
			'is_complete' => self::has_installed_extensions(),
			'can_view'    => Features::is_enabled( 'remote-free-extensions' ) && count( self::get_bundles() ) > 0,
			'time'        => __( '1 minute', 'woocommerce' ),
		);
	}

	/**
	 * Get the marketing bundles.
	 *
	 * @return array
	 */
	public static function get_bundles() {
		return RemoteFreeExtensions::get_extensions(
			array(
				'reach',
				'grow',
			)
		);
	}

	/**
	 * Check if the store has installed marketing extensions.
	 *
	 * @return bool
	 */
	public static function has_installed_extensions() {
		$bundles = self::get_bundles();

		return array_reduce(
			$bundles,
			function( $has_installed, $bundle ) {
				if ( $has_installed ) {
					return true;
				}
				foreach ( $bundle['plugins'] as $plugin ) {
					if ( $plugin->is_installed ) {
						return true;
					}
				}
				return false;
			},
			false
		);
	}
}
