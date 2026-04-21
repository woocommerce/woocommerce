<?php
/**
 * Navigation v2 bootstrap.
 *
 * Registers the feature flag and, when enabled, wires up the reconciler,
 * renderer, assets, and telemetry.
 */

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
	 */
	public function __construct() {
		add_action( 'woocommerce_register_feature_definitions', array( $this, 'register_feature' ) );
		add_action( 'admin_init', array( $this, 'boot_when_enabled' ) );
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
	 * When the flag is enabled, instantiate the reconciler, renderer, assets,
	 * and telemetry. Each of those classes registers its own hooks.
	 *
	 * Called on admin_init so the feature flag is readable and translations are loaded.
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

		new Menu_Reconciler();
		new Renderer();
		new Assets();
		new Telemetry();
	}
}
