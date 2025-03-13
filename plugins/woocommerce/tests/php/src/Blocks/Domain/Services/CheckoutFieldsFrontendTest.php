<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsFrontend;
use Automattic\WooCommerce\Blocks\Package;
use Exception;
use WC_Customer;
use WP_Error;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test \Automattic\WooCommerce\Blocks\Domain\Services\Hydration class.
 */
class CheckoutFieldsFrontendTest extends TestCase {
	/**
	 * System under test.
	 *
	 * @var CheckoutFieldsFrontend
	 */
	private $sut;

	/**
	 * Helper controller
	 *
	 * @var CheckoutFields
	 */
	private $controller;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_add_notice', [ $this, 'capture_notice' ] );
		add_filter( 'woocommerce_add_error', [ $this, 'capture_error' ] );
		add_filter( 'woocommerce_add_success', [ $this, 'capture_success' ] );

		$this->sut        = Package::container()->get( CheckoutFieldsFrontend::class );
		$this->controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		remove_filter( 'woocommerce_add_notice', [ $this, 'capture_notice' ] );
		remove_filter( 'woocommerce_add_error', [ $this, 'capture_error' ] );
		remove_filter( 'woocommerce_add_success', [ $this, 'capture_success' ] );
	}

	/**
	 * Capture success messages.
	 *
	 * @param string $message The message string.
	 */
	public function capture_success( string $message ): void {
		$this->capture_messages( $message, 'success' );
	}

	/**
	 * Capture error messages.
	 *
	 * @param string $message The message string.
	 */
	public function capture_error( string $message ): void {
		$this->capture_messages( $message, 'error' );
	}

	/**
	 * Capture notice messages.
	 *
	 * @param string $message The message string.
	 */
	public function capture_notice( string $message ): void {
		$this->capture_messages( $message, 'notice' );
	}

	/**
	 * Capture messages.
	 *
	 * @param string $message The message string.
	 * @param string $type The message type.
	 */
	private function capture_messages( string $message, string $type ): void {
		global $mocked_messages;
		$mocked_messages[] = [
			'message' => $message,
			'type'    => $type,
		];
	}


	/**
	 * @testDox Save account form fields.
	 */
	public function test_save_account_form_fields_contact_customer_data_store_exception() {

		$hash = uniqid();

		$mock_data_store = function () use ( $hash ) {
			throw new Exception( $hash ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		};

		add_filter( 'woocommerce_customer_data_store', $mock_data_store );

		global $mocked_messages;
		$mocked_messages = [];

		$this->sut->save_account_form_fields( 12345 );

		remove_filter( 'woocommerce_customer_data_store', $mock_data_store );

		$this->assertCount( 1, $mocked_messages );
		$this->assertEquals( sprintf( __( 'An error occurred while saving account details: %s', 'woocommerce' ), $hash ), $mocked_messages[0]['message'] ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		$this->assertEquals( 'error', $mocked_messages[0]['type'] );
	}

	/**
	 * @testDox Contact additional field validation error
	 */
	public function test_save_account_form_fields_contact_generic_validation_error() {

		woocommerce_register_additional_checkout_field(
			array(
				'id'                => 'mynamespace/generic_validation_error',
				'label'             => 'Optional field with validation',
				'location'          => 'contact',
				'required'          => false,
				'validate_callback' => function () {
					return new WP_Error(
						'generic_validation_error',
						'Generic validation error message'
					);
				},
			),
		);

		$_POST['_wc_other/mynamespace/generic_validation_error'] = 'value';
		global $mocked_messages;
		$mocked_messages = [];

		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 1, $mocked_messages );
		$this->assertEquals( 'Generic validation error message', $mocked_messages[0]['message'] );
		$this->assertEquals( 'error', $mocked_messages[0]['type'] );

		$value = $this->controller->get_field_from_object( 'mynamespace/generic_validation_error', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		$_POST['_wc_other/mynamespace/generic_validation_error'] = '';
		$mocked_messages = [];

		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 0, $mocked_messages );

		$value = $this->controller->get_field_from_object( 'mynamespace/generic_validation_error', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		__internal_woocommerce_blocks_deregister_checkout_field( 'mynamespace/generic_validation_error' );
	}

	/**
	 * @testDox Contact additional field location validation error
	 */
	public function test_save_account_form_fields_contact_location_validation_error() {

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'mynamespace/location_validation_error',
				'label'    => 'Impossible contact field',
				'location' => 'contact',
				'required' => false,
			),
		);

		$e_thrower = function () {
			throw new Exception( 'Location validation error message' );
		};

		$_POST['_wc_other/mynamespace/location_validation_error'] = 'value';
		global $mocked_messages;
		$mocked_messages = [];

		add_action( 'woocommerce_blocks_validate_location_contact_fields', $e_thrower );

		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 1, $mocked_messages );
		$this->assertStringContainsString( 'Location validation error message', $mocked_messages[0]['message'] );
		$this->assertEquals( 'error', $mocked_messages[0]['type'] );
		$value = $this->controller->get_field_from_object( 'mynamespace/required_field', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		__internal_woocommerce_blocks_deregister_checkout_field( 'mynamespace/location_validation_error' );

		remove_action( 'woocommerce_blocks_validate_location_contact_fields', $e_thrower );
	}

	/**
	 * @testDox Contact additional field required field test.
	 */
	public function test_save_account_form_fields_contact_save_required_field() {

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'mynamespace/required_field',
				'label'    => 'Required field',
				'location' => 'contact',
				'required' => true,
			),
		);

		$_POST['_wc_other/mynamespace/required_field'] = '';
		global $mocked_messages;
		$mocked_messages = [];

		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 1, $mocked_messages );
		$this->assertEquals( '<strong>Required field</strong> is required', $mocked_messages[0]['message'] );
		$this->assertEquals( 'error', $mocked_messages[0]['type'] );

		$value = $this->controller->get_field_from_object( 'mynamespace/required_field', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		$mocked_messages = [];
		$hash            = uniqid();
		$_POST['_wc_other/mynamespace/required_field'] = $hash;
		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 0, $mocked_messages );

		$value = $this->controller->get_field_from_object( 'mynamespace/required_field', new WC_Customer( 1 ) );
		$this->assertEquals( $hash, $value );

		__internal_woocommerce_blocks_deregister_checkout_field( 'mynamespace/required_field' );
	}

	/**
	 * @testDox Contact additional field save optional field test.
	 */
	public function test_save_account_form_fields_contact_save_optional_field() {

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'mynamespace/optional_field',
				'label'    => 'Optional field',
				'location' => 'contact',
				'required' => false,
			),
		);

		global $mocked_messages;
		$mocked_messages = [];

		$_POST['_wc_other/mynamespace/optional_field'] = '';
		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 0, $mocked_messages );

		$value = $this->controller->get_field_from_object( 'mynamespace/optional_field', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		$hash = uniqid();
		$_POST['_wc_other/mynamespace/optional_field'] = $hash;
		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 0, $mocked_messages );

		$value = $this->controller->get_field_from_object( 'mynamespace/optional_field', new WC_Customer( 1 ) );
		$this->assertEquals( $hash, $value );

		__internal_woocommerce_blocks_deregister_checkout_field( 'mynamespace/optional_field' );
	}

	/**
	 * @testDox Contact additional field validation error for an optional email field to ensure validation rules are applied.
	 */
	public function test_save_account_form_fields_contact_email_validation_error_optional_field() {
		woocommerce_register_additional_checkout_field(
			array(
				'id'                => 'mynamespace/email_validation_error',
				'label'             => 'Optional field with validation',
				'location'          => 'contact',
				'required'          => false,
				'sanitize_callback' => function ( $field_value ) {
					return sanitize_email( $field_value );
				},
				'validate_callback' => function ( $field_value ) {
					if ( ! is_email( $field_value ) ) {
						return new WP_Error(
							'email_validation_error',
							'Email validation error message'
						);
					}
				},
			),
		);

		$_POST['_wc_other/mynamespace/email_validation_error'] = 'invalidvalue';
		global $mocked_messages;
		$mocked_messages = [];

		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 1, $mocked_messages );
		$this->assertEquals( 'Email validation error message', $mocked_messages[0]['message'] );
		$this->assertEquals( 'error', $mocked_messages[0]['type'] );

		$value = $this->controller->get_field_from_object( 'mynamespace/email_validation_error', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		$_POST['_wc_other/mynamespace/email_validation_error'] = '';
		$mocked_messages                                       = [];

		$this->sut->save_account_form_fields( 1 );

		$this->assertCount( 0, $mocked_messages );

		$value = $this->controller->get_field_from_object( 'mynamespace/email_validation_error', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		__internal_woocommerce_blocks_deregister_checkout_field( 'mynamespace/email_validation_error' );
	}

	/**
	 * @testDox Optional field with existing value can be cleared by submitting empty value.
	 */
	public function test_save_account_form_fields_optional_field_can_be_cleared() {
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'mynamespace/optional_field',
				'label'    => 'Optional field',
				'location' => 'contact',
				'required' => false,
			),
		);

		// First save a value.
		$_POST['_wc_other/mynamespace/optional_field'] = 'initial value';
		$this->sut->save_account_form_fields( 1 );

		$value = $this->controller->get_field_from_object( 'mynamespace/optional_field', new WC_Customer( 1 ) );
		$this->assertEquals( 'initial value', $value );

		// Now clear the value.
		$_POST['_wc_other/mynamespace/optional_field'] = '';
		$this->sut->save_account_form_fields( 1 );

		$value = $this->controller->get_field_from_object( 'mynamespace/optional_field', new WC_Customer( 1 ) );
		$this->assertEquals( '', $value );

		__internal_woocommerce_blocks_deregister_checkout_field( 'mynamespace/optional_field' );
	}
}
