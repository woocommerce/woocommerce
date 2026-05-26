<?php
/**
 * Class WC_Settings_Tax_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Tax class.
 */
class WC_Settings_Tax_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testDox 'get_sections' returns the predefined sections as well as one section per existing tax class.
	 */
	public function test_get_sections_returns_predefined_sections_and_one_section_per_tax_class() {
		 $tax_classes = array( 'tax_class_1', 'tax_class_2' );

		StaticMockerHack::add_method_mocks(
			array(
				WC_Tax::class => array(
					'get_tax_classes' => function() use ( $tax_classes ) {
						return $tax_classes;
					},
				),
			)
		);

		$sut      = new WC_Settings_Tax();
		$sections = $sut->get_sections();

		$expected = array(
			''            => 'Tax options',
			'standard'    => 'Standard rates',
			'tax_class_1' => 'tax_class_1 rates',
			'tax_class_2' => 'tax_class_2 rates',
		);

		$this->assertEquals( $expected, $sections );
	}

	/**
	 * @testDox 'get_settings' returns the appropriate settings for the default section.
	 */
	public function test_get_settings_for_default_section() {
		$sut = new WC_Settings_Tax();

		$settings              = $sut->get_settings_for_section( '' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'tax_options'                       => array( 'title', 'sectionend' ),
			'woocommerce_prices_include_tax'    => 'radio',
			'woocommerce_tax_based_on'          => 'select',
			'woocommerce_shipping_tax_class'    => 'select',
			'woocommerce_tax_round_at_subtotal' => 'checkbox',
			'woocommerce_tax_classes'           => 'textarea',
			'woocommerce_tax_display_shop'      => 'select',
			'woocommerce_tax_display_cart'      => 'select',
			'woocommerce_price_display_suffix'  => 'text',
			'woocommerce_tax_total_display'     => 'select',
			''                                  => array( 'conflict_error', 'add_settings_slot' ),
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}

	/**
	 * @testDox 'output' invokes 'output_tax_rates' for the 'standard' section and for sections named as a tax class.
	 *
	 * @testWith ["standard"]
	 *           ["tax_class_slug"]
	 *
	 * @param string $section_name Current section name.
	 */
	public function test_output_for_standard_section_and_known_tax_class( $section_name ) {
		global $current_section;
		$current_section = $section_name;

		$output_tax_rates_invoked = false;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' => array(
					'get_tax_class_slugs' => function() {
						return array( 'tax_class_slug' );
					},
				),
			)
		);

		$sut = $this->getMockBuilder( WC_Settings_Tax::class )
					->setMethods( array( 'output_tax_rates' ) )
					->getMock();

		$sut->method( 'output_tax_rates' )->will(
			$this->returnCallback(
				function() use ( &$output_tax_rates_invoked ) {
					$output_tax_rates_invoked = true;
				}
			)
		);

		$sut->output();

		$this->assertTrue( $output_tax_rates_invoked );
	}

	/**
	 * @testDox 'output' fallbacks to 'output_fields' in WC_Admin_Settings for an unknown tax class.
	 */
	public function test_output_for_unknown_tax_class() {
		global $current_section;
		$current_section = 'foobar';

		$output_fields_in_admin_settings_invoked = false;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'output_fields' => function( $settings ) use ( &$output_fields_in_admin_settings_invoked ) {
						$output_fields_in_admin_settings_invoked = true;
					},
				),
				'WC_Tax'            => array(
					'get_tax_class_slugs' => function() {
						return array( 'tax_class_slug' );
					},
				),
			)
		);

		$sut = new WC_Settings_Tax();

		$sut->output();

		$this->assertTrue( $output_fields_in_admin_settings_invoked );
	}

	/**
	 * @testDox 'save_tax_classes' appropriately creates or deletes the tax classes.
	 */
	public function test_save_tax_classes() {
		$created = array();
		$deleted = array();

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' => array(
					'get_tax_classes'     => function() {
						return array( 'tax_1', 'tax_2', 'tax_3' );
					},
					'delete_tax_class_by' => function( $field, $name ) use ( &$deleted ) {
						$deleted[] = $name;
					},
					'create_tax_class'    => function( $name ) use ( &$created ) {
						$created[] = $name;
					},
				),
			)
		);

		$sut = new WC_Settings_Tax();

		$sut->save_tax_classes( "tax_2\ntax_3\ntax_4" );

		$this->assertEquals( array( 'tax_1' ), $deleted );
		$this->assertEquals( array( 'tax_4' ), $created );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' shows notice when tax-inclusive pricing is enabled without base rate.
	 */
	public function test_tax_configuration_validation_notice_shows_when_prices_include_tax_but_no_base_rate() {
		// Set up prices include tax option.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Mock the screen to simulate being on WooCommerce settings page.
		set_current_screen( 'woocommerce_page_wc-settings' );

		// Ensure no tax rates exist for US.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = 'US' OR tax_rate_country = ''" );

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Assert notice is displayed with country code and "standard tax rates" text.
		$this->assertStringContainsString( 'Tax configuration incomplete', $output );
		$this->assertStringContainsString( 'configure standard tax rates', $output );
		$this->assertStringContainsString( '(US)', $output );
		$this->assertStringContainsString( 'notice-warning', $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice when taxes are disabled.
	 */
	public function test_tax_configuration_validation_notice_does_not_show_when_taxes_disabled() {
		// Set up prices include tax but taxes disabled.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Mock the screen.
		set_current_screen( 'woocommerce_page_wc-settings' );

		// Ensure no tax rates exist for US.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = 'US' OR tax_rate_country = ''" );

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Assert notice is NOT displayed because taxes are disabled.
		$this->assertEmpty( $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice when base rate exists.
	 */
	public function test_tax_configuration_validation_notice_does_not_show_when_base_rate_exists() {
		// Set up prices include tax option.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Mock the screen.
		set_current_screen( 'woocommerce_page_wc-settings' );

		// Insert a tax rate for US.
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Test Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_rate_id );

		// Assert notice is NOT displayed.
		$this->assertEmpty( $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice when prices are not tax-inclusive.
	 */
	public function test_tax_configuration_validation_notice_does_not_show_when_prices_not_inclusive() {
		// Set up prices exclude tax option.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Mock the screen.
		set_current_screen( 'woocommerce_page_wc-settings' );

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Assert notice is NOT displayed.
		$this->assertEmpty( $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice on non-WooCommerce pages.
	 */
	public function test_tax_configuration_validation_notice_does_not_show_on_non_woocommerce_pages() {
		// Set up prices include tax option.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Mock the screen to simulate being on a different admin page.
		set_current_screen( 'dashboard' );

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Assert notice is NOT displayed.
		$this->assertEmpty( $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice when filtered to false.
	 */
	public function test_tax_configuration_validation_notice_respects_filter() {
		// Set up prices include tax option.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Mock the screen.
		set_current_screen( 'woocommerce_page_wc-settings' );

		// Ensure no tax rates exist for US.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = 'US' OR tax_rate_country = ''" );

		// Add filter to disable the notice.
		add_filter( 'woocommerce_show_tax_configuration_notice', '__return_false' );

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Remove filter.
		remove_filter( 'woocommerce_show_tax_configuration_notice', '__return_false' );

		// Assert notice is NOT displayed due to filter.
		$this->assertEmpty( $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice when localized tax rate exists for base country.
	 */
	public function test_tax_configuration_validation_notice_does_not_show_when_localized_rate_exists() {
		// Set up prices include tax option.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		// Base location is DE without a specific state.
		update_option( 'woocommerce_default_country', 'DE' );

		// Mock the screen.
		set_current_screen( 'woocommerce_page_wc-settings' );

		// Insert a tax rate for DE with a specific state (more localized than base).
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate_state'    => 'BE', // Berlin - more specific than base location.
				'tax_rate'          => '19.0000',
				'tax_rate_name'     => 'MwSt',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_rate_id );

		// Assert notice is NOT displayed because a rate exists for the country.
		$this->assertEmpty( $output );
	}

	/**
	 * @testDox 'tax_configuration_validation_notice' does not show notice when adjust_non_base_location_prices is disabled.
	 */
	public function test_tax_configuration_validation_notice_respects_adjust_non_base_location_prices_filter() {
		// Set up prices include tax option.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Mock the screen.
		set_current_screen( 'woocommerce_page_wc-settings' );

		// Ensure no tax rates exist for US.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = 'US' OR tax_rate_country = ''" );

		// Disable non-base location price adjustments.
		add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

		$sut = new WC_Settings_Tax();

		// Capture output.
		ob_start();
		$sut->tax_configuration_validation_notice();
		$output = ob_get_clean();

		// Remove filter.
		remove_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

		// Assert notice is NOT displayed because price adjustment is disabled.
		$this->assertEmpty( $output );
	}
}
