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

		$this->assertEquals( 10, $limits['maximum'], 'When stock management is enabled, maximum quantity should be 10' );
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
}
