<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\StoreApi\Utilities\QuantityLimits;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * QuantityLimitsTests class.
 */
class QuantityLimitsTests extends TestCase {
	/**
	 * @var string
	 */
	private $manage_stock;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		$this->manage_stock = get_option( 'woocommerce_manage_stock' );
		parent::setUp();
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_manage_stock', $this->manage_stock );
		// Clean up custom filters.
		remove_all_filters( 'woocommerce_store_api_product_quantity_multiple_of' );
		remove_all_filters( 'woocommerce_store_api_product_quantity_maximum' );
		remove_all_filters( 'woocommerce_store_api_product_quantity_minimum' );
		remove_all_filters( 'woocommerce_quantity_input_args' );
		parent::tearDown();
	}

	/**
	 * Enable float support for tests.
	 */
	private function enable_float_support() {
		// Remove all existing filters first.
		remove_all_filters( 'woocommerce_stock_amount' );
		// Add only floatval.
		add_filter( 'woocommerce_stock_amount', 'floatval' );
	}

	/**
	 * Disable float support and restore integer support.
	 */
	private function disable_float_support() {
		// Remove all existing filters first.
		remove_all_filters( 'woocommerce_stock_amount' );
		// Add only intval.
		add_filter( 'woocommerce_stock_amount', 'intval' );
	}

	/**
	 * Test quantity limits with float support enabled.
	 */
	public function test_quantity_limits_with_classic_filters() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'no' );
		$product->save();

		add_filter(
			'woocommerce_quantity_input_args',
			function ( $args, $the_product ) use ( $product ) {
				if ( $the_product->get_id() === $product->get_id() ) {
					$args['min_value'] = 2;
					$args['max_value'] = 8;
					$args['step']      = 2;
				}
				return $args;
			},
			10,
			2
		);

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 2, $limits['minimum'], 'Minimum quantity should be 2' );
		$this->assertEquals( 8, $limits['maximum'], 'Maximum quantity should be 8' );
		$this->assertEquals( 2, $limits['multiple_of'], 'Multiple of should be 2' );
		remove_all_filters( 'woocommerce_quantity_input_args' );

		// Adjust max value in filter greater than stock quantity.
		add_filter(
			'woocommerce_quantity_input_args',
			function ( $args, $the_product ) use ( $product ) {
				if ( $the_product->get_id() === $product->get_id() ) {
					$args['min_value'] = 2;
					$args['max_value'] = 12;
					$args['step']      = 2;
				}
				return $args;
			},
			10,
			2
		);

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 2, $limits['minimum'], 'Minimum quantity should be 2' );
		$this->assertEquals( 10, $limits['maximum'], 'Maximum quantity should be 10 to match stock quantity' );
		$this->assertEquals( 2, $limits['multiple_of'], 'Multiple of should be 2' );
		remove_all_filters( 'woocommerce_quantity_input_args' );
	}

	/**
	 * Test quantity limit when stock management is enabled.
	 */
	public function test_quantity_limit_when_stock_management_enabled() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Enable stock management globally.
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'yes' );
		$product->save();

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 9999, $limits['maximum'], 'When stock management is enabled, but backorders are allowed, maximum quantity should be 9999' );

		$product->set_backorders( 'no' );
		$product->save();

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 10, $limits['maximum'], 'When stock management is enabled and backorders are not allowed, maximum quantity should be 10' );
	}

	/**
	 * Test quantity limit when stock management is disabled.
	 */
	public function test_quantity_limit_when_stock_management_disabled() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Enable stock management.
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'yes' );
		$product->save();

		// Disable stock management.
		update_option( 'woocommerce_manage_stock', 'no' );

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 9999, $limits['maximum'], 'When stock management is disabled, maximum quantity should be 9999' );
	}

	/**
	 * Test quantity limit when stock management is disabled but product has manage_stock enabled from when it was previously enabled.
	 * This reproduces the Cart Block bug where products retain their stock quantity as max even when stock management is globally disabled.
	 */
	public function test_quantity_limit_when_stock_management_disabled_but_product_has_manage_stock_enabled() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Step 1: Enable stock management globally and on product level.
		update_option( 'woocommerce_manage_stock', 'yes' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'no' );
		$product->save();

		// Verify that with stock management enabled, max is limited to stock quantity.
		$quantity_limits     = new QuantityLimits();
		$limits_when_enabled = $quantity_limits->get_add_to_cart_limits( $product );
		$this->assertEquals( 10, $limits_when_enabled['maximum'], 'When stock management is enabled, maximum should be limited to stock quantity' );

		// Step 2: Disable stock management globally but leave product-level manage_stock as true.
		// This simulates the scenario from import or when stock management was previously enabled.
		update_option( 'woocommerce_manage_stock', 'no' );

		// The product still has manage_stock = true and stock_quantity = 10.
		$product = wc_get_product( $product->get_id() );
		$this->assertTrue( $product->get_manage_stock(), 'Product should still have manage_stock = true' );
		$this->assertEquals( 10, $product->get_stock_quantity(), 'Product should still have stock quantity of 10' );

		// Step 3: Test quantity limits - this should return 9999, not 10.
		$quantity_limits      = new QuantityLimits();
		$limits_when_disabled = $quantity_limits->get_add_to_cart_limits( $product );

		/**
		 * Filter the maximum quantity to allow extensions to override the default value for testing purposes.
		 *
		 * @since 6.8.0
		 *
		 * @param mixed $value The value being filtered.
		 * @param \WC_Product $product The product object.
		 * @param array|null $cart_item The cart item if the product exists in the cart, or null.
		 * @return mixed
		 */
		$expected_max = apply_filters( 'woocommerce_store_api_product_quantity_maximum', 9999, $product );
		$this->assertEquals( $expected_max, $limits_when_disabled['maximum'], 'When stock management is globally disabled, maximum should ignore product-level manage_stock/stock and use the default maximum' );
		$this->assertEquals( 1, $limits_when_disabled['minimum'], 'Minimum should remain default when stock management is globally disabled' );
		$this->assertEquals( 1, $limits_when_disabled['multiple_of'], 'Multiple-of should remain default when stock management is globally disabled' );
	}

	/**
	 * Test quantity limit when stock quantity is sold individually.
	 */
	public function test_quantity_limit_when_stock_quantity_is_sold_individually() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Enable stock management globally.
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'yes' );
		$product->set_sold_individually( true );
		$product->save();

		$quantity_limits = new QuantityLimits();

		$limits = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 1, $limits['maximum'], 'When stock quantity is sold individually, maximum quantity should be 1' );
	}

	/**
	 * Test quantity limit when backorders are allowed.
	 */
	public function test_quantity_limit_when_backorders_allowed() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Enable stock management globally.
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Set up product with stock management and backorders allowed.
		$product->set_manage_stock( 'no' );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'yes' );
		$product->save();

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 9999, $limits['maximum'], 'When backorders are allowed, maximum quantity should be 9999' );
	}

	/**
	 * Test quantity limit when backorders are not allowed.
	 */
	public function test_quantity_limit_when_backorders_not_allowed() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Enable stock management globally.
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Set up product with stock management and backorders not allowed.
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'no' );
		$product->save();

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 10, $limits['maximum'], 'When backorders are not allowed, maximum quantity should be limited to stock quantity' );
	}

	/**
	 * Test quantity limit when product is sold individually with stock management.
	 */
	public function test_quantity_limit_when_sold_individually_with_stock() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Enable stock management globally.
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Set up product as sold individually with stock management.
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->set_backorders( 'no' );
		$product->set_sold_individually( true );
		$product->save();

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 1, $limits['maximum'], 'When product is sold individually, maximum quantity should be 1 regardless of stock' );
	}

	/**
	 * Test quantity limit when product is sold individually without stock management.
	 */
	public function test_quantity_limit_when_sold_individually_without_stock() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Disable stock management globally.
		update_option( 'woocommerce_manage_stock', 'no' );

		// Set up product as sold individually without stock management.
		$product->set_manage_stock( false );
		$product->set_sold_individually( true );
		$product->save();

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 1, $limits['maximum'], 'When product is sold individually without stock management, maximum quantity should be 1' );
	}

	/**
	 * Test quantity limits with float support enabled.
	 */
	public function test_quantity_limits_with_float_support() {
		$this->enable_float_support();

		// Make multiple_of 0.5.
		add_filter(
			'woocommerce_store_api_product_quantity_multiple_of',
			function () {
				return 0.5;
			},
			10
		);

		// Test the filter is working.
		$test_value = wc_stock_amount( 5.5 );
		$this->assertEquals( 5.5, $test_value, 'wc_stock_amount should return float when floatval filter is active' );

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		update_option( 'woocommerce_manage_stock', 'yes' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 5.5 );
		$product->set_backorders( 'no' );
		$product->save();

		// Check what the product actually stored.
		$stored_quantity = $product->get_stock_quantity();
		$this->assertEquals( 5.5, $stored_quantity, 'Product should store float stock quantity' );

		$quantity_limits = new QuantityLimits();
		$limits          = $quantity_limits->get_add_to_cart_limits( $product );

		$this->assertEquals( 5.5, $limits['maximum'], 'Float quantities should be supported for maximum limits' );
	}

	/**
	 * Test is_multiple_of method with integers.
	 */
	public function test_is_multiple_of_with_integers() {
		$quantity_limits = new QuantityLimits();

		// Use reflection to access the private method.
		$reflection = new \ReflectionClass( $quantity_limits );
		$method     = $reflection->getMethod( 'is_multiple_of' );
		$method->setAccessible( true );

		// Test integer multiples.
		$this->assertTrue( $method->invoke( $quantity_limits, 6, 3 ), '6 should be a multiple of 3' );
		$this->assertTrue( $method->invoke( $quantity_limits, 10, 5 ), '10 should be a multiple of 5' );
		$this->assertFalse( $method->invoke( $quantity_limits, 7, 3 ), '7 should not be a multiple of 3' );

		// Test edge cases.
		$this->assertTrue( $method->invoke( $quantity_limits, 0, 5 ), '0 should be a multiple of any number' );
		$this->assertFalse( $method->invoke( $quantity_limits, 5, 0 ), '0 is not a multiple of 5' );
		$this->assertTrue( $method->invoke( $quantity_limits, 5, 1 ), '5 should be a multiple of 1' );
		$this->assertFalse( $method->invoke( $quantity_limits, 5.5, 1 ), '5.5 should not be a multiple of 1' );
	}

	/**
	 * Test is_multiple_of method with floats.
	 */
	public function test_is_multiple_of_with_floats() {
		$quantity_limits = new QuantityLimits();

		// Use reflection to access the private method.
		$reflection = new \ReflectionClass( $quantity_limits );
		$method     = $reflection->getMethod( 'is_multiple_of' );
		$method->setAccessible( true );

		// Test float multiples.
		$this->assertTrue( $method->invoke( $quantity_limits, 1.5, 0.5 ), '1.5 should be a multiple of 0.5' );
		$this->assertTrue( $method->invoke( $quantity_limits, 2.25, 0.25 ), '2.25 should be a multiple of 0.25' );
		$this->assertFalse( $method->invoke( $quantity_limits, 1.7, 0.5 ), '1.7 should not be a multiple of 0.5' );

		// Test mixed integer and float.
		$this->assertTrue( $method->invoke( $quantity_limits, 3.0, 1.5 ), '3.0 should be a multiple of 1.5' );
		$this->assertTrue( $method->invoke( $quantity_limits, 4, 0.5 ), '4 should be a multiple of 0.5' );

		// Test precision edge cases - these should work with our division-based approach.
		$this->assertTrue( $method->invoke( $quantity_limits, 0.3, 0.1 ), '0.3 should be considered a multiple of 0.1 (within tolerance)' );
		$this->assertTrue( $method->invoke( $quantity_limits, 0.75, 0.25 ), '0.75 should be considered a multiple of 0.25 (within tolerance)' );
	}

	/**
	 * Test limit_to_multiple method with integers.
	 */
	public function test_limit_to_multiple_with_integers() {
		$quantity_limits = new QuantityLimits();

		// Test rounding.
		$this->assertEquals( 6, $quantity_limits->limit_to_multiple( 7, 3, 'round' ), '7 rounded to multiple of 3 should be 6' );
		$this->assertEquals( 9, $quantity_limits->limit_to_multiple( 7, 3, 'ceil' ), '7 ceiled to multiple of 3 should be 9' );
		$this->assertEquals( 6, $quantity_limits->limit_to_multiple( 7, 3, 'floor' ), '7 floored to multiple of 3 should be 6' );

		// Test edge cases.
		$this->assertEquals( 5, $quantity_limits->limit_to_multiple( 5, 0 ), 'Multiple of 0 should return original number' );
		$this->assertEquals( 5, $quantity_limits->limit_to_multiple( 5, 1 ), 'Multiple of 1 should return rounded integer' );
	}

	/**
	 * Test limit_to_multiple method with floats.
	 */
	public function test_limit_to_multiple_with_floats() {
		$this->enable_float_support();

		$quantity_limits = new QuantityLimits();

		// Test float rounding.
		$this->assertEquals( 1.5, $quantity_limits->limit_to_multiple( 1.7, 0.5, 'round' ), '1.7 rounded to multiple of 0.5 should be 1.5' );
		$this->assertEquals( 2.0, $quantity_limits->limit_to_multiple( 1.7, 0.5, 'ceil' ), '1.7 ceiled to multiple of 0.5 should be 2.0' );
		$this->assertEquals( 1.5, $quantity_limits->limit_to_multiple( 1.7, 0.5, 'floor' ), '1.7 floored to multiple of 0.5 should be 1.5' );

		// Test quarter increments.
		$this->assertEquals( 2.25, $quantity_limits->limit_to_multiple( 2.3, 0.25, 'round' ), '2.3 rounded to multiple of 0.25 should be 2.25' );
	}

	/**
	 * Test normalize_cart_item_quantity with floats.
	 */
	public function test_normalize_cart_item_quantity_with_floats() {
		$this->enable_float_support();

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10.5 );
		$product->set_backorders( 'no' );
		$product->save();

		$cart_item = array(
			'data' => $product,
		);

		$quantity_limits = new QuantityLimits();

		// Test normalization with float quantities.
		$normalized = $quantity_limits->normalize_cart_item_quantity( 5.7, $cart_item );
		$this->assertIsFloat( $normalized, 'Normalized quantity should be a float when float support is enabled' );

		$normalized = $quantity_limits->normalize_cart_item_quantity( 11.4, $cart_item );
		$this->assertEquals( 10, $normalized, 'Normalized quantity should not exceed stock limit (so should not be 11, it should round down to 10)' );

		// Test invalid input handling.
		$this->assertEquals( 0, $quantity_limits->normalize_cart_item_quantity( -1.5, $cart_item ), 'Negative quantities should be normalized to 0' );
		$this->assertEquals( 0, $quantity_limits->normalize_cart_item_quantity( 'invalid', $cart_item ), 'Non-numeric quantities should be normalized to 0' );
	}

	/**
	 * Test validate_cart_item_quantity with floats.
	 */
	public function test_validate_cart_item_quantity_with_floats() {
		$this->enable_float_support();

		// Make multiple_of 0.5.
		add_filter(
			'woocommerce_store_api_product_quantity_multiple_of',
			function () {
				return 0.5;
			},
			10
		);

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10.5 );
		$product->set_backorders( 'no' );
		$product->save();

		$cart_item = array(
			'data' => $product,
		);

		$quantity_limits = new QuantityLimits();

		// Test valid float quantities.
		$result = $quantity_limits->validate_cart_item_quantity( 5.5, $cart_item );
		$this->assertTrue( $result, 'Valid float quantities should pass validation' );

		// Test quantities exceeding stock.
		$result = $quantity_limits->validate_cart_item_quantity( 11.0, $cart_item );
		$this->assertInstanceOf( 'WP_Error', $result, 'Quantities exceeding stock should return WP_Error' );
	}

	/**
	 * Test float multiple validation with custom multiple_of filter.
	 */
	public function test_float_multiple_validation_with_filter() {
		$this->enable_float_support();

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		$product->save();

		$cart_item = array(
			'data' => $product,
		);

		// Add filter to set multiple_of to 0.5.
		add_filter(
			'woocommerce_store_api_product_quantity_multiple_of',
			function () {
				return 0.5;
			},
			10
		);

		$quantity_limits = new QuantityLimits();

		// Test valid multiples of 0.5.
		$result = $quantity_limits->validate_cart_item_quantity( 2.5, $cart_item );
		$this->assertTrue( $result, '2.5 should be valid when multiple_of is 0.5' );

		$result = $quantity_limits->validate_cart_item_quantity( 3.0, $cart_item );
		$this->assertTrue( $result, '3.0 should be valid when multiple_of is 0.5' );

		// Test invalid multiples.
		$result = $quantity_limits->validate_cart_item_quantity( 2.3, $cart_item );
		$this->assertInstanceOf( 'WP_Error', $result, '2.3 should be invalid when multiple_of is 0.5' );
	}

	/**
	 * Test precision edge cases with very small floats.
	 */
	public function test_precision_edge_cases() {
		$this->enable_float_support();

		$quantity_limits = new QuantityLimits();

		// Use reflection to access the private method.
		$reflection = new \ReflectionClass( $quantity_limits );
		$method     = $reflection->getMethod( 'is_multiple_of' );
		$method->setAccessible( true );

		// Test very small multiples.
		$this->assertTrue( $method->invoke( $quantity_limits, 0.001, 0.000001 ), 'Very small multiples should be handled with tolerance' );

		// Test edge case where multiple_of is smaller than tolerance.
		$this->assertTrue( $method->invoke( $quantity_limits, 5, 0.000001 ), 'Multiple smaller than tolerance should return true' );
	}

	/**
	 * Test limit_to_multiple with decimal multiples like 1.5, 2.5.
	 */
	public function test_limit_to_multiple_with_decimal_multiples() {
		$this->enable_float_support();

		$quantity_limits = new QuantityLimits();

		// Test with multiple_of = 1.5.
		$this->assertEquals( 6.0, $quantity_limits->limit_to_multiple( 5.3, 1.5, 'round' ), '5.3 rounded to multiple of 1.5 should be 6.0' );
		$this->assertEquals( 6.0, $quantity_limits->limit_to_multiple( 5.3, 1.5, 'ceil' ), '5.3 ceiled to multiple of 1.5 should be 6.0' );
		$this->assertEquals( 4.5, $quantity_limits->limit_to_multiple( 5.3, 1.5, 'floor' ), '5.3 floored to multiple of 1.5 should be 4.5' );

		// Test with multiple_of = 2.5.
		$this->assertEquals( 5.0, $quantity_limits->limit_to_multiple( 6.0, 2.5, 'round' ), '6.0 rounded to multiple of 2.5 should be 5.0' );
		$this->assertEquals( 7.5, $quantity_limits->limit_to_multiple( 6.0, 2.5, 'ceil' ), '6.0 ceiled to multiple of 2.5 should be 7.5' );
		$this->assertEquals( 5.0, $quantity_limits->limit_to_multiple( 6.0, 2.5, 'floor' ), '6.0 floored to multiple of 2.5 should be 5.0' );

		// Test with multiple_of = 0.25.
		$this->assertEquals( 1.75, $quantity_limits->limit_to_multiple( 1.8, 0.25, 'round' ), '1.8 rounded to multiple of 0.25 should be 1.75' );
		$this->assertEquals( 2.0, $quantity_limits->limit_to_multiple( 1.8, 0.25, 'ceil' ), '1.8 ceiled to multiple of 0.25 should be 2.0' );
		$this->assertEquals( 1.75, $quantity_limits->limit_to_multiple( 1.8, 0.25, 'floor' ), '1.8 floored to multiple of 0.25 should be 1.75' );
	}

	/**
	 * Test is_multiple_of with decimal multiples.
	 */
	public function test_is_multiple_of_with_decimal_multiples() {
		$quantity_limits = new QuantityLimits();

		// Use reflection to access the private method.
		$reflection = new \ReflectionClass( $quantity_limits );
		$method     = $reflection->getMethod( 'is_multiple_of' );
		$method->setAccessible( true );

		// Test with multiple_of = 1.5.
		$this->assertTrue( $method->invoke( $quantity_limits, 3.0, 1.5 ), '3.0 should be a multiple of 1.5' );
		$this->assertTrue( $method->invoke( $quantity_limits, 4.5, 1.5 ), '4.5 should be a multiple of 1.5' );
		$this->assertTrue( $method->invoke( $quantity_limits, 6.0, 1.5 ), '6.0 should be a multiple of 1.5' );
		$this->assertFalse( $method->invoke( $quantity_limits, 5.0, 1.5 ), '5.0 should not be a multiple of 1.5' );

		// Test with multiple_of = 2.5.
		$this->assertTrue( $method->invoke( $quantity_limits, 5.0, 2.5 ), '5.0 should be a multiple of 2.5' );
		$this->assertTrue( $method->invoke( $quantity_limits, 7.5, 2.5 ), '7.5 should be a multiple of 2.5' );
		$this->assertFalse( $method->invoke( $quantity_limits, 6.0, 2.5 ), '6.0 should not be a multiple of 2.5' );

		// Test with multiple_of = 0.25.
		$this->assertTrue( $method->invoke( $quantity_limits, 1.0, 0.25 ), '1.0 should be a multiple of 0.25' );
		$this->assertTrue( $method->invoke( $quantity_limits, 1.75, 0.25 ), '1.75 should be a multiple of 0.25' );
		$this->assertFalse( $method->invoke( $quantity_limits, 1.3, 0.25 ), '1.3 should not be a multiple of 0.25' );
	}

	/**
	 * Test limit_to_multiple edge cases with zero and negative values.
	 */
	public function test_limit_to_multiple_edge_cases() {
		$quantity_limits = new QuantityLimits();

		// Test with zero multiple_of (should return original number).
		$this->assertEquals( 5.5, $quantity_limits->limit_to_multiple( 5.5, 0, 'round' ), 'Zero multiple_of should return original number' );

		// Test with non-numeric inputs.
		$this->assertEquals( 0, $quantity_limits->limit_to_multiple( 'invalid', 1.5, 'round' ), 'Non-numeric number should return 0 via wc_stock_amount' );
		$this->assertEquals( 0, $quantity_limits->limit_to_multiple( 5.5, 'invalid', 'round' ), 'Non-numeric multiple_of should return 0' );

		// Test with invalid rounding function (should default to 'round').
		$this->assertEquals( 6.0, $quantity_limits->limit_to_multiple( 5.3, 1.5, 'invalid' ), 'Invalid rounding function should default to round' );
	}
}
