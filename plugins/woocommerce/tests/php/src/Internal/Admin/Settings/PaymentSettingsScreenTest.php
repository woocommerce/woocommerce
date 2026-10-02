<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreen;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentSettingsScreen class.
 */
class PaymentSettingsScreenTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PaymentSettingsScreen
	 */
	private $sut;

	/**
	 * The admin submenu before the test.
	 *
	 * @var array|null
	 */
	private $original_submenu;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $submenu;
		parent::setUp();
		$this->sut              = new PaymentSettingsScreen();
		$this->original_submenu = $submenu;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $submenu;
		try {
			unset( $_GET['page'] );
			$submenu = $this->original_submenu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the value saved in setUp().
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should not be available while the feature flag is off.
	 */
	public function test_is_not_available_without_feature_flag(): void {
		add_filter(
			'woocommerce_admin_features',
			fn( $features ) => array_values( array_diff( $features, array( 'payment-settings-screen' ) ) )
		);

		$this->assertFalse( $this->sut->is_available(), 'The screen should be unavailable when its flag is off' );
	}

	/**
	 * @testdox Should not register the admin page when the screen is unavailable.
	 */
	public function test_does_not_register_admin_page_when_unavailable(): void {
		add_filter(
			'woocommerce_admin_features',
			fn( $features ) => array_values( array_diff( $features, array( 'payment-settings-screen' ) ) )
		);
		set_current_screen( 'dashboard' );

		$this->sut->handle_init();

		$this->assertFalse( has_action( 'admin_menu', array( $this->sut, 'handle_admin_menu' ) ), 'The admin page should not be registered' );
	}

	/**
	 * @testdox Should remove only its own item from the WooCommerce menu.
	 */
	public function test_removes_own_menu_item(): void {
		global $submenu;
		$submenu['woocommerce'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored in tearDown().
			array( 'Settings', 'manage_woocommerce', 'wc-settings' ),
			array( 'Payment settings', 'manage_woocommerce', PaymentSettingsScreen::PAGE_SLUG ),
		);

		$this->sut->handle_admin_head();

		$slugs = array_column( $submenu['woocommerce'], 2 );
		$this->assertNotContains( PaymentSettingsScreen::PAGE_SLUG, $slugs, 'The screen should not have its own menu item' );
		$this->assertContains( 'wc-settings', $slugs, 'Other WooCommerce menu items should stay' );
	}

	/**
	 * @testdox Should highlight WooCommerce > Settings only on the payment settings screen.
	 *
	 * @testWith ["wc-payment-settings-wp-admin", "wc-settings"]
	 *           ["wc-settings", "other-submenu"]
	 *
	 * @param string $page             The requested admin page.
	 * @param string $expected_submenu The expected submenu file.
	 */
	public function test_highlights_settings_menu_on_screen( string $page, string $expected_submenu ): void {
		$_GET['page'] = $page;

		$this->assertSame( $expected_submenu, $this->sut->handle_submenu_file( 'other-submenu' ) );
	}
}
