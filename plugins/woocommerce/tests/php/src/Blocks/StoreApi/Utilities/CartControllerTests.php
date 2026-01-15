<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\Internal\FraudProtection\CartEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Unit tests for the CartController class.
 */
class CartControllerTests extends TestCase {
	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
		remove_all_filters( 'woocommerce_cart_shipping_packages' );

		// Reset DI container to clear any mocks.
		$container = wc_get_container();
		$container->reset_all_resolved();
		$container->reset_all_replacements();
	}

	/**
	 * Test the normalize_cart method.
	 */
	public function test_normalize_cart() {
		$class    = new CartController();
		$fixtures = new FixtureData();

		$product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'regular_price' => 10,
			)
		);

		// Test maximum quantity after normalizing.
		$product_key = wc()->cart->add_to_cart( $product->get_id(), 5 );
		add_filter(
			'woocommerce_store_api_product_quantity_maximum',
			function () {
				return 2;
			},
			10
		);
		$class->normalize_cart();
		$this->assertEquals( 2, wc()->cart->get_cart_item( $product_key )['quantity'] );
		remove_all_filters( 'woocommerce_store_api_product_quantity_maximum' );
		wc()->cart->empty_cart();

		// Test minimum quantity after normalizing.
		$product_key = wc()->cart->add_to_cart( $product->get_id(), 1 );
		add_filter(
			'woocommerce_store_api_product_quantity_minimum',
			function () {
				return 5;
			},
			10
		);
		$class->normalize_cart();
		$this->assertEquals( 5, wc()->cart->get_cart_item( $product_key )['quantity'] );
		remove_all_filters( 'woocommerce_store_api_product_quantity_minimum' );
		wc()->cart->empty_cart();

		// Test multiple of after normalizing.
		$product_key = wc()->cart->add_to_cart( $product->get_id(), 7 );
		add_filter(
			'woocommerce_store_api_product_quantity_multiple_of',
			function () {
				return 3;
			},
			10
		);
		$class->normalize_cart();
		$this->assertEquals( 6, wc()->cart->get_cart_item( $product_key )['quantity'] );
		remove_all_filters( 'woocommerce_store_api_product_quantity_multiple_of' );
		wc()->cart->empty_cart();
	}

	/**
	 * Test cart error code is getting exposed.
	 */
	public function test_get_cart_errors() {
		$class    = new CartController();
		$fixtures = new FixtureData();

		// This product will simply be in/out of stock.
		$out_of_stock_product     = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'regular_price' => 10,
			)
		);
		$out_of_stock_product_key = wc()->cart->add_to_cart( $out_of_stock_product->get_id(), 2 );
		$out_of_stock_in_cart     = wc()->cart->get_cart_item( $out_of_stock_product_key )['data'];

		// This product will have exact levels of stock known.
		$partially_out_of_stock_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 2',
				'regular_price' => 10,
			)
		);
		$partially_out_of_stock_key     = wc()->cart->add_to_cart( $partially_out_of_stock_product->get_id(), 4 );
		$partially_out_of_stock_in_cart = wc()->cart->get_cart_item( $partially_out_of_stock_key )['data'];

		// This product will have exact levels of stock known.
		$too_many_in_cart_product     = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 3',
				'regular_price' => 10,
			)
		);
		$too_many_in_cart_product_key = wc()->cart->add_to_cart( $too_many_in_cart_product->get_id(), 4 );
		$too_many_in_cart_in_cart     = wc()->cart->get_cart_item( $too_many_in_cart_product_key )['data'];

		$out_of_stock_in_cart->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$partially_out_of_stock_in_cart->set_manage_stock( true );
		$partially_out_of_stock_in_cart->set_stock_quantity( 2 );
		$too_many_in_cart_in_cart->set_sold_individually( true );

		// This product will not be purchasable.
		$not_purchasable_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 4',
				'regular_price' => 10,
			)
		);
		wc()->cart->add_to_cart( $not_purchasable_product->get_id(), 2 );

		// This function will force the $product->is_purchasable() function to return false for our $not_purchasable_product.
		add_filter(
			'woocommerce_is_purchasable',
			function ( $is_purchasable, $product ) use ( $not_purchasable_product ) {
				if ( $product->get_id() === $not_purchasable_product->get_id() ) {
					return false;
				}
				return true;
			},
			10,
			2
		);

		$errors = $class->get_cart_errors();

		$this->assertTrue( is_wp_error( $errors ) );
		$this->assertTrue( $errors->has_errors() );

		$error_codes     = $errors->get_error_codes();
		$expected_errors = array(
			'woocommerce_rest_product_partially_out_of_stock',
			'woocommerce_rest_product_out_of_stock',
			'woocommerce_rest_product_not_purchasable',
			'woocommerce_rest_product_too_many_in_cart',
		);

		foreach ( $expected_errors as $expected_error ) {
			$this->assertContains( $expected_error, $error_codes );
		}
	}

	/**
	 * Test that get_shipping_packages returns packages with package_id and package_name.
	 */
	public function test_get_shipping_packages_includes_package_id_and_package_name() {
		$class    = new CartController();
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
				'weight'        => 10,
			)
		);

		wc()->cart->add_to_cart( $product->get_id(), 1 );

		// Set shipping address so packages are generated.
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_shipping_postcode( '90210' );

		$packages = $class->get_shipping_packages( false );

		$this->assertNotEmpty( $packages, 'Should have at least one shipping package.' );
		$this->assertArrayHasKey( 'package_id', $packages[0], 'Package should have package_id.' );
		$this->assertArrayHasKey( 'package_name', $packages[0], 'Package should have package_name.' );
		$this->assertEquals( 0, $packages[0]['package_id'], 'First package should have package_id of 0 (array key).' );
		$this->assertStringContainsString( 'Shipment 1', $packages[0]['package_name'], 'First package should have package_name containing "Shipment 1".' );
	}

	/**
	 * Test that get_shipping_packages handles multiple packages correctly.
	 */
	public function test_get_shipping_packages_handles_multiple_packages() {
		$class    = new CartController();
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
				'weight'        => 10,
			)
		);

		wc()->cart->add_to_cart( $product->get_id(), 1 );

		// Set shipping address.
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_shipping_postcode( '90210' );

		// Filter to create multiple packages.
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) {
				$packages[] = $packages[0];
				return $packages;
			}
		);

		$packages = $class->get_shipping_packages( false );

		$this->assertCount( 2, $packages, 'Should have two shipping packages.' );

		// First package.
		$this->assertArrayHasKey( 'package_id', $packages[0], 'First package should have package_id.' );
		$this->assertArrayHasKey( 'package_name', $packages[0], 'First package should have package_name.' );
		$this->assertEquals( 0, $packages[0]['package_id'], 'First package should have package_id of 0.' );
		$this->assertStringContainsString( 'Shipment 1', $packages[0]['package_name'], 'First package should have package_name containing "Shipment 1".' );

		// Second package.
		$this->assertArrayHasKey( 'package_id', $packages[1], 'Second package should have package_id.' );
		$this->assertArrayHasKey( 'package_name', $packages[1], 'Second package should have package_name.' );
		$this->assertEquals( 1, $packages[1]['package_id'], 'Second package should have package_id of 1.' );
		$this->assertStringContainsString( 'Shipment 2', $packages[1]['package_name'], 'Second package should have package_name containing "Shipment 2".' );

		remove_all_filters( 'woocommerce_cart_shipping_packages' );
	}

	/**
	 * Test that get_shipping_packages respects custom package_id from filter.
	 */
	public function test_get_shipping_packages_respects_custom_package_id() {
		$class    = new CartController();
		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
				'weight'        => 10,
			)
		);

		wc()->cart->add_to_cart( $product->get_id(), 1 );

		// Set shipping address.
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_shipping_postcode( '90210' );

		// Filter to add custom package_id.
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) {
				$packages[0]['package_id'] = 'custom-package-123';
				return $packages;
			}
		);

		$packages = $class->get_shipping_packages( false );

		$this->assertNotEmpty( $packages, 'Should have at least one shipping package.' );
		$this->assertEquals( 'custom-package-123', $packages[0]['package_id'], 'Package should use custom package_id from filter.' );
		$this->assertArrayHasKey( 'package_name', $packages[0], 'Package should still have package_name.' );

		remove_all_filters( 'woocommerce_cart_shipping_packages' );
	}

	/**
	 * Test that fraud protection tracking is called when adding items via Store API.
	 */
	public function test_add_to_cart_triggers_fraud_protection_tracking(): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Fraud Protection Test Product',
				'regular_price' => 10,
			)
		);

		// Create mock for CartEventTracker.
		$mock_cart_tracker = $this->createMock( CartEventTracker::class );

		// Create mock for FraudProtectionController that returns enabled.
		$mock_controller = $this->createMock( FraudProtectionController::class );
		$mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Replace container instances with mocks.
		$container = wc_get_container();
		$container->replace( FraudProtectionController::class, $mock_controller );
		$container->replace( CartEventTracker::class, $mock_cart_tracker );

		// Expect track_cart_item_added to be called once with correct parameters.
		$mock_cart_tracker
			->expects( $this->once() )
			->method( 'track_cart_item_added' )
			->with(
				$this->isType( 'string' ), // cart_id.
				$this->equalTo( $product->get_id() ), // product_id.
				$this->equalTo( 2 ), // quantity.
				$this->equalTo( 0 ) // variation_id.
			);

		// Use Store API CartController to add item.
		$cart_controller = new CartController();
		$cart_controller->add_to_cart(
			array(
				'id'       => $product->get_id(),
				'quantity' => 2,
			)
		);
	}

	/**
	 * Test that fraud protection tracking is NOT called when feature is disabled.
	 */
	public function test_add_to_cart_skips_fraud_protection_tracking_when_disabled(): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Fraud Protection Disabled Test Product',
				'regular_price' => 10,
			)
		);

		// Create mock for CartEventTracker.
		$mock_cart_tracker = $this->createMock( CartEventTracker::class );

		// Create mock for FraudProtectionController that returns disabled.
		$mock_controller = $this->createMock( FraudProtectionController::class );
		$mock_controller->method( 'feature_is_enabled' )->willReturn( false );

		// Replace container instances with mocks.
		$container = wc_get_container();
		$container->replace( FraudProtectionController::class, $mock_controller );
		$container->replace( CartEventTracker::class, $mock_cart_tracker );

		// Expect track_cart_item_added to NOT be called.
		$mock_cart_tracker
			->expects( $this->never() )
			->method( 'track_cart_item_added' );

		// Use Store API CartController to add item.
		$cart_controller = new CartController();
		$cart_controller->add_to_cart(
			array(
				'id'       => $product->get_id(),
				'quantity' => 1,
			)
		);
	}

	/**
	 * Test that fraud protection tracking includes variation_id for variable products.
	 */
	public function test_add_to_cart_tracks_variation_id_for_variable_products(): void {
		// Create a variable product with variations using WC_Helper_Product.
		$variable_product = \WC_Helper_Product::create_variation_product();

		// Get the third variation which has all attributes defined (size, colour, number).
		$children     = $variable_product->get_children();
		$variation_id = $children[2]; // "DUMMY SKU VARIABLE HUGE RED 0" - has all attributes.

		// Create mock for CartEventTracker.
		$mock_cart_tracker = $this->createMock( CartEventTracker::class );

		// Create mock for FraudProtectionController that returns enabled.
		$mock_controller = $this->createMock( FraudProtectionController::class );
		$mock_controller->method( 'feature_is_enabled' )->willReturn( true );

		// Replace container instances with mocks.
		$container = wc_get_container();
		$container->replace( FraudProtectionController::class, $mock_controller );
		$container->replace( CartEventTracker::class, $mock_cart_tracker );

		// Expect track_cart_item_added to be called with the correct variation_id.
		$mock_cart_tracker
			->expects( $this->once() )
			->method( 'track_cart_item_added' )
			->with(
				$this->isType( 'string' ), // cart_id.
				$this->equalTo( $variable_product->get_id() ), // product_id.
				$this->equalTo( 1 ), // quantity.
				$this->equalTo( $variation_id ) // variation_id.
			);

		// Use Store API CartController to add variation with all required attributes.
		// CartController::parse_variation_data() expects an array of {attribute, value} pairs.
		$cart_controller = new CartController();
		$cart_controller->add_to_cart(
			array(
				'id'        => $variation_id,
				'quantity'  => 1,
				'variation' => array(
					array(
						'attribute' => 'attribute_pa_size',
						'value'     => 'huge',
					),
					array(
						'attribute' => 'attribute_pa_colour',
						'value'     => 'red',
					),
					array(
						'attribute' => 'attribute_pa_number',
						'value'     => '0',
					),
				),
			)
		);

		// Clean up.
		$variable_product->delete( true );
	}
}
