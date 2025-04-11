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
}
