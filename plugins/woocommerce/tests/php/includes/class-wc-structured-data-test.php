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
	 * Test simple product offer structured data includes offer-level price currency.
	 *
	 * @return void
	 */
	public function test_simple_product_offer_includes_offer_level_price_currency(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '97' );
		$product->set_price( '97' );
		$product->save();

		$this->structured_data->generate_product_data( $product );

		$data  = $this->structured_data->get_data();
		$offer = $data[0]['offers'][0];

		$this->assertEquals( '97.00', $offer['price'] );
		$this->assertEquals( get_woocommerce_currency(), $offer['priceCurrency'] );
		$this->assertEquals( get_woocommerce_currency(), $offer['priceSpecification'][0]['priceCurrency'] );
	}

	/**
	 * Test on-sale simple product offer reports the sale price at the offer top level.
	 *
	 * @return void
	 */
	public function test_simple_product_offer_on_sale_uses_sale_price_at_offer_level(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '100' );
		$product->set_sale_price( '70' );
		$product->set_price( '70' );
		$product->save();

		$this->structured_data->generate_product_data( $product );

		$data  = $this->structured_data->get_data();
		$offer = $data[0]['offers'][0];

		// The offer-level `price` should reflect the sale price, matching `priceSpecification[0]['price']`.
		$this->assertEquals( '70.00', $offer['price'] );
		$this->assertEquals( '70.00', $offer['priceSpecification'][0]['price'] );
		$this->assertEquals( get_woocommerce_currency(), $offer['priceCurrency'] );
	}
}
