<?php
/**
 * Class WC_Tax_Test file.
 *
 * @package WooCommerce\Tests\Includes
 */

declare( strict_types=1 );

/**
 * Unit tests for the WC_Tax class get_shipping_tax_rates method and related functionality.
 * Covers edge case bug from https://github.com/woocommerce/woocommerce/issues/58757
 */
class WC_Tax_Test extends WC_Unit_Test_Case {

	/**
	 * Track whether we created the reduced-rate tax class.
	 *
	 * @var bool
	 */
	private $created_reduced_rate_class = false;

	/**
	 * Track whether we created the zero-rate tax class.
	 *
	 * @var bool
	 */
	private $created_zero_rate_class = false;

	/**
	 * Track created products for cleanup.
	 *
	 * @var array
	 */
	private $created_products = array();

	/**
	 * Store the original value of woocommerce_calc_taxes.
	 *
	 * @var string|null
	 */
	private $original_calc_taxes = null;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clear any existing tax rates and settings.
		$this->clean_up_tax_rates();

		// Store and set woocommerce_calc_taxes option.
		$this->original_calc_taxes = get_option( 'woocommerce_calc_taxes', null );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Ensure required tax classes exist for testing.
		$this->ensure_tax_classes_exist();

		// Clear cart.
		WC()->cart->empty_cart();
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clear cart.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		// Clean up created products.
		foreach ( $this->created_products as $product_id ) {
			wp_delete_post( $product_id, true );
		}
		$this->created_products = array();

		// Clean up tax rates.
		$this->clean_up_tax_rates();

		// Clear test tax rates.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = 'US'" );

		// Reset tax settings.
		delete_option( 'woocommerce_shipping_tax_class' );
		delete_option( 'woocommerce_tax_classes' );

		// Restore woocommerce_calc_taxes option to its original value.
		if ( null !== $this->original_calc_taxes ) {
			update_option( 'woocommerce_calc_taxes', $this->original_calc_taxes );
		} else {
			delete_option( 'woocommerce_calc_taxes' );
		}

		// Only clean up tax classes we created during testing.
		if ( $this->created_reduced_rate_class ) {
			WC_Tax::delete_tax_class_by( 'slug', 'reduced-rate' );
		}
		if ( $this->created_zero_rate_class ) {
			WC_Tax::delete_tax_class_by( 'slug', 'zero-rate' );
		}
	}

	/**
	 * Helper method to clean up all tax rates.
	 */
	private function clean_up_tax_rates() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		wp_cache_flush();
		\WC_Cache_Helper::invalidate_cache_group( 'taxes' );
	}

	/**
	 * Ensure required tax classes exist for testing.
	 * Creates them only if they don't already exist.
	 */
	private function ensure_tax_classes_exist() {
		$existing_tax_class_slugs = WC_Tax::get_tax_class_slugs();

		// Check and create reduced-rate tax class if needed.
		if ( ! in_array( 'reduced-rate', $existing_tax_class_slugs, true ) ) {
			$reduced_class = WC_Tax::create_tax_class( 'Reduced rate', 'reduced-rate' );
			if ( is_array( $reduced_class ) ) {
				$this->created_reduced_rate_class = true;
			}
		}

		// Check and create zero-rate tax class if needed.
		if ( ! in_array( 'zero-rate', $existing_tax_class_slugs, true ) ) {
			$zero_class = WC_Tax::create_tax_class( 'Zero rate', 'zero-rate' );
			if ( is_array( $zero_class ) ) {
				$this->created_zero_rate_class = true;
			}
		}
	}

	/**
	 * Helper method to create a product with specified tax class.
	 *
	 * @param string $tax_class Tax class slug ('' for standard, 'reduced-rate', etc).
	 * @param string $name Product name.
	 * @return WC_Product_Simple
	 */
	private function create_product_with_tax_class( $tax_class = '', $name = 'Test Product' ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( 10.00 );
		$product->set_tax_class( $tax_class );
		$product->save();

		$this->created_products[] = $product->get_id();

		return $product;
	}

	/**
	 * Create a test tax rate.
	 *
	 * @param string $tax_class Tax class slug.
	 * @param float  $rate Tax rate percentage.
	 * @return int Tax rate ID.
	 */
	private function create_test_tax_rate( $tax_class, $rate ) {
		return WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => $rate,
				'tax_rate_name'     => $tax_class . ' Rate',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => $tax_class,
			)
		);
	}

	/**
	 * Set up a test cart with specific items and tax rates.
	 *
	 * @param array $items Array of items with 'price' and 'tax_class' keys.
	 * @return void
	 */
	private function setup_test_cart( $items ) {
		// Clear existing cart.
		WC()->cart->empty_cart();

		// Set tax calculation to billing address.
		update_option( 'woocommerce_tax_based_on', 'billing' );

		// Create test tax rates.
		$tax_rates = array();
		foreach ( $items as $item ) {
			if ( ! isset( $tax_rates[ $item['tax_class'] ] ) ) {
				$rate = isset( $item['rate'] ) ? $item['rate'] : 10;
				$tax_rates[ $item['tax_class'] ] = $this->create_test_tax_rate( $item['tax_class'], $rate );
			}
		}

		// Create products and add to cart.
		foreach ( $items as $item ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( $item['price'] );
			$product->set_tax_class( $item['tax_class'] );
			$product->set_tax_status( 'taxable' );
			$product->save();

			WC()->cart->add_to_cart( $product->get_id(), 1 );
		}

		// Set customer to taxable location.
		WC()->customer->set_billing_country( 'US' );
		WC()->customer->set_is_vat_exempt( false );

		// Calculate cart totals to populate tax data.
		WC()->cart->calculate_totals();
	}

	/**
	 * Test get_shipping_tax_rates returns correct rates for standard tax class.
	 */
	public function test_get_shipping_tax_rates_with_standard_tax_class() {
		// Create a standard tax rate with shipping enabled.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'Test Sales Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		// Set shipping tax class to inherit.
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Create product with standard tax class and add to cart.
		$product = $this->create_product_with_tax_class( '', 'Standard Tax Product' );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertArrayHasKey( $tax_rate_id, $shipping_rates );
		$this->assertEquals( '10.0000', $shipping_rates[ $tax_rate_id ]['rate'] );
		$this->assertEquals( 'Test Sales Tax', $shipping_rates[ $tax_rate_id ]['label'] );
		$this->assertEquals( 'yes', $shipping_rates[ $tax_rate_id ]['shipping'] );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}

	/**
	 * Test get_shipping_tax_rates with reduced rate tax class.
	 */
	public function test_get_shipping_tax_rates_with_reduced_tax_class() {
		// Create reduced rate tax with shipping enabled.
		$reduced_tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'Reduced Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'reduced-rate',
		);

		$reduced_tax_rate_id = WC_Tax::_insert_tax_rate( $reduced_tax_rate );

		// Set shipping tax class to inherit.
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Create product with reduced rate tax class and add to cart.
		$product = $this->create_product_with_tax_class( 'reduced-rate', 'Reduced Rate Product' );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertArrayHasKey( $reduced_tax_rate_id, $shipping_rates );
		$this->assertEquals( '5.0000', $shipping_rates[ $reduced_tax_rate_id ]['rate'] );
		$this->assertEquals( 'Reduced Tax', $shipping_rates[ $reduced_tax_rate_id ]['label'] );

		WC_Tax::_delete_tax_rate( $reduced_tax_rate_id );
	}

	/**
	 * Test the correct behavior: when reduced rate items have no shipping tax rates,
	 * it should return empty array (no shipping tax) rather than falling back to standard rates.
	 * This tests the fix for https://github.com/woocommerce/woocommerce/issues/58757
	 */
	public function test_get_shipping_tax_rates_edge_case_no_reduced_shipping_rates() {
		// Create standard tax rate with shipping enabled.
		$standard_tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'Standard Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		// Create reduced rate tax WITHOUT shipping enabled (shipping = 0).
		$reduced_tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'Reduced Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0', // This is the key - no shipping tax for reduced rate.
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'reduced-rate',
		);

		$standard_tax_rate_id = WC_Tax::_insert_tax_rate( $standard_tax_rate );
		$reduced_tax_rate_id  = WC_Tax::_insert_tax_rate( $reduced_tax_rate );

		// Set shipping tax class to inherit.
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Create product with reduced rate tax class and add to cart (the problematic scenario).
		$product = $this->create_product_with_tax_class( 'reduced-rate', 'Reduced Rate Product' );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		// The correct behavior: when reduced rate has no shipping tax enabled,
		// no shipping tax should be applied (empty array), not fall back to standard rates.
		$this->assertEmpty( $shipping_rates, 'Should return empty array when reduced rate has no shipping tax enabled' );

		WC_Tax::_delete_tax_rate( $standard_tax_rate_id );
		WC_Tax::_delete_tax_rate( $reduced_tax_rate_id );
	}

	/**
	 * Test get_shipping_tax_rates with explicit shipping tax class setting.
	 */
	public function test_get_shipping_tax_rates_with_explicit_shipping_tax_class() {
		// Create reduced rate tax with shipping enabled.
		$reduced_tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'Reduced Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'reduced-rate',
		);

		$reduced_tax_rate_id = WC_Tax::_insert_tax_rate( $reduced_tax_rate );

		// Set explicit shipping tax class (not inherit).
		update_option( 'woocommerce_shipping_tax_class', 'reduced-rate' );

		// Create product with standard tax class - should be ignored due to explicit setting.
		$product = $this->create_product_with_tax_class( '', 'Standard Tax Product' );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertArrayHasKey( $reduced_tax_rate_id, $shipping_rates );
		$this->assertEquals( '5.0000', $shipping_rates[ $reduced_tax_rate_id ]['rate'] );

		WC_Tax::_delete_tax_rate( $reduced_tax_rate_id );
	}

	/**
	 * Test get_shipping_tax_rates returns empty array when no taxable items.
	 */
	public function test_get_shipping_tax_rates_no_taxable_items() {
		// Set shipping tax class to inherit.
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Create product with no tax status (non-taxable) and add to cart.
		$product = new WC_Product_Simple();
		$product->set_name( 'Non-taxable Product' );
		$product->set_regular_price( 10.00 );
		$product->set_tax_status( 'none' );
		$product->save();

		$this->created_products[] = $product->get_id();

		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertEmpty( $shipping_rates );
	}

	/**
	 * Test get_shipping_tax_rates with mixed tax classes prioritizes standard.
	 */
	public function test_get_shipping_tax_rates_mixed_tax_classes_prioritizes_standard() {
		// Create both standard and reduced rate taxes with shipping enabled.
		$standard_tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'Standard Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		$reduced_tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.0000',
			'tax_rate_name'     => 'Reduced Tax',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'reduced-rate',
		);

		$standard_tax_rate_id = WC_Tax::_insert_tax_rate( $standard_tax_rate );
		$reduced_tax_rate_id  = WC_Tax::_insert_tax_rate( $reduced_tax_rate );

		// Set shipping tax class to inherit.
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Add products with mixed tax classes to cart.
		$standard_product = $this->create_product_with_tax_class( '', 'Standard Tax Product' );
		$reduced_product  = $this->create_product_with_tax_class( 'reduced-rate', 'Reduced Rate Product' );

		WC()->cart->add_to_cart( $standard_product->get_id(), 1 );
		WC()->cart->add_to_cart( $reduced_product->get_id(), 1 );

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		// Should prioritize standard tax class.
		$this->assertArrayHasKey( $standard_tax_rate_id, $shipping_rates );
		$this->assertEquals( '10.0000', $shipping_rates[ $standard_tax_rate_id ]['rate'] );

		WC_Tax::_delete_tax_rate( $standard_tax_rate_id );
		WC_Tax::_delete_tax_rate( $reduced_tax_rate_id );
	}

	/**
	 * Test get_shipping_tax_rates returns empty array for empty cart.
	 */
	public function test_get_shipping_tax_rates_empty_cart() {
		// Set shipping tax class to inherit.
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Ensure cart is empty.
		WC()->cart->empty_cart();

		$shipping_rates = WC_Tax::get_shipping_tax_rates();

		$this->assertEmpty( $shipping_rates );
	}

	/**
	 * Test get_shipping_tax_class_from_cart_items returns standard for empty cart.
	 */
	public function test_get_shipping_tax_class_from_cart_items_empty_cart() {
		// Ensure cart is empty.
		WC()->cart->empty_cart();

		// Use reflection to test private method.
		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_from_cart_items' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		$this->assertEquals( '', $result );
	}

	/**
	 * Test get_shipping_tax_class_from_cart_items returns null for no taxable items.
	 */
	public function test_get_shipping_tax_class_from_cart_items_no_taxable_items() {
		// Create non-taxable product and add to cart.
		$product = new WC_Product_Simple();
		$product->set_name( 'Non-taxable Product' );
		$product->set_regular_price( 10.00 );
		$product->set_tax_status( 'none' );
		$product->save();

		$this->created_products[] = $product->get_id();

		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Use reflection to test private method.
		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_from_cart_items' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		$this->assertNull( $result );
	}

	/**
	 * Test get_shipping_tax_class_from_cart_items prioritizes standard tax class.
	 */
	public function test_get_shipping_tax_class_from_cart_items_prioritizes_standard() {
		// Add products with mixed tax classes to cart.
		$reduced_product  = $this->create_product_with_tax_class( 'reduced-rate', 'Reduced Rate Product' );
		$standard_product = $this->create_product_with_tax_class( '', 'Standard Tax Product' );

		WC()->cart->add_to_cart( $reduced_product->get_id(), 1 );
		WC()->cart->add_to_cart( $standard_product->get_id(), 1 );

		// Use reflection to test private method.
		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_from_cart_items' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		$this->assertEquals( '', $result, 'Should prioritize standard tax class (empty string)' );
	}

	/**
	 * Test get_shipping_tax_class_from_cart_items returns single tax class.
	 */
	public function test_get_shipping_tax_class_from_cart_items_single_tax_class() {
		// Add product with reduced rate tax class to cart.
		$product = $this->create_product_with_tax_class( 'reduced-rate', 'Reduced Rate Product' );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Use reflection to test private method.
		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_from_cart_items' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		$this->assertEquals( 'reduced-rate', $result );
	}

	/**
	 * Test get_shipping_tax_class_from_cart_items follows tax class hierarchy.
	 */
	public function test_get_shipping_tax_class_from_cart_items_follows_hierarchy() {
		// Add products with zero-rate and reduced-rate tax classes to cart.
		$zero_product    = $this->create_product_with_tax_class( 'zero-rate', 'Zero Rate Product' );
		$reduced_product = $this->create_product_with_tax_class( 'reduced-rate', 'Reduced Rate Product' );

		WC()->cart->add_to_cart( $zero_product->get_id(), 1 );
		WC()->cart->add_to_cart( $reduced_product->get_id(), 1 );

		// Use reflection to test private method.
		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_from_cart_items' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Should return the first tax class found in the hierarchy (reduced-rate comes before zero-rate).
		$this->assertEquals( 'reduced-rate', $result );
	}

	/**
	 * Test get_shipping_tax_class_by_highest_rate with different rates.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_rate_basic() {
		$this->setup_test_cart(
			array(
				array(
					'price'     => 100,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 100,
					'tax_class' => '',
					'rate'      => 21,
				),
			)
		);

		// Use reflection to call private method.
		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_rate' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Standard class (21%) has highest rate.
		$this->assertEquals( '', $result );
	}

	/**
	 * Test highest rate method with empty cart.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_rate_empty_cart() {
		WC()->cart->empty_cart();

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_rate' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Empty cart returns null (no data), not empty string (which means standard class).
		$this->assertNull( $result );
	}

	/**
	 * Test highest rate method with non-taxable items.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_rate_no_taxable() {
		$this->setup_test_cart(
			array(
				array(
					'price'     => 100,
					'tax_class' => '',
					'rate'      => 0,
				),
			)
		);

		// Set all products to non-taxable.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$product->set_tax_status( 'none' );
			$product->save();
		}

		// Recalculate.
		WC()->cart->calculate_totals();

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_rate' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		$this->assertNull( $result );
	}

	/**
	 * Test highest rate method with three rates.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_rate_three_rates() {
		$this->setup_test_cart(
			array(
				array(
					'price'     => 50,
					'tax_class' => 'zero-rate',
					'rate'      => 0,
				),
				array(
					'price'     => 50,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 50,
					'tax_class' => '',
					'rate'      => 21,
				),
			)
		);

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_rate' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Standard (21%) is highest.
		$this->assertEquals( '', $result );
	}

	/**
	 * Test highest amount method - Dutch scenario 1.
	 *
	 * Cart: €45 tax at 9%, €65 tax at 21%
	 * Expected: 21% class (higher tax amount)
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_amount_dutch_scenario_1() {
		// To get €45 tax at 9%: price = 45 / 0.09 = €500
		// To get €65 tax at 21%: price = 65 / 0.21 = €309.52
		$this->setup_test_cart(
			array(
				array(
					'price'     => 500,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 309.52,
					'tax_class' => '',
					'rate'      => 21,
				),
			)
		);

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_amount' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Standard class (21%) collected more tax (€65 > €45).
		$this->assertEquals( '', $result );
	}

	/**
	 * Test highest amount method - Dutch scenario 2.
	 *
	 * Cart: €115 tax at 9%, €55 tax at 21%
	 * Expected: 9% class (higher tax amount despite lower rate)
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_amount_dutch_scenario_2() {
		// To get €115 tax at 9%: price = 115 / 0.09 = €1277.78
		// To get €55 tax at 21%: price = 55 / 0.21 = €261.90
		$this->setup_test_cart(
			array(
				array(
					'price'     => 1277.78,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 261.90,
					'tax_class' => '',
					'rate'      => 21,
				),
			)
		);

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_amount' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Reduced-rate class (9%) collected more tax (€115 > €55).
		$this->assertEquals( 'reduced-rate', $result );
	}

	/**
	 * Test highest amount method with tie.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_amount_tie() {
		// Both classes collect same amount of tax.
		$this->setup_test_cart(
			array(
				array(
					'price'     => 100,
					'tax_class' => 'reduced-rate',
					'rate'      => 10,
				),
				array(
					'price'     => 100,
					'tax_class' => '',
					'rate'      => 10,
				),
			)
		);

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_amount' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Should return one of them (arsort is stable, returns first).
		$this->assertTrue( in_array( $result, array( '', 'reduced-rate' ), true ) );
	}

	/**
	 * Test highest amount method with mixed taxable and non-taxable items.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_amount_mixed() {
		$this->setup_test_cart(
			array(
				array(
					'price'     => 100,
					'tax_class' => '',
					'rate'      => 21,
				),
			)
		);

		// Add a non-taxable product.
		$non_taxable = WC_Helper_Product::create_simple_product();
		$non_taxable->set_regular_price( 500 );
		$non_taxable->set_tax_status( 'none' );
		$non_taxable->save();
		WC()->cart->add_to_cart( $non_taxable->get_id(), 1 );

		// Recalculate.
		WC()->cart->calculate_totals();

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_amount' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Should return standard class (only taxable item).
		$this->assertEquals( '', $result );
	}

	/**
	 * Test highest amount method with multiple items per class.
	 *
	 * @since X.X.X
	 */
	public function test_get_shipping_tax_class_by_highest_amount_multiple_items() {
		$this->setup_test_cart(
			array(
				array(
					'price'     => 100,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 100,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 100,
					'tax_class' => 'reduced-rate',
					'rate'      => 9,
				),
				array(
					'price'     => 100,
					'tax_class' => '',
					'rate'      => 21,
				),
			)
		);

		$reflection = new ReflectionClass( 'WC_Tax' );
		$method     = $reflection->getMethod( 'get_shipping_tax_class_by_highest_amount' );
		$method->setAccessible( true );

		$result = $method->invoke( null );

		// Reduced-rate: 3 * €100 * 9% = €27
		// Standard: 1 * €100 * 21% = €21
		// Reduced-rate collected more.
		$this->assertEquals( 'reduced-rate', $result );
	}
}
