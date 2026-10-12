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

use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ContractCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\PlansController;
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

		ContractCapabilities::register_hooks();
		PlansController::register_hooks();
		ContractsController::register_hooks();

		// The schema install reads options and runs dbDelta, so it waits for `init`; run it
		// immediately when a consumer boots the engine after `init` already fired.
		if ( did_action( 'init' ) ) {
			SchemaInstaller::maybe_install();
		} else {
			add_action( 'init', array( SchemaInstaller::class, 'maybe_install' ) );
		}
	}
}
