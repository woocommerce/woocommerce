<?php
/**
 * Bootstrap test.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap
 */
class BootstrapTest extends \WC_Unit_Test_Case {

	/**
	 * The feature id must be registered as an experimental, disabled-by-default feature.
	 */
	public function test_feature_is_registered() {
		$controller = wc_get_container()->get( FeaturesController::class );
		$features   = $controller->get_features( true );

		$this->assertArrayHasKey( 'navigation_v2', $features );
		$this->assertTrue( $features['navigation_v2']['is_experimental'] );
		$this->assertFalse( $controller->feature_is_enabled( 'navigation_v2' ) );
	}

	/**
	 * Bootstrap registers the feature-registration hook and the init gateway.
	 * The init hook must fire before admin_menu — see Bootstrap's constructor
	 * note. It does NOT register admin_menu or admin_enqueue_scripts itself —
	 * those live in Menu_Reconciler and Assets, which only instantiate when
	 * boot_when_enabled() runs with the flag on.
	 */
	public function test_bootstrap_registers_feature_and_init_hooks() {
		wc_get_container()->get( Bootstrap::class );

		$this->assertNotFalse( has_action( 'woocommerce_register_feature_definitions' ) );
		$this->assertNotFalse( has_action( 'init' ) );
	}

	/**
	 * boot_when_enabled() is idempotent with respect to no-op: calling it
	 * repeatedly with the flag off doesn't error and doesn't accumulate hooks.
	 */
	public function test_boot_when_enabled_is_safe_to_call_with_flag_off() {
		update_option( 'woocommerce_feature_navigation_v2_enabled', 'no' );

		$hooks_before = count( $GLOBALS['wp_filter']['admin_menu']->callbacks ?? array() );

		$bootstrap = wc_get_container()->get( Bootstrap::class );
		$bootstrap->boot_when_enabled();
		$bootstrap->boot_when_enabled();

		$hooks_after = count( $GLOBALS['wp_filter']['admin_menu']->callbacks ?? array() );

		$this->assertSame(
			$hooks_before,
			$hooks_after,
			'Flag-off boot_when_enabled must not register admin_menu hooks.'
		);
	}
}
