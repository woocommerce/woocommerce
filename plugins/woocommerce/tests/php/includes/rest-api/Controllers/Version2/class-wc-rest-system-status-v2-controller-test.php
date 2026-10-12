<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\{ DefaultCustomerAddress, TaxBasedOn, TaxDisplayMode };

/**
 * Tests for WC_REST_System_Status_V2_Controller.
 *
 * @since 10.6.0
 */
class WC_REST_System_Status_V2_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_REST_System_Status_V2_Controller
	 */
	private $sut;

	/**
	 * Plugin installed from the sample-woo-plugin.zip test fixture.
	 *
	 * @var string
	 */
	private const TEST_PLUGIN_FILE = 'sample-woo-plugin/sample-woo-plugin.php';

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_REST_System_Status_V2_Controller();
		delete_transient( 'wc_system_status_theme_info' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			remove_all_filters( 'wc_get_template' );
			delete_transient( 'wc_system_status_theme_info' );

			if ( file_exists( WP_PLUGIN_DIR . '/' . self::TEST_PLUGIN_FILE ) ) {
				delete_plugins( array( self::TEST_PLUGIN_FILE ) );
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should detect template override via wc_get_template filter.
	 */
	public function test_get_theme_info_detects_wc_get_template_filter_override(): void {
		$template_to_override = 'cart/cart.php';
		$override_path        = WC()->plugin_path() . '/includes/class-woocommerce.php';

		add_filter(
			'wc_get_template',
			function ( $template, $template_name ) use ( $template_to_override, $override_path ) {
				if ( $template_to_override === $template_name ) {
					return $override_path;
				}
				return $template;
			},
			10,
			2
		);

		$theme_info = $this->sut->get_theme_info();

		$override_files = array_column( $theme_info['overrides'], 'file' );
		$this->assertContains(
			str_replace( ABSPATH, '', $override_path ),
			$override_files,
			'Template overridden via wc_get_template filter should appear in overrides'
		);
	}

	/**
	 * @testdox Should return tax settings used for troubleshooting.
	 */
	public function test_get_settings_returns_tax_settings(): void {
		global $wpdb;

		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_based_on', TaxBasedOn::BILLING );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		update_option( 'woocommerce_tax_display_shop', TaxDisplayMode::INCLUSIVE );
		update_option( 'woocommerce_tax_display_cart', TaxDisplayMode::EXCLUSIVE );
		update_option( 'woocommerce_price_display_suffix', 'including tax' );
		update_option( 'woocommerce_tax_total_display', 'single' );
		update_option( 'woocommerce_default_country', 'IN:MH' );
		update_option( 'woocommerce_default_customer_address', DefaultCustomerAddress::GEOLOCATION_AJAX );
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_prices_include_tax', '__return_false' );

		$tax_class = WC_Tax::create_tax_class( 'Diagnostic rate' );
		if ( is_wp_error( $tax_class ) ) {
			$this->fail( 'The diagnostic tax class should be created.' );
		}
		$tax_class_slug = $tax_class['slug'];
		update_option( 'woocommerce_shipping_tax_class', $tax_class_slug );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tax_rate_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates" );
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'IN',
				'tax_rate_state'    => 'MH',
				'tax_rate'          => '18.0000',
				'tax_rate_name'     => 'GST',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => $tax_class_slug,
			)
		);

		$settings = $this->sut->get_settings();

		$this->assertTrue( $settings['taxes_enabled'] );
		$this->assertFalse( $settings['prices_include_tax'] );
		$this->assertSame( TaxBasedOn::BILLING, $settings['tax_based_on'] );
		$this->assertSame( $tax_class_slug, $settings['shipping_tax_class'] );
		$this->assertTrue( $settings['tax_round_at_subtotal'] );
		$this->assertContains( 'Diagnostic rate', $settings['additional_tax_classes'] );
		$this->assertSame( TaxDisplayMode::INCLUSIVE, $settings['tax_display_shop'] );
		$this->assertSame( TaxDisplayMode::EXCLUSIVE, $settings['tax_display_cart'] );
		$this->assertSame( 'including tax', $settings['price_display_suffix'] );
		$this->assertSame( 'single', $settings['tax_total_display'] );
		$this->assertSame( $tax_rate_count + 1, $settings['tax_rate_count'] );
		$this->assertSame( 'IN', $settings['store_base_country'] );
		$this->assertSame( 'MH', $settings['store_base_state'] );
		$this->assertSame( DefaultCustomerAddress::GEOLOCATION_AJAX, $settings['default_customer_location'] );
	}

	/**
	 * @testdox Should not list a plugin in inactive_plugins after it has been deleted.
	 */
	public function test_get_inactive_plugins_excludes_deleted_plugin(): void {
		$this->login_as_administrator();
		$this->install_test_plugin();

		$this->assertContains(
			self::TEST_PLUGIN_FILE,
			$this->get_inactive_plugin_files_from_endpoint(),
			'Precondition: the installed but inactive plugin should be listed'
		);

		$this->assertTrue( delete_plugins( array( self::TEST_PLUGIN_FILE ) ), 'Precondition: the plugin should be deleted' );
		// The non-persistent `plugins` cache group would be empty on the next real request.
		wp_clean_plugins_cache( false );

		$this->assertNotContains(
			self::TEST_PLUGIN_FILE,
			$this->get_inactive_plugin_files_from_endpoint(),
			'A deleted plugin should not be served from the cached inactive plugins list'
		);
	}

	/**
	 * @testdox Should list a plugin in inactive_plugins after it has been installed.
	 */
	public function test_get_inactive_plugins_includes_newly_installed_plugin(): void {
		$this->login_as_administrator();

		$this->assertNotContains(
			self::TEST_PLUGIN_FILE,
			$this->get_inactive_plugin_files_from_endpoint(),
			'Precondition: the plugin should not be listed before it is installed'
		);

		$this->install_test_plugin();

		$this->assertContains(
			self::TEST_PLUGIN_FILE,
			$this->get_inactive_plugin_files_from_endpoint(),
			'A newly installed plugin should not be missing from the cached inactive plugins list'
		);
	}

	/**
	 * Install the sample-woo-plugin.zip fixture through the plugin upgrader.
	 */
	private function install_test_plugin(): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/sample-woo-plugin.zip' );

		$this->assertTrue( $result, 'Precondition: the test plugin should be installed' );
	}

	/**
	 * Fetch the inactive plugin files reported by the system status endpoint.
	 *
	 * @return string[]
	 */
	private function get_inactive_plugin_files_from_endpoint(): array {
		$response = $this->do_rest_get_request( 'system_status', array( '_fields' => 'inactive_plugins' ) );
		$this->assertSame( 200, $response->get_status() );

		return array_column( $response->get_data()['inactive_plugins'], 'plugin' );
	}
}
