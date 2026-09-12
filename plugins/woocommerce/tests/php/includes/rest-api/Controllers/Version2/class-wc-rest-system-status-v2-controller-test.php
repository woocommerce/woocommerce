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
		parent::tearDown();
		remove_all_filters( 'wc_get_template' );
		delete_transient( 'wc_system_status_theme_info' );
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

		$this->assertFalse( $settings['taxes_enabled'] );
		$this->assertTrue( $settings['prices_include_tax'] );
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
		$this->assertSame( DefaultCustomerAddress::GEOLOCATION_AJAX, $settings['default_customer_location'] );
	}
}
