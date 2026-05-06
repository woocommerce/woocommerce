<?php
declare( strict_types = 1 );

/**
 * Class WC_Structured_Data_Test.
 */
class WC_Structured_Data_Test extends \WC_Unit_Test_Case {

	/** @var WC_Structured_Data $structured_data */
	public $structured_data;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		include_once WC_ABSPATH . 'includes/class-wc-structured-data.php';
		$this->structured_data = new WC_Structured_Data();
		parent::setUp();
	}

	/**
	 * Test is_valid_gtin function
	 *
	 * @return void
	 */
	public function test_is_valid_gtin(): void {

		$valid_gtins = array(
			'12345678',
			'123456789012',
			'1234567890123',
			'12345678901234',
		);

		$invalid_gtins = array(
			'',
			null,
			false,
			12345678,
			123.4e-5,
			+1234567,
			'abcdefgh',
			'-9999999',
			'12-45-66',
			'123',
			'123456789012345',
			'123456789',
			'1234567890',
			'12 34 56 78',
			'12 34 56',
			'+12345678',
			'123.4e-5',
		);

		foreach ( $valid_gtins as $valid_gtin ) {
			$this->assertTrue( $this->structured_data->is_valid_gtin( $valid_gtin ) );
		}

		foreach ( $invalid_gtins as $invalid_gtin ) {
			$this->assertFalse( $this->structured_data->is_valid_gtin( $invalid_gtin ) );
		}
	}

	/**
	 * Test prepare_gtin function
	 *
	 * @return void
	 */
	public function test_prepare_gtin(): void {
		$this->assertEquals( $this->structured_data->prepare_gtin( '123-456-78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '-123-456-78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( 'GTIN: 123-456-78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '123 456 78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( null ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( 'GTIN' ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( 123 ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( array( '123-456-78', '123-456-78' ) ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '+12345678' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '123.4e-5' ), '12345' );
	}

	/**
	 * Test that valueAddedTaxIncluded is included in product structured data when taxes are enabled
	 *
	 * @return void
	 */
	public function test_product_structured_data_includes_vat_when_taxes_enabled(): void {
		// Enable taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );

		// Create a simple product with a price.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		// Generate structured data.
		$this->structured_data->generate_product_data( $product );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry.
		$this->assertNotEmpty( $data );
		$product_data = $data[0];

		// Check that offers exist.
		$this->assertArrayHasKey( 'offers', $product_data );
		$this->assertNotEmpty( $product_data['offers'] );

		// Get the first offer.
		$offer = $product_data['offers'][0];

		// Check that priceSpecification exists and contains valueAddedTaxIncluded.
		$this->assertArrayHasKey( 'priceSpecification', $offer );
		$this->assertNotEmpty( $offer['priceSpecification'] );

		$price_spec = $offer['priceSpecification'][0];
		$this->assertArrayHasKey( 'valueAddedTaxIncluded', $price_spec );
		$this->assertTrue( $price_spec['valueAddedTaxIncluded'] );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test that valueAddedTaxIncluded is not included in product structured data when taxes are disabled
	 *
	 * @return void
	 */
	public function test_product_structured_data_excludes_vat_when_taxes_disabled(): void {
		// Disable taxes.
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Create a simple product with a price.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		// Generate structured data.
		$this->structured_data->generate_product_data( $product );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry.
		$this->assertNotEmpty( $data );
		$product_data = $data[0];

		// Check that offers exist.
		$this->assertArrayHasKey( 'offers', $product_data );
		$this->assertNotEmpty( $product_data['offers'] );

		// Get the first offer.
		$offer = $product_data['offers'][0];

		// Check that priceSpecification exists.
		$this->assertArrayHasKey( 'priceSpecification', $offer );
		$this->assertNotEmpty( $offer['priceSpecification'] );

		$price_spec = $offer['priceSpecification'][0];

		// valueAddedTaxIncluded should not be present when taxes are disabled.
		$this->assertArrayNotHasKey( 'valueAddedTaxIncluded', $price_spec );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test that valueAddedTaxIncluded is included in order structured data when taxes are enabled
	 *
	 * @return void
	 */
	public function test_order_structured_data_includes_vat_when_taxes_enabled(): void {
		// Enable taxes with prices inclusive of tax.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		// Create a simple product and order.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->save();

		// Generate structured data.
		$this->structured_data->generate_order_data( $order );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry (should be order).
		$this->assertNotEmpty( $data );
		$order_data = $data[0];

		// Check that priceSpecification exists and contains valueAddedTaxIncluded as a boolean.
		$this->assertArrayHasKey( 'priceSpecification', $order_data );
		$this->assertArrayHasKey( 'valueAddedTaxIncluded', $order_data['priceSpecification'] );
		$this->assertIsBool( $order_data['priceSpecification']['valueAddedTaxIncluded'] );
		$this->assertTrue( $order_data['priceSpecification']['valueAddedTaxIncluded'] );

		// Clean up.
		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * Test that valueAddedTaxIncluded is not included in order structured data when taxes are disabled
	 *
	 * @return void
	 */
	public function test_order_structured_data_excludes_vat_when_taxes_disabled(): void {
		// Disable taxes.
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Create a simple product and order.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->save();

		// Generate structured data.
		$this->structured_data->generate_order_data( $order );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry (should be order).
		$this->assertNotEmpty( $data );
		$order_data = $data[0];

		// Check that priceSpecification exists.
		$this->assertArrayHasKey( 'priceSpecification', $order_data );

		// valueAddedTaxIncluded should not be present when taxes are disabled.
		$this->assertArrayNotHasKey( 'valueAddedTaxIncluded', $order_data['priceSpecification'] );

		// Clean up.
		$order->delete( true );
		$product->delete( true );
	}
}
