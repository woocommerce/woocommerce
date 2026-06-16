<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyBookingsCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyBookingsCompatibilityProjectionService class.
 */
class MultiCurrencyBookingsCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Bookings compatibility hook manifest.
	 */
	public function test_projects_bookings_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyBookingsCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				'woocommerce_bookings_calculated_booking_cost',
				'woocommerce_product_get_block_cost',
				'woocommerce_product_get_cost',
				'woocommerce_product_get_display_cost',
				'woocommerce_product_booking_person_type_get_block_cost',
				'woocommerce_product_booking_person_type_get_cost',
				'woocommerce_product_get_resource_base_costs',
				'woocommerce_product_get_resource_block_costs',
				'wcpay_multi_currency_should_convert_product_price',
				'woocommerce_bookings_process_cost_rules_cost',
				'woocommerce_bookings_process_cost_rules_base_cost',
			),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame(
			array(
				'wp_ajax_wc_bookings_calculate_costs',
				'wp_ajax_nopriv_wc_bookings_calculate_costs',
			),
			array_column( $manifest['actions'], 'hook' )
		);
	}

	/**
	 * @testdox Should project Bookings registration guards.
	 */
	public function test_projects_bookings_registration_guards(): void {
		$this->assertTrue( MultiCurrencyBookingsCompatibilityProjectionService::should_register( true, false, false ) );
		$this->assertTrue( MultiCurrencyBookingsCompatibilityProjectionService::should_register( true, true, true ) );
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_register( true, true, false ) );
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_register( false, false, false ) );
	}

	/**
	 * @testdox Should project calculated booking cost conversion guards.
	 */
	public function test_projects_calculated_booking_cost_conversion_guards(): void {
		$this->assertTrue( MultiCurrencyBookingsCompatibilityProjectionService::should_adjust_calculated_booking_cost( false ) );
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_adjust_calculated_booking_cost( true ) );
	}

	/**
	 * @testdox Should project Bookings price conversion decisions.
	 */
	public function test_projects_bookings_price_conversion_decisions(): void {
		$this->assertSame( 'product', MultiCurrencyBookingsCompatibilityProjectionService::get_booking_price_type( true ) );
		$this->assertSame( 'exchange_rate', MultiCurrencyBookingsCompatibilityProjectionService::get_booking_price_type( false ) );
		$this->assertTrue( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_price( '10.00', false ) );
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_price( 0, false ) );
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_price( '10.00', true ) );
	}

	/**
	 * @testdox Should project Bookings product price conversion guards.
	 */
	public function test_projects_bookings_product_price_conversion_guards(): void {
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_product_price( true, 'booking', true ) );
		$this->assertTrue( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_product_price( true, 'booking', false ) );
		$this->assertTrue( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_product_price( true, 'simple', true ) );
		$this->assertFalse( MultiCurrencyBookingsCompatibilityProjectionService::should_convert_booking_product_price( false, 'booking', true ) );
	}
}
