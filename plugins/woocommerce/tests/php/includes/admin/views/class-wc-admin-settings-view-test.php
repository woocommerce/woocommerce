<?php
declare( strict_types = 1 );
/**
 * Class WC_Admin_Settings_View_Test file.
 *
 * @package WooCommerce\Tests\Admin\Views
 */

/**
 * Unit tests for admin settings views.
 */
class WC_Admin_Settings_View_Test extends WC_Unit_Test_Case {

	/**
	 * Hook priority for admin feature mocks.
	 *
	 * @var int
	 */
	private const HOOK_PRIORITY = 9999;

	/**
	 * Mocked WooCommerce Admin feature flags.
	 *
	 * @var string[]
	 */
	private $enabled_admin_features_mock = array();

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user_id = 0;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_admin_features', array( $this, 'get_mocked_admin_features' ), self::HOOK_PRIORITY );

		$this->admin_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );
		update_option( 'woocommerce_show_marketplace_suggestions', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_admin_features', array( $this, 'get_mocked_admin_features' ), self::HOOK_PRIORITY );
		unset( $_GET['zone_id'] );
		delete_option( 'woocommerce_show_marketplace_suggestions' );
		delete_option( 'woocommerce_default_country' );
		delete_option( 'woocommerce_onboarding_profile' );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should not render the generic shipping marketplace link on Shipping settings.
	 */
	public function test_shipping_marketplace_link_is_not_rendered_on_shipping_settings(): void {
		$output = $this->render_settings_view( 'shipping' );

		$this->assertStringNotContainsString(
			'wc-settings-marketplace-link',
			$output,
			'Shipping settings marketplace fallback links are rendered by the shipping recommendations component.'
		);
		$this->assertStringNotContainsString(
			'shipping-delivery-and-fulfillment',
			$output,
			'The generic Shipping settings marketplace URL should not be rendered by the PHP settings template.'
		);
	}

	/**
	 * @testdox Should keep non-shipping marketplace links when shipping smart defaults are enabled.
	 */
	public function test_non_shipping_marketplace_links_are_rendered_when_shipping_smart_defaults_are_enabled(): void {
		$this->enabled_admin_features_mock = array( 'shipping-smart-defaults' );

		$output = $this->render_settings_view( 'products' );

		$this->assertStringContainsString(
			'data-settings-tab="products"',
			$output,
			'Marketplace links for unrelated settings tabs should not be affected.'
		);
		$this->assertStringContainsString(
			'merchandising',
			$output,
			'Marketplace URLs for unrelated settings tabs should not be affected.'
		);
	}

	/**
	 * Render the admin settings view.
	 *
	 * @param string $current_tab The current settings tab.
	 * @param string $current_section The current settings section.
	 * @return string
	 */
	private function render_settings_view( string $current_tab, string $current_section = '' ): string {
		$tabs = array(
			'products' => 'Products',
			'shipping' => 'Shipping',
		);

		ob_start();
		include WC_ABSPATH . 'includes/admin/views/html-admin-settings.php';
		return (string) ob_get_clean();
	}

	/**
	 * Returns a mocked list of enabled WC Admin features.
	 *
	 * @return string[]
	 */
	public function get_mocked_admin_features(): array {
		return $this->enabled_admin_features_mock;
	}
}
