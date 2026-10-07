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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;
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

		CapabilityRegistry::init();

		// Register the callbacks that dispatch renewals back into the engine. Plain
		// add_action calls, safe before Action Scheduler has loaded; must run on every
		// boot (not just activation) so the hooks can fire.
		( new RenewalEngine() )->register_hooks();
		PlansController::register_hooks();
		ContractsController::register_hooks();
		( new RenewalDispatcher() )->register_hooks();

		// Deferred boot work, each on the most specific moment it needs: the schema install
		// reads options and runs dbDelta, so it waits for `init`; the recurring-action
		// enqueue needs the `as_*` functions, so it waits for `action_scheduler_init` - the
		// hook Action Scheduler fires once it is ready. Run immediately when a consumer
		// boots the engine after a hook already fired.
		if ( did_action( 'init' ) ) {
			SchemaInstaller::maybe_install();
		} else {
			add_action( 'init', array( SchemaInstaller::class, 'maybe_install' ) );
		}

		if ( did_action( 'action_scheduler_init' ) ) {
			RenewalDispatcher::ensure_scheduled();
		} else {
			add_action( 'action_scheduler_init', array( RenewalDispatcher::class, 'ensure_scheduled' ) );
		}
	}
}
