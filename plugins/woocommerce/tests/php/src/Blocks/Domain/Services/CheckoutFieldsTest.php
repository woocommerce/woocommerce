<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes\DateFieldType;
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
				'id'       => 'plugin-namespace/promo-date',
				'label'    => 'Promotion date',
				'location' => 'order',
				'type'     => 'date',
				'min'      => '2026-01-01',
				'max'      => '2026-12-31',
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
	 * @testdox Date fields can be registered.
	 */
	public function test_date_fields_can_be_registered() {
		$fields = $this->controller->get_additional_fields();

		$this->assertArrayHasKey( 'plugin-namespace/delivery-date', $fields, 'Date fields should be a supported field type.' );
		$this->assertSame( 'date', $fields['plugin-namespace/delivery-date']['type'] );
	}

	/**
	 * @testdox Date fields only accept a real calendar date in Y-m-d format.
	 *
	 * @testWith ["2026-08-26", false]
	 *           ["", false]
	 *           ["2026-02-31", true]
	 *           ["2026-8-6", true]
	 *           ["26-08-2026", true]
	 *           ["not-a-date", true]
	 *
	 * @param string $value       The submitted value.
	 * @param bool   $has_errors  Whether the value should be rejected.
	 */
	public function test_date_field_validation( string $value, bool $has_errors ) {
		$fields = $this->controller->get_additional_fields();
		$errors = $this->controller->validate_field( $fields['plugin-namespace/delivery-date'], $value );

		$this->assertSame( $has_errors, $errors->has_errors(), sprintf( 'Unexpected validation result for "%s".', $value ) );
	}

	/**
	 * @testdox Date field values are displayed using the site date format, in the site timezone.
	 *
	 * @testWith ["UTC", "F j, Y", "August 26, 2026"]
	 *           ["America/New_York", "Y-m-d", "2026-08-26"]
	 *           ["Pacific/Auckland", "Y-m-d", "2026-08-26"]
	 *
	 * @param string $timezone    The site timezone.
	 * @param string $date_format The site date format.
	 * @param string $expected    The expected formatted value.
	 */
	public function test_date_field_value_formatting( string $timezone, string $date_format, string $expected ) {
		update_option( 'timezone_string', $timezone );
		update_option( 'date_format', $date_format );

		$fields = $this->controller->get_additional_fields();
		$value  = $this->controller->format_additional_field_value( '2026-08-26', $fields['plugin-namespace/delivery-date'] );

		$this->assertSame( $expected, $value, 'The stored calendar date should never shift when it is formatted.' );
	}

	/**
	 * @testdox Date values that are not a real calendar date are displayed as stored.
	 *
	 * @testWith ["2026-02-31"]
	 *           ["2026-13-01"]
	 *           ["not-a-date"]
	 *           [""]
	 *
	 * @param string $value The stored value.
	 */
	public function test_invalid_date_field_value_is_not_reformatted( string $value ) {
		update_option( 'date_format', 'F j, Y' );

		$fields = $this->controller->get_additional_fields();

		$this->assertSame(
			$value,
			$this->controller->format_additional_field_value( $value, $fields['plugin-namespace/delivery-date'] ),
			'A value that is not a real date should be shown as stored rather than rolled forward.'
		);
	}

	/**
	 * @testdox Date constraints are exposed on the field as registered, not resolved.
	 */
	public function test_date_constraints_are_not_resolved_on_the_field() {
		$fields = $this->controller->get_additional_fields();

		$this->assertSame( '2026-01-01', $fields['plugin-namespace/promo-date']['min'] );
		$this->assertSame( '2026-12-31', $fields['plugin-namespace/promo-date']['max'] );

		// A resolved value here would freeze into any page cache holding the rendered form.
		$this->assertSame( 'P0D', $fields['plugin-namespace/appointment-date']['min'] );
		$this->assertSame( 'P30D', $fields['plugin-namespace/appointment-date']['max'] );
	}

	/**
	 * @testdox Date constraints are resolved against the current date in the store timezone.
	 */
	public function test_date_constraints_are_resolved_on_demand() {
		$fields    = $this->controller->get_additional_fields();
		$date_type = new DateFieldType();

		$this->assertSame(
			array(
				'min' => $this->date_relative_to_today( 'today' ),
				'max' => $this->date_relative_to_today( '+30 days' ),
			),
			$date_type->get_constraints( $fields['plugin-namespace/appointment-date'] )
		);

		$this->assertSame(
			array(
				'min' => '2026-01-01',
				'max' => '2026-12-31',
			),
			$date_type->get_constraints( $fields['plugin-namespace/promo-date'] )
		);

		$this->assertSame(
			array(
				'min' => null,
				'max' => null,
			),
			$date_type->get_constraints( $fields['plugin-namespace/delivery-date'] )
		);
	}

	/**
	 * @testdox Date fields without constraints are unbounded.
	 */
	public function test_date_field_without_constraints_has_no_bounds() {
		$field = $this->controller->get_additional_fields()['plugin-namespace/delivery-date'];

		$this->assertArrayNotHasKey( 'min', $field );
		$this->assertArrayNotHasKey( 'max', $field );
		$this->assertFalse( $this->controller->validate_field( $field, '1901-01-01' )->has_errors() );
		$this->assertFalse( $this->controller->validate_field( $field, '2222-12-31' )->has_errors() );
	}

	/**
	 * @testdox Absolute date constraints are enforced, inclusive of both bounds.
	 *
	 * @testWith ["2026-01-01", false]
	 *           ["2026-06-15", false]
	 *           ["2026-12-31", false]
	 *           ["2025-12-31", true]
	 *           ["2027-01-01", true]
	 *
	 * @param string $value      The submitted value.
	 * @param bool   $has_errors Whether the value should be rejected.
	 */
	public function test_absolute_date_constraints_are_enforced( string $value, bool $has_errors ) {
		$field  = $this->controller->get_additional_fields()['plugin-namespace/promo-date'];
		$errors = $this->controller->validate_field( $field, $value );

		$this->assertSame( $has_errors, $errors->has_errors(), sprintf( 'Unexpected validation result for "%s".', $value ) );
	}

	/**
	 * @testdox Relative date constraints are enforced against the current date.
	 *
	 * @testWith ["today", false]
	 *           ["+1 day", false]
	 *           ["+30 days", false]
	 *           ["-1 day", true]
	 *           ["+31 days", true]
	 *
	 * @param string $offset     The submitted value, relative to today.
	 * @param bool   $has_errors Whether the value should be rejected.
	 */
	public function test_relative_date_constraints_are_enforced( string $offset, bool $has_errors ) {
		$field  = $this->controller->get_additional_fields()['plugin-namespace/appointment-date'];
		$errors = $this->controller->validate_field( $field, $this->date_relative_to_today( $offset ) );

		$this->assertSame( $has_errors, $errors->has_errors(), sprintf( 'Unexpected validation result for "%s".', $offset ) );
	}

	/**
	 * @testdox An out of range date is rejected with a message naming the boundary in the site date format.
	 */
	public function test_out_of_range_date_error_message() {
		update_option( 'date_format', 'F j, Y' );

		$field = $this->controller->get_additional_fields()['plugin-namespace/promo-date'];

		$this->assertSame(
			'Please provide a Promotion date on or after January 1, 2026.',
			$this->controller->validate_field( $field, '2025-12-31' )->get_error_message()
		);
		$this->assertSame(
			'Please provide a Promotion date on or before December 31, 2026.',
			$this->controller->validate_field( $field, '2027-01-01' )->get_error_message()
		);
	}

	/**
	 * @testdox A date field with an unparseable constraint is not registered.
	 *
	 * @testWith ["min", "not-a-date"]
	 *           ["max", "2026-02-31"]
	 *           ["min", "2026-8-6"]
	 *           ["max", ""]
	 *           ["min", "today"]
	 *           ["max", "+1 day"]
	 *           ["min", "PT1H"]
	 *           ["max", 20260826]
	 *
	 * @param string $constraint The constraint being set.
	 * @param mixed  $value      The invalid value.
	 */
	public function test_date_field_with_invalid_constraint_is_not_registered( string $constraint, $value ) {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		$field                = array(
			'id'       => 'plugin-namespace/invalid-constraint',
			'label'    => 'Invalid constraint',
			'location' => 'order',
			'type'     => 'date',
		);
		$field[ $constraint ] = $value;

		woocommerce_register_additional_checkout_field( $field );

		$this->assertArrayNotHasKey( 'plugin-namespace/invalid-constraint', $this->controller->get_additional_fields() );
	}

	/**
	 * @testdox A date field whose min resolves later than its max is not registered.
	 *
	 * @testWith ["2026-12-31", "2026-01-01"]
	 *           ["P2M", "P1M"]
	 *           ["-P13Y", "-P18Y"]
	 *
	 * @param string $min The min constraint.
	 * @param string $max The max constraint.
	 */
	public function test_date_field_with_inverted_constraints_is_not_registered( string $min, string $max ) {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'plugin-namespace/inverted-constraints',
				'label'    => 'Inverted constraints',
				'location' => 'order',
				'type'     => 'date',
				'min'      => $min,
				'max'      => $max,
			)
		);

		$this->assertArrayNotHasKey( 'plugin-namespace/inverted-constraints', $this->controller->get_additional_fields() );
	}

	/**
	 * @testdox An ordered min/max pair is registered, as is a mixed absolute/duration pair.
	 *
	 * @testWith ["P1D", "P1M"]
	 *           ["P28D", "P1M"]
	 *           ["P12M", "P370D"]
	 *           ["-P18Y", "-P13Y"]
	 *           ["P0D", "2999-12-31"]
	 *           ["2020-01-01", "P0D"]
	 *
	 * @param string $min The min constraint.
	 * @param string $max The max constraint.
	 */
	public function test_date_field_with_ordered_constraints_is_registered( string $min, string $max ) {
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'plugin-namespace/ordered-constraints',
				'label'    => 'Ordered constraints',
				'location' => 'order',
				'type'     => 'date',
				'min'      => $min,
				'max'      => $max,
			)
		);

		$this->assertArrayHasKey( 'plugin-namespace/ordered-constraints', $this->controller->get_additional_fields() );
	}

	/**
	 * Returns a Y-m-d date, resolved independently of the code under test.
	 *
	 * @param string $expression An absolute Y-m-d date, or an expression relative to today such as "+1 day".
	 * @return string
	 */
	private function date_relative_to_today( string $expression ): string {
		return ( new \DateTime( $expression, wp_timezone() ) )->format( 'Y-m-d' );
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
