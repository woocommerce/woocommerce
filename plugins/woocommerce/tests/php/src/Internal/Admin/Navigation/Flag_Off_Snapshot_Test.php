<?php

declare( strict_types = 1 );


namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap
 *
 * The empirical proof that flag-off = uninstalled. If this test fails, the
 * navigation_v2 feature has leaked a side effect into the flag-off path.
 * Fix it before shipping.
 */
class Flag_Off_Snapshot_Test extends \WC_Unit_Test_Case {

	/** @var mixed */
	private $option_backup;

	/** @var mixed */
	private $menu_backup;

	public function setUp(): void {
		parent::setUp();
		$this->option_backup = get_option( 'woocommerce_feature_navigation_v2_enabled', null );
		$this->menu_backup   = $GLOBALS['menu'] ?? null;
		// Ensure flag is explicitly off.
		update_option( 'woocommerce_feature_navigation_v2_enabled', 'no' );
	}

	public function tearDown(): void {
		if ( null === $this->option_backup ) {
			delete_option( 'woocommerce_feature_navigation_v2_enabled' );
		} else {
			update_option( 'woocommerce_feature_navigation_v2_enabled', $this->option_backup );
		}
		if ( null === $this->menu_backup ) {
			unset( $GLOBALS['menu'] );
		} else {
			$GLOBALS['menu'] = $this->menu_backup;
		}
		parent::tearDown();
	}

	/**
	 * Fire admin_menu with the flag off and confirm the reconciler did NOT run.
	 *
	 * Practical implementation of spec §9.4: we can't uninstall the feature
	 * mid-test (the container already holds a Bootstrap instance), so we prove
	 * the no-op property by asserting the observable side effect never
	 * happens: rehomed Woo top-level slugs remain present in $menu.
	 */
	public function test_flag_off_leaves_menu_untouched() {
		global $menu;

		// Seed $menu with Woo-related top-level entries the reconciler would remove.
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce',                'wc-icon', '' ),
			array( 'Products',    'read', 'edit.php?post_type=product', '',       '' ),
			array( 'Marketing',   'read', 'woocommerce-marketing',      '',       '' ),
			array( 'Plugins',     'read', 'plugins.php',                '',       '' ),
		);

		// Force Bootstrap to exist (as it would in production) and call the
		// boot_when_enabled() gateway directly — equivalent to the init hook
		// firing. Calling do_action('init') is avoided because it would
		// re-register blocks and trigger unrelated "already registered" notices
		// that abort the test via WC_Unit_Test_Case's notice guard.
		$bootstrap = wc_get_container()->get( Bootstrap::class );
		$bootstrap->boot_when_enabled();

		// Fire admin_menu — if the reconciler had been instantiated it would
		// have added a hook here and would now remove the Woo top-level items.
		do_action( 'admin_menu' );

		$slugs = array_column( $menu, 2 );

		// All four remain — reconciler never ran.
		$this->assertContains( 'woocommerce', $slugs );
		$this->assertContains( 'edit.php?post_type=product', $slugs );
		$this->assertContains( 'woocommerce-marketing', $slugs );
		$this->assertContains( 'plugins.php', $slugs );
	}
}
