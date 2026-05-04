<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\ShippingPartnerSuggestions;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\EvaluateSuggestion;
use Automattic\WooCommerce\Admin\Features\ShippingPartnerSuggestions\DefaultShippingPartners;
use WC_Unit_Test_Case;

/**
 * DefaultShippingPartners test.
 *
 * @class DefaultShippingPartnersTest
 */
class DefaultShippingPartnersTest extends WC_Unit_Test_Case {

	/**
	 * Set things up before each test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_default_country', 'US:CA' );

		/*
		 * Required for the BaseLocationCountryRuleProcessor
		 * to not return false for "US:CA" country-state combo.
		 */
		update_option( 'woocommerce_store_address', 'foo' );

		update_option( 'active_plugins', array( 'foo/foo.php' ) );

		EvaluateSuggestion::reset_memo();
	}

	/**
	 * Tests if in a default situation there are no errors.
	 *
	 * @return void
	 */
	public function test_it_evaluates_with_no_errors() {
		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
	}

	/**
	 * Tests if WooCommerce Shipping is present by default.
	 *
	 * @return void
	 */
	public function test_wcshipping_is_present() {
		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );

		$ids = array_map(
			function ( $s ) {
				return $s->id;
			},
			$results['suggestions']
		);
		$this->assertContains( 'woocommerce-shipping', $ids );
	}

	/**
	 * Asserts WooCommerce Shipping is not recommended in unsupported countries.
	 *
	 * @return void
	 */
	public function test_wcshipping_is_absent_if_in_an_unsupported_country() {
		update_option( 'woocommerce_default_country', 'FOO' );

		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		$this->assertCount( 0, $results['errors'] );
		$this->assertCount( 0, $results['suggestions'] );
	}

	/**
	 * Asserts no extensions are recommended if WooCommerce Shipping is active.
	 *
	 * @return void
	 */
	public function test_no_extensions_are_recommended_if_woocommerce_shipping_is_active() {
		// Arrange.
		// Make sure the plugin passes as active.
		$shipping_plugin_file = 'woocommerce-shipping/woocommerce-shipping.php';
		// To pass the validation, we need to the plugin file to exist.
		$shipping_plugin_file_path = WP_PLUGIN_DIR . '/' . $shipping_plugin_file;
		self::touch( $shipping_plugin_file_path );
		update_option( 'active_plugins', array( $shipping_plugin_file ) );

		// Act.
		$specs   = DefaultShippingPartners::get_all();
		$results = EvaluateSuggestion::evaluate_specs( $specs );

		// Assert.
		$this->assertCount( 0, $results['errors'] );

		$ids = array_map(
			function ( $s ) {
				return $s->id;
			},
			$results['suggestions']
		);
		$this->assertNotContains( 'woocommerce-shipping', $ids );

		// Clean up.
		self::rmdir( dirname( $shipping_plugin_file_path ) );
		self::delete_folders( dirname( $shipping_plugin_file_path ) );
	}

	/**
	 * Asserts the WooCommerce Shipping spec includes a layout_row block with required fields.
	 *
	 * @return void
	 */
	public function test_wcshipping_has_layout_row_with_required_fields() {
		$wcs_spec = $this->get_wcshipping_spec();

		$this->assertArrayHasKey( 'layout_row', $wcs_spec );
		$row = $wcs_spec['layout_row'];

		$this->assertArrayHasKey( 'image', $row );
		$this->assertNotEmpty( $row['image'] );

		$this->assertArrayHasKey( 'image_label', $row );
		$this->assertNotEmpty( $row['image_label'] );

		$this->assertArrayHasKey( 'description', $row );
		$this->assertNotEmpty( $row['description'] );

		$this->assertArrayHasKey( 'features', $row );
		$this->assertNotEmpty( $row['features'] );

		foreach ( $row['features'] as $feature ) {
			$this->assertIsArray( $feature );
			$this->assertArrayHasKey( 'icon', $feature );
			$this->assertNotEmpty( $feature['icon'] );
			$this->assertArrayHasKey( 'description', $feature );
			$this->assertNotEmpty( $feature['description'] );
		}
	}

	/**
	 * Asserts the WooCommerce Shipping spec declares both row and column layouts as available.
	 *
	 * @return void
	 */
	public function test_wcshipping_available_layouts_includes_row_and_column() {
		$wcs_spec = $this->get_wcshipping_spec();

		$this->assertArrayHasKey( 'available_layouts', $wcs_spec );
		$this->assertContains( 'row', $wcs_spec['available_layouts'] );
		$this->assertContains( 'column', $wcs_spec['available_layouts'] );
	}

	/**
	 * Returns the WooCommerce Shipping raw spec array, failing the test if not found.
	 *
	 * @return array
	 */
	private function get_wcshipping_spec(): array {
		$specs = DefaultShippingPartners::get_all();

		foreach ( $specs as $spec ) {
			if ( 'woocommerce-shipping' === $spec['id'] ) {
				return $spec;
			}
		}

		$this->fail( 'WooCommerce Shipping spec not found.' );
	}
}
