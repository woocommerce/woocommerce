<?php
/**
 * Onboarding - set up shipping tests.
 *
 * @package WooCommerce\Tests\Onboarding-shipping-set-up
 */

use \Automattic\WooCommerce\Admin\Features\OnboardingSetUpShipping;
use \Automattic\WooCommerce\Admin\Notes\ReviewShippingSettings;

/**
 * Tests shipping set up during onboarding.
 */
class WC_Tests_OnboardingShippingSetUp extends WC_Unit_Test_Case {
	/**
	 * Verify that the zone gets created.
	 */
	public function test_zone_gets_created_when_setting_up_free_shipping() {
		update_option( 'woocommerce_default_country', 'AU:QLD' );

		OnboardingSetUpShipping::set_up_free_local_shipping();

		$zones = \WC_Shipping_Zones::get_zones();

		// There should be only one zone, with a zone name of 'Australia'.
		$this->assertEquals( 1, count( $zones ) );
		$zone = $zones[ array_keys( $zones )[0] ];
		$this->assertEquals( 'Australia', $zone['zone_name'] );

		// There should only be one zone location with a code of 'AU'.
		$this->assertEquals( 1, count( $zone['zone_locations'] ) );
		$zone_location = $zone['zone_locations'][0];
		$this->assertEquals( 'AU', $zone_location->code );

		// There should be only one shipping zone method, with a method ID of
		// 'free_shipping'.
		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$methods    = $data_store->get_methods( $zone['id'], true );
		$this->assertEquals( 1, count( $methods ) );
		$method = $methods[0];
		$this->assertEquals( 'free_shipping', $method->method_id );
	}

	/**
	 * Verify that the zone doesn't get created if there is no default
	 * country.
	 */
	public function test_zone_does_not_get_created_with_no_default_country() {
		add_filter(
			'woocommerce_get_base_location',
			array(
				$this,
				'get_empty_base_location',
			)
		);

		OnboardingSetUpShipping::set_up_free_local_shipping();

		$zones = \WC_Shipping_Zones::get_zones();

		$this->assertEquals( 0, count( $zones ) );
	}

	/**
	 * Get empty base location.
	 */
	public function get_empty_base_location() {
		return null;
	}

	/**
	 * Verify that the shipping note does not include label printing for non-us countries.
	 * Related issue: https://github.com/woocommerce/woocommerce-admin/issues/5394.
	 */
	public function test_shipping_note_does_not_include_label_printing_for_non_us_countries() {
		// Given AU:QLD as the default country.
		update_option( 'woocommerce_default_country', 'AU:QLD' );

		// When note is retrieved.
		$note = ReviewShippingSettings::get_note()->get_content();

		// Then it should not contain 'Label printing: enabled'.
		$contains_string = strpos( $note, 'Label printing: enabled' );
		$this->assertFalse( $contains_string );
	}

	/**
	 * Verify that the shipping note includes label printing for US.
	 * Related issue: https://github.com/woocommerce/woocommerce-admin/issues/5394.
	 */
	public function test_shipping_note_includes_label_printing_string_for_us() {
		// Given US:CA as the default country.
		update_option( 'woocommerce_default_country', 'US:CA' );

		// When note is retrieved.
		$note = ReviewShippingSettings::get_note()->get_content();

		// Then it should contain 'Label printing: enabled'.
		$contains_string = strpos( $note, 'Label printing: enabled' );
		$this->assertNotFalse( $contains_string );
	}
}
