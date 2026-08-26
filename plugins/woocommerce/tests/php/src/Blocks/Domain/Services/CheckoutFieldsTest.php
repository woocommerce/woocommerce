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
