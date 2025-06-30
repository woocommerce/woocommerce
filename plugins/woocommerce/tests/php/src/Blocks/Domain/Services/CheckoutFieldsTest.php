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
}
