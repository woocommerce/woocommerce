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
	 * Test that the generated Product structured data exposes `priceCurrency`
	 * at the top-level `offers` object for simple products, in addition to the
	 * value nested inside `priceSpecification`.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/60652
	 * where Google Search Console reports a critical "Missing field priceCurrency"
	 * error for products whose currency was only present inside `priceSpecification`.
	 *
	 * @return void
	 */
	public function test_simple_product_offer_includes_top_level_price_currency(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '97.00',
			)
		);

		$this->structured_data->generate_product_data( $product );
		$data = $this->structured_data->get_structured_data( array( 'product' ) );

		$this->assertNotEmpty( $data, 'Expected Product structured data to be generated.' );
		$this->assertArrayHasKey( 'offers', $data[0] );

		$offer = $data[0]['offers'][0];

		// The fix: priceCurrency must be present at the top-level offer object.
		$this->assertArrayHasKey( 'priceCurrency', $offer );
		$this->assertSame( get_woocommerce_currency(), $offer['priceCurrency'] );

		// And the nested value inside priceSpecification should still be intact.
		$this->assertArrayHasKey( 'priceSpecification', $offer );
		$this->assertSame( get_woocommerce_currency(), $offer['priceSpecification'][0]['priceCurrency'] );

		$product->delete( true );
	}
}
