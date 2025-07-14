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

	public function test_generate_product_data_for_variable_product_on_sale() {
		$size_attribute = new WC_Product_Attribute();
		$size_attribute->set_name( 'Size' );
		$size_attribute->set_options( array( 'Small', 'Large' ) );
		$size_attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Sample T-Shirt' );
		$product->set_status( 'publish' );
		$product->set_attributes( array( $size_attribute ) );
		$product_id = $product->save();

		$variation1 = new WC_Product_Variation();
		$variation1->set_parent_id( $product_id );
		$variation1->set_attributes( array( 'size' => 'Small' ) );
		$variation1->set_regular_price( 25.00 );
		$variation1->set_sale_price( 20.00 );
		$variation1->set_status( 'publish' );
		$variation1->save();

		$variation2 = new WC_Product_Variation();
		$variation2->set_parent_id( $product_id );
		$variation2->set_attributes( array( 'size' => 'Large' ) );
		$variation2->set_regular_price( 35.00 );
		$variation2->set_sale_price( 30.00 );
		$variation2->set_status( 'publish' );
		$variation2->save();

		$variation2 = new WC_Product_Variation();
		$variation2->set_parent_id( $product_id );
		$variation2->set_attributes( array( 'size' => 'Large' ) );
		$variation2->set_regular_price( 15.00 );
		$variation2->set_sale_price( 10.00 );
		$variation2->set_status( 'publish' );
		$variation2->save();

		$product = wc_get_product( $product_id );
		$product->set_price( 10 );
		$product->save();

		$this->structured_data->generate_product_data( $product );
		$data = $this->structured_data->get_data();
		// var_dump( $data );
		// die;
		$this->assertEquals(
			'UnitPriceSpecification',
			$data[0]['offers'][0]['priceSpecification'][0]['@type']
		);
		$this->assertEquals(
			'10.00',
			$data[0]['offers'][0]['priceSpecification'][0]['price']
		);
	}
}
