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
	 * Enable the WordPress Abilities API client for admin pages.
	 *
	 * This is the main method external plugins should use to enable
	 * the abilities API JavaScript client.
	 *
	 * @param bool $with_debug Optional. Whether to add debug console logging. Default false.
	 * @return bool True if successfully enabled, false otherwise.
	 */
	public static function enable( bool $with_debug = false ): bool {
		// Only enable once
		if ( self::$enabled ) {
			return true;
		}

		// Check if abilities API is available
		if ( ! function_exists( 'wp_abilities_register_client_assets' ) ) {
			return false;
		}

		// Hook into admin_enqueue_scripts to register and enqueue when needed
		add_action( 'admin_enqueue_scripts', function() use ( $with_debug ) {
			self::enqueue_for_admin( $with_debug );
		} );

		self::$enabled = true;
		return true;
	}

	/**
	 * Check if the abilities API client is available.
	 *
	 * @return bool True if client is available, false otherwise.
	 */
	public static function is_available(): bool {
		return function_exists( 'wp_abilities_register_client_assets' );
	}

	/**
	 * Internal method to handle script registration and enqueueing.
	 *
	 * @param bool $with_debug Whether to add debug logging.
	 */
	private static function enqueue_for_admin( bool $with_debug ): void {
		// Only enqueue on admin pages
		if ( ! is_admin() ) {
			return;
		}

		// Register the client assets
		wp_abilities_register_client_assets();

		// Check if registration was successful
		if ( ! wp_script_is( 'wp-abilities', 'registered' ) ) {
			return;
		}

		// Enqueue the script
		wp_enqueue_script( 'wp-abilities' );

		// Add debug script if requested
		if ( $with_debug ) {
			self::add_debug_logging();
		}
	}

	/**
	 * Add debug console logging.
	 */
	private static function add_debug_logging(): void {
		if ( ! wp_script_is( 'wp-abilities', 'enqueued' ) ) {
			return;
		}

		$debug_script = "
		if ( typeof wp !== 'undefined' && wp.abilities ) {
			console.log('WC Abilities Client (Namespaced): API loaded successfully', wp.abilities);
			wp.abilities.listAbilities().then(function(abilities) {
				console.log('WC Abilities Client (Namespaced): Available abilities:', abilities);
			}).catch(function(error) {
				console.error('WC Abilities Client (Namespaced): Failed to load abilities:', error);
			});
		} else {
			console.warn('WC Abilities Client (Namespaced): API not available');
		}
		";

		wp_add_inline_script( 'wp-abilities', $debug_script );
	}
}