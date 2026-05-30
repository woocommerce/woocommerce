<?php
/**
 * Navigation v2 bootstrap.
 *
 * Registers the feature flag and, when enabled, wires up the reconciler,
 * assets, and telemetry.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

use Automattic\WooCommerce\Enums\FeaturePluginCompatibility;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap for the nested admin navigation feature.
 */
class Bootstrap {

	public const FEATURE_ID = 'navigation_v2';

	/**
	 * Wire the feature registration.
	 *
	 * `boot_when_enabled` runs on `init` (not `admin_init`) because WordPress
	 * fires `admin_menu` *before* `admin_init` in the admin request lifecycle.
	 * If we booted on admin_init, Menu_Reconciler would register its
	 * admin_menu hook after the hook had already fired, and the reconciler
	 * would never run.
	 */
	public function __construct() {
		add_action( 'woocommerce_register_feature_definitions', array( $this, 'register_feature' ) );
		add_action( 'init', array( $this, 'boot_when_enabled' ), 20 );
	}

	/**
	 * Register the feature in the FeaturesController.
	 *
	 * @param FeaturesController $controller Controller instance.
	 */
	public function register_feature( FeaturesController $controller ): void {
		$controller->add_feature_definition(
			self::FEATURE_ID,
			__( 'Nested admin navigation', 'woocommerce' ),
			array(
				'description'                  => __(
					'Move all WooCommerce menu items under a single top-level item.',
					'woocommerce'
				),
				'is_experimental'              => true,
				'enabled_by_default'           => false,
				'disable_ui'                   => false,
				'default_plugin_compatibility' => FeaturePluginCompatibility::COMPATIBLE,
			)
		);
	}

	/**
	 * When the flag is enabled, instantiate the reconciler, assets, and
	 * telemetry. Each of those classes registers its own hooks.
	 *
	 * Called on `init` priority 20 so Menu_Reconciler's admin_menu hook lands
	 * before WordPress fires admin_menu (see constructor note).
	 *
	 * Spec §8: multisite network admin always uses the native rail — we bail
	 * before any hook registration in that context.
	 */
	public function boot_when_enabled(): void {
		if ( ! is_admin() || is_network_admin() ) {
			return;
		}

		$controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $controller->feature_is_enabled( self::FEATURE_ID ) ) {
			return;
		}

		$container = wc_get_container();
		$container->get( Menu_Reconciler::class );
		$container->get( Assets::class );
		$container->get( Telemetry::class );
		$container->get( Section_Memory::class );
		$container->get( Order_Badge::class );
	}
}
