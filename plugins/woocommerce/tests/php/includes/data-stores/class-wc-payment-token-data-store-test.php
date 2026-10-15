<?php
declare( strict_types = 1 );

/**
 * Class WC_Payment_Token_Data_Store_Test file.
 *
 * @package WooCommerce\Tests\DataStores
 */

/**
 * Tests for the WC_Payment_Token_Data_Store class.
 */
class WC_Payment_Token_Data_Store_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Payment_Token_Data_Store
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Payment_Token_Data_Store();
	}

	/**
	 * @testdox Should throw a data exception listing the invalid fields when creating an invalid token.
	 */
	public function test_create_throws_data_exception_with_invalid_fields(): void {
		$token = new WC_Payment_Token_CC();
		$token->set_token( 'tok_123' );
		$token->set_last4( '4242' );
		$token->set_expiry_year( '30' );
		$token->set_expiry_month( '08' );

		$exception = null;
		try {
			$this->sut->create( $token );
		} catch ( WC_Data_Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( WC_Data_Exception::class, $exception, 'Creating an invalid token should throw a WC_Data_Exception' );
		$this->assertSame( 'woocommerce_invalid_payment_token_fields', $exception->getErrorCode() );
		$this->assertSame( 'Invalid or missing payment token fields.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame(
			array(
				'status'         => 0,
				'invalid_fields' => array(
					'expiry_year' => '30',
					'card_type'   => '',
				),
			),
			$exception->getErrorData()
		);
		$this->assertSame( 0, $token->get_id(), 'An invalid token should not be inserted' );
	}

	/**
	 * @testdox Should throw a data exception listing the invalid fields when updating a token with invalid changes.
	 */
	public function test_update_throws_data_exception_with_invalid_fields(): void {
		$token = WC_Helper_Payment_Token::create_eCheck_token();
		$token->set_last4( '' );

		$exception = null;
		try {
			$this->sut->update( $token );
		} catch ( WC_Data_Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( WC_Data_Exception::class, $exception, 'Updating a token with invalid changes should throw a WC_Data_Exception' );
		$this->assertSame( 'woocommerce_invalid_payment_token_fields', $exception->getErrorCode() );
		$this->assertSame( 'Invalid or missing payment token fields.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame(
			array(
				'status'         => 0,
				'invalid_fields' => array( 'last4' => '' ),
			),
			$exception->getErrorData()
		);

		$stored_token = new WC_Payment_Token_ECheck( $token->get_id() );
		$this->assertSame( '1234', $stored_token->get_last4(), 'The stored token should keep its previous value' );
	}

	/**
	 * @testdox Should throw a generic exception when a token fails validation without reporting invalid fields.
	 */
	public function test_throws_generic_exception_when_no_invalid_fields_are_reported(): void {
		// An extension token that adds its own validate() rule without overriding get_invalid_token_fields().
		// phpcs:disable Squiz.Commenting
		$token = new class() extends WC_Payment_Token {
			protected $type = 'custom';

			public function validate() {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting
		$token->set_token( 'tok_123' );

		$exception = null;
		try {
			$this->sut->create( $token );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertInstanceOf( Exception::class, $exception, 'Creating an invalid token should throw an exception' );
		$this->assertSame( 'Invalid or missing payment token fields.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNotInstanceOf( WC_Data_Exception::class, $exception, 'Without reported invalid fields there is nothing to attach to a WC_Data_Exception' );
	}

	/**
	 * @testdox Should save a token whose validate() override passes even when the core field checks fail.
	 */
	public function test_create_respects_validate_override(): void {
		// An extension token that relaxes the core card field requirements.
		// phpcs:disable Squiz.Commenting
		$token = new class() extends WC_Payment_Token_CC {
			public function validate() {
				return true;
			}
		};
		// phpcs:enable Squiz.Commenting
		$token->set_token( 'tok_123' );

		$this->sut->create( $token );

		$this->assertGreaterThan( 0, $token->get_id(), 'A token that passes validate() should be inserted' );
	}
}
