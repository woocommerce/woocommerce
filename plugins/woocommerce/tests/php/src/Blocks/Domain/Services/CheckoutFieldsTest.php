<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use WP_UnitTestCase;

/**
 * Tests for CheckoutFields class.
 */
class CheckoutFieldsTest extends WP_UnitTestCase {
	/**
	 * The system under test.
	 *
	 * @var CheckoutFields
	 */
	private $controller;

	/**
	 * Setup test case.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->register_fields();
		$this->controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * Tear down Rest API server and remove fields.
	 */
	public function tearDown(): void {
		$this->unregister_fields();
		parent::tearDown();
	}

	/**
	 * Register fields for testing.
	 */
	private function register_fields() {
		$this->fields = array(
			array(
				'id'                => 'plugin-namespace/gov-id',
				'label'             => 'Government ID',
				'location'          => 'address',
				'type'              => 'text',
				'required'          => true,
				'attributes'        => array(
					'title'          => 'This is a gov id',
					'autocomplete'   => 'gov-id',
					'autocapitalize' => 'none',
					'maxLength'      => '30',
				),
				'sanitize_callback' => function ( $value ) {
					return trim( $value );
				},
				'validate_callback' => function ( $value ) {
					return strlen( $value ) > 3;
				},
			),
			array(
				'id'       => 'plugin-namespace/job-function',
				'label'    => 'What is your main role at your company?',
				'location' => 'contact',
				'required' => true,
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => 'Director',
						'value' => 'director',
					),
					array(
						'label' => 'Engineering',
						'value' => 'engineering',
					),
					array(
						'label' => 'Customer Support',
						'value' => 'customer-support',
					),
					array(
						'label' => 'Other',
						'value' => 'other',
					),
				),
			),
			array(
				'id'       => 'plugin-namespace/leave-on-porch',
				'label'    => __( 'Please leave my package on the porch if I\'m not home', 'woocommerce' ),
				'location' => 'order',
				'type'     => 'checkbox',
			),
			array(
				'id'       => 'plugin-namespace/delivery-date',
				'label'    => 'Preferred delivery date',
				'location' => 'order',
				'type'     => 'date',
			),
			array(
				'id'       => 'plugin-namespace/appointment-date',
				'label'    => 'Appointment date',
				'location' => 'order',
				'type'     => 'date',
				'min'      => 'P0D',
				'max'      => 'P30D',
			),
			array(
				'id'         => 'namespace/vat-number',
				'label'      => 'VAT Number',
				'location'   => 'address',
				'required'   => true,
				'hidden'     => array(
					'customer' => array(
						'properties' => array(
							'address' => array(
								'properties' => array(
									'country' => array(
										'type' => 'string',
										'not'  => array(
											'enum' => array_merge( WC()->countries->get_european_union_countries( 'eu_vat' ), array( 'GB' ) ),
										),
									),
								),
							),
						),
					),
				),
				'validation' => array(
					'type'    => 'string',
					'pattern' => '^[A-Z]{2}[0-9A-Z]{2,12}$',
				),
			),
		);
		array_map( 'woocommerce_register_additional_checkout_field', $this->fields );
	}

	/**
	 * Unregister fields after testing.
	 */
	private function unregister_fields() {
		$fields = $this->controller->get_additional_fields();
		array_map( '__internal_woocommerce_blocks_deregister_checkout_field', array_keys( $fields ) );
	}

	/**
	 * Test get_contextual_fields_for_location returns correct fields for billing location.
	 */
	public function test_get_contextual_fields_for_location_address() {
		$fields = $this->controller->get_contextual_fields_for_location( 'address' );

		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'plugin-namespace/gov-id', $fields );
		$this->assertArrayHasKey( 'namespace/vat-number', $fields );
	}

	/**
	 * Test get_contextual_fields_for_location returns correct fields for billing location.
	 */
	public function test_get_contextual_fields_for_location_contact() {
		$fields = $this->controller->get_contextual_fields_for_location( 'contact' );

		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'plugin-namespace/job-function', $fields );
	}

	/**
	 * Test get_contextual_fields_for_location returns correct fields for billing location.
	 */
	public function test_get_contextual_fields_for_location_order() {
		$fields = $this->controller->get_contextual_fields_for_location( 'order' );

		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'plugin-namespace/leave-on-porch', $fields );
	}

	/**
	 * Test get_contextual_fields_for_location returns correct fields for billing location.
	 */
	public function test_get_contextual_fields_for_location_address_with_context() {
		$customer        = \WC_Helper_Customer::create_mock_customer();
		$document_object = new DocumentObject();
		$document_object->set_context( 'shipping_address' );

		// Test VAT field is shown with UK address.
		$customer->set_shipping_country( 'GB' );
		$customer->set_shipping_state( '' );
		$customer->set_shipping_postcode( 'PP121PP' );
		$document_object->set_customer( $customer );

		$fields = $this->controller->get_contextual_fields_for_location( 'address', $document_object );
		$this->assertArrayHasKey( 'plugin-namespace/gov-id', $fields );
		$this->assertArrayHasKey( 'namespace/vat-number', $fields );

		// Test VAT field is hidden with US address.
		$customer->set_shipping_country( 'US' );
		$customer->set_shipping_state( 'CA' );
		$customer->set_shipping_postcode( '90210' );
		$document_object->set_customer( $customer );

		$fields = $this->controller->get_contextual_fields_for_location( 'address', $document_object );
		$this->assertArrayHasKey( 'plugin-namespace/gov-id', $fields );
		$this->assertArrayNotHasKey( 'namespace/vat-number', $fields );
	}

	/**
	 * @testdox Date fields can be registered, with their constraints stored as registered.
	 */
	public function test_date_fields_can_be_registered() {
		$fields = $this->controller->get_additional_fields();

		$this->assertArrayHasKey( 'plugin-namespace/delivery-date', $fields, 'Date fields should be a supported field type.' );
		$this->assertSame( 'date', $fields['plugin-namespace/delivery-date']['type'] );

		// The constraint rules themselves are covered by DateFieldTypeTest; this only checks registration carries them through unresolved.
		$this->assertSame( 'P0D', $fields['plugin-namespace/appointment-date']['min'] );
		$this->assertSame( 'P30D', $fields['plugin-namespace/appointment-date']['max'] );
	}

	/**
	 * @testdox Date field validation is delegated to the date field type.
	 */
	public function test_date_field_validation_is_delegated() {
		$field = $this->controller->get_additional_fields()['plugin-namespace/delivery-date'];

		$this->assertFalse( $this->controller->validate_field( $field, '2025-08-26' )->has_errors() );
		$this->assertTrue( $this->controller->validate_field( $field, '2025-02-31' )->has_errors() );
	}

	/**
	 * @testdox A date field whose constraints the date field type rejects is not registered.
	 */
	public function test_date_field_with_invalid_constraint_is_not_registered() {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'plugin-namespace/invalid-constraint',
				'label'    => 'Invalid constraint',
				'location' => 'order',
				'type'     => 'date',
				'min'      => 'not-a-date',
			)
		);

		$this->assertArrayNotHasKey( 'plugin-namespace/invalid-constraint', $this->controller->get_additional_fields() );
	}

	/**
	 * @testdox A field whose rules reference another field with $data is registered.
	 */
	public function test_field_with_data_reference_rule_is_registered() {
		$this->register_stay_dates();

		$this->assertArrayHasKey( 'plugin-namespace/check-out', $this->controller->get_additional_fields(), 'A $data reference in a validation rule should not be rejected as an invalid schema.' );
	}

	/**
	 * @testdox A $data rule orders one date field against another.
	 *
	 * @testWith ["2026-05-01", "2026-05-02", true]
	 *           ["2026-05-01", "2026-05-01", false]
	 *           ["2026-05-04", "2026-05-01", false]
	 *
	 * @param string $check_in  The value of the referenced field.
	 * @param string $check_out The value of the field carrying the rule.
	 * @param bool   $is_valid  Whether the pair should pass validation.
	 */
	public function test_data_reference_rule_compares_two_date_fields( string $check_in, string $check_out, bool $is_valid ) {
		$this->register_stay_dates();

		$field = $this->controller->get_additional_fields()['plugin-namespace/check-out'];

		$this->assertSame(
			$is_valid,
			true === $this->controller->is_valid_field( $field, $this->stay_document_object( $check_in, $check_out ) ),
			sprintf( 'Check-out %s against check-in %s was not judged as expected.', $check_out, $check_in )
		);
	}

	/**
	 * @testdox A blank date only skips the ordering rule on the side that carries it.
	 *
	 * Opis reports "Invalid $data" whenever a $data pointer resolves to something that is not a
	 * number, so a blank check-in fails the check-out field rather than being skipped, even though
	 * the rule allows null. A blank check-out is skipped, because the keyword only applies to numbers.
	 *
	 * @testWith ["2026-05-01", "", true]
	 *           ["", "2026-05-04", false]
	 *
	 * @param string $check_in  The value of the referenced field.
	 * @param string $check_out The value of the field carrying the rule.
	 * @param bool   $is_valid  Whether the pair should pass validation.
	 */
	public function test_a_blank_date_only_skips_the_rule_on_its_own_side( string $check_in, string $check_out, bool $is_valid ) {
		$this->register_stay_dates( array( 'integer', 'null' ) );

		$field = $this->controller->get_additional_fields()['plugin-namespace/check-out'];

		$this->assertSame( $is_valid, true === $this->controller->is_valid_field( $field, $this->stay_document_object( $check_in, $check_out ) ) );
	}

	/**
	 * @testdox Each field type contributes its own keywords to the REST API value schema.
	 */
	public function test_prepare_field_value_schema() {
		$fields = $this->controller->get_additional_fields();

		$date = $this->controller->prepare_field_value_schema( array( 'type' => 'string' ), $fields['plugin-namespace/delivery-date'] );
		$this->assertSame( '^(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))?$', $date['pattern'] );

		$checkbox = $this->controller->prepare_field_value_schema( array( 'type' => 'string' ), $fields['plugin-namespace/leave-on-porch'] );
		$this->assertSame( 'boolean', $checkbox['type'] );

		$select = $this->controller->prepare_field_value_schema( array( 'type' => 'string' ), $fields['plugin-namespace/job-function'] );
		$this->assertSame( array( 'director', 'engineering', 'customer-support', 'other' ), $select['enum'] );

		$text = $this->controller->prepare_field_value_schema( array( 'type' => 'string' ), $fields['plugin-namespace/gov-id'] );
		$this->assertSame( array( 'type' => 'string' ), $text, 'A type with no keywords of its own should leave the schema alone.' );
	}

	/**
	 * Registers a pair of date fields where the second must fall after the first.
	 *
	 * @param string|array $rule_type The type the ordering rule accepts.
	 */
	private function register_stay_dates( $rule_type = 'integer' ) {
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'plugin-namespace/check-in',
				'label'    => 'Check-in',
				'location' => 'order',
				'type'     => 'date',
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'         => 'plugin-namespace/check-out',
				'label'      => 'Check-out',
				'location'   => 'order',
				'type'       => 'date',
				'validation' => array(
					'type'             => $rule_type,
					'exclusiveMinimum' => array( '$data' => '1/plugin-namespace~1check-in' ),
				),
			)
		);
	}

	/**
	 * Builds a document object holding the two stay dates as they reach rule evaluation.
	 *
	 * @param string $check_in  The value of the referenced field.
	 * @param string $check_out The value of the field carrying the rule.
	 * @return DocumentObject
	 */
	private function stay_document_object( string $check_in, string $check_out ): DocumentObject {
		$document_object = new DocumentObject(
			array(
				'checkout' => array(
					'additional_fields' => array(
						'plugin-namespace/check-in'  => $check_in,
						'plugin-namespace/check-out' => $check_out,
					),
				),
			)
		);
		$document_object->set_customer( new \WC_Customer( 0 ) );

		return $document_object;
	}

	/**
	 * Registering a field before after_setup_theme warns the developer.
	 */
	public function test_registering_before_after_setup_theme_triggers_notice() {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		$saved_action = $GLOBALS['wp_actions']['after_setup_theme'] ?? null;
		unset( $GLOBALS['wp_actions']['after_setup_theme'] );

		try {
			woocommerce_register_additional_checkout_field(
				array(
					'id'       => 'test-namespace/early-field',
					'label'    => 'Early field',
					'location' => 'contact',
				)
			);
		} finally {
			if ( null !== $saved_action ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the count cleared above to simulate registering before after_setup_theme.
				$GLOBALS['wp_actions']['after_setup_theme'] = $saved_action;
			}
			__internal_woocommerce_blocks_deregister_checkout_field( 'test-namespace/early-field' );
		}
	}
}
