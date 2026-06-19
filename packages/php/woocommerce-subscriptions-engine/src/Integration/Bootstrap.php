<?php
/**
 * Bootstrap - wires the engine's integration layer into WordPress.
 *
 * The engine is bundled rather than independently activated, so it cannot rely
 * on a plugin activation hook to install its schema. Instead it performs a
 * version-gated install check on boot: cheap in the common case (a single
 * option read) and self-healing if the tables are missing or behind.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration;

use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

defined( 'ABSPATH' ) || exit;

/**
 * Integration-layer bootstrap.
 */
final class Bootstrap {

	/**
	 * Whether hooks have already been registered, to keep init idempotent when
	 * more than one consumer boots the engine in the same request.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register the engine's WordPress hooks.
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		if ( did_action( 'init' ) ) {
			self::maybe_install_schema();
		} else {
			add_action( 'init', array( __CLASS__, 'maybe_install_schema' ) );
		}
	}

	/**
	 * Install or upgrade the engine schema when it is missing or behind.
	 */
	public static function maybe_install_schema(): void {
		SchemaInstaller::maybe_install();
	}
}
