<?php
/**
 * WooCommerce Abilities API Client (Namespaced Version)
 *
 * Simple interface for enabling WordPress Abilities API client scripts.
 * This version uses WooCommerce's PSR-4 namespace structure.
 *
 * @package Automattic\WooCommerce\Internal\AbilitiesApi
 * @version 10.4.0
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

/**
 * AbilitiesClient class.
 */
class AbilitiesClient {

	/**
	 * Whether the client has been enabled.
	 *
	 * @var bool
	 */
	private static bool $enabled = false;

	/**
	 * Whether debug mode is enabled.
	 *
	 * @var bool
	 */
	private static bool $debug = false;

	/**
	 * Enable the WordPress Abilities API client for admin pages.
	 *
	 * This is the main method external plugins should use to enable
	 * the abilities API JavaScript client.
	 *
	 * @param bool $with_debug Optional. Whether to add debug console logging. Default false.
	 * @return bool True if successfully enabled, false otherwise.
	 */
	public static function enable( bool $with_debug = false ): bool {
		// Only enable once.
		if ( self::$enabled ) {
			return true;
		}

		self::$debug = $with_debug;

		// Hook into admin_enqueue_scripts to enqueue when needed.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_for_admin' ) );

		self::$enabled = true;
		return true;
	}

	/**
	 * Internal method to handle script enqueueing.
	 */
	public static function enqueue_for_admin(): void {
		// Only enqueue on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Enqueue the script if it's registered.
		if ( wp_script_is( 'wp-abilities', 'registered' ) ) {
			wp_enqueue_script( 'wp-abilities' );

			// Add debug script if requested.
			if ( self::$debug ) {
				self::add_debug_logging();
			}
		}
	}

	/**
	 * Add debug console logging.
	 */
	private static function add_debug_logging(): void {
		$debug_script = "
		if ( typeof wp !== 'undefined' && wp.abilities ) {
			console.log('WC Abilities Client: API loaded successfully', wp.abilities);
			wp.abilities.getAbilities().then(function(abilities) {
				console.log('WC Abilities Client: Available abilities:', abilities);
			}).catch(function(error) {
				console.error('WC Abilities Client: Failed to load abilities:', error);
			});
		} else {
			console.warn('WC Abilities Client: API not available');
		}
		";

		wp_add_inline_script( 'wp-abilities', $debug_script );
	}
}
