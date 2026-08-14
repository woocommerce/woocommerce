<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsAdmin;
use Automattic\WooCommerce\Blocks\Package;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the CheckoutFieldsAdmin class.
 */
class CheckoutFieldsAdminTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CheckoutFieldsAdmin
	 */
	private $sut;

	/**
	 * Checkout fields controller.
	 *
	 * @var CheckoutFields
	 */
	private $controller;

	/**
	 * Field IDs registered by the test.
	 *
	 * @var string[]
	 */
	private $registered_fields = array();

	/**
	 * Whether this test registered the admin hooks.
	 *
	 * @var bool
	 */
	private $registered_hooks = false;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut        = Package::container()->get( CheckoutFieldsAdmin::class );
		$this->controller = Package::container()->get( CheckoutFields::class );

		if ( false === has_filter( 'woocommerce_admin_billing_fields', array( $this->sut, 'admin_address_fields' ) ) ) {
			$this->sut->init();
			$this->registered_hooks = true;
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->registered_fields as $field_id ) {
			__internal_woocommerce_blocks_deregister_checkout_field( $field_id );
		}

		if ( $this->registered_hooks ) {
			remove_filter( 'woocommerce_admin_billing_fields', array( $this->sut, 'admin_address_fields' ), 10 );
			remove_filter( 'woocommerce_admin_billing_fields', array( $this->sut, 'admin_contact_fields' ), 10 );
			remove_filter( 'woocommerce_admin_shipping_fields', array( $this->sut, 'admin_address_fields' ), 10 );
			remove_filter( 'woocommerce_admin_shipping_fields', array( $this->sut, 'admin_order_fields' ), 10 );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should inject formatted fields and persist admin updates in the correct groups.
	 */
	public function test_injects_and_updates_additional_fields_for_each_admin_group(): void {
		$address_field = 'test-namespace/delivery-note';
		$contact_field = 'test-namespace/contact-method';
		$order_field   = 'test-namespace/gift-wrap';

		$this->register_checkout_field(
			array(
				'id'       => $address_field,
				'label'    => 'Delivery note',
				'location' => 'address',
				'type'     => 'text',
			)
		);
		$this->register_checkout_field(
			array(
				'id'       => $contact_field,
				'label'    => 'Preferred contact method',
				'location' => 'contact',
				'type'     => 'select',
				'options'  => array(
					array(
						'label' => 'Email',
						'value' => 'email',
					),
					array(
						'label' => 'Phone',
						'value' => 'phone',
					),
				),
			)
		);
		$this->register_checkout_field(
			array(
				'id'       => $order_field,
				'label'    => 'Add gift wrap',
				'location' => 'order',
				'type'     => 'checkbox',
			)
		);

		$order = \WC_Helper_Order::create_order();
		$order->set_created_via( 'store-api' );
		$this->controller->persist_field_for_order( $address_field, 'Reception', $order, 'billing', false );
		$this->controller->persist_field_for_order( $address_field, 'Side door', $order, 'shipping', false );
		$this->controller->persist_field_for_order( $contact_field, 'email', $order, 'other', false );
		$this->controller->persist_field_for_order( $order_field, true, $order, 'other', false );
		$order->save();

		$base_fields = array( 'state' => array( 'label' => 'State' ) );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing admin filter to exercise its callbacks, not declaring a new hook.
		$billing_fields = apply_filters( 'woocommerce_admin_billing_fields', $base_fields, $order, 'edit' );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing an existing admin filter to exercise its callbacks, not declaring a new hook.
		$shipping_fields     = apply_filters( 'woocommerce_admin_shipping_fields', $base_fields, $order, 'edit' );
		$billing_address_id  = '_wc_billing/' . $address_field;
		$shipping_address_id = '_wc_shipping/' . $address_field;
		$contact_admin_id    = '_wc_other/' . $contact_field;
		$order_admin_id      = '_wc_other/' . $order_field;
		$billing_address     = $this->find_field_by_id( $billing_fields, $billing_address_id );
		$shipping_address    = $this->find_field_by_id( $shipping_fields, $shipping_address_id );

		$this->assertSame( $base_fields['state'], $billing_fields['state'] ?? null, 'The existing billing state field should remain unchanged.' );
		$this->assertSame( $base_fields['state'], $shipping_fields['state'] ?? null, 'The existing shipping state field should remain unchanged.' );
		$this->assert_field_follows_state( $billing_fields, $billing_address_id, 'billing' );
		$this->assert_field_follows_state( $shipping_fields, $shipping_address_id, 'shipping' );
		$this->assertSame( $contact_field, array_key_last( $billing_fields ), 'Contact fields should be appended to the billing field list.' );
		$this->assertSame( $order_field, array_key_last( $shipping_fields ), 'Order fields should be appended to the shipping field list.' );
		$this->assertSame( 1, $this->count_fields_by_id( $billing_fields, $billing_address_id ), 'The billing address field should be injected exactly once.' );
		$this->assertSame( 1, $this->count_fields_by_id( $billing_fields, $contact_admin_id ), 'The contact field should be injected exactly once in billing.' );
		$this->assertSame( 0, $this->count_fields_by_id( $billing_fields, $shipping_address_id ), 'The shipping address field should not be injected in billing.' );
		$this->assertSame( 0, $this->count_fields_by_id( $billing_fields, $order_admin_id ), 'The order field should not be injected in billing.' );
		$this->assertSame( 1, $this->count_fields_by_id( $shipping_fields, $shipping_address_id ), 'The shipping address field should be injected exactly once.' );
		$this->assertSame( 1, $this->count_fields_by_id( $shipping_fields, $order_admin_id ), 'The order field should be injected exactly once in shipping.' );
		$this->assertSame( 0, $this->count_fields_by_id( $shipping_fields, $billing_address_id ), 'The billing address field should not be injected in shipping.' );
		$this->assertSame( 0, $this->count_fields_by_id( $shipping_fields, $contact_admin_id ), 'The contact field should not be injected in shipping.' );

		$this->assert_field_properties(
			array(
				'id'              => $billing_address_id,
				'label'           => 'Delivery note',
				'value'           => 'Reception',
				'type'            => 'text',
				'update_callback' => array( $this->sut, 'update_callback' ),
				'show'            => true,
				'wrapper_class'   => 'form-field-wide',
			),
			$billing_address,
			'Billing address fields should be injected with their current value and billing-prefixed ID.'
		);
		$this->assert_field_properties(
			array(
				'id'              => $shipping_address_id,
				'label'           => 'Delivery note',
				'value'           => 'Side door',
				'type'            => 'text',
				'update_callback' => array( $this->sut, 'update_callback' ),
				'show'            => true,
				'wrapper_class'   => 'form-field-wide',
			),
			$shipping_address,
			'Shipping address fields should be injected with their current value and shipping-prefixed ID.'
		);
		$this->assert_field_properties(
			array(
				'id'              => $contact_admin_id,
				'label'           => 'Preferred contact method',
				'value'           => 'email',
				'type'            => 'select',
				'update_callback' => array( $this->sut, 'update_callback' ),
				'show'            => true,
				'wrapper_class'   => 'form-field-wide',
				'options'         => array(
					'email' => 'Email',
					'phone' => 'Phone',
				),
			),
			$billing_fields[ $contact_field ],
			'Contact select fields should expose their labels, options, current value, and update callback.'
		);
		$this->assert_field_properties(
			array(
				'id'              => $order_admin_id,
				'label'           => 'Add gift wrap',
				'value'           => true,
				'type'            => 'checkbox',
				'update_callback' => array( $this->sut, 'update_callback' ),
				'show'            => true,
				'wrapper_class'   => 'form-field-wide',
				'checked_value'   => '1',
				'unchecked_value' => '0',
			),
			$shipping_fields[ $order_field ],
			'Order checkbox fields should expose their checked and unchecked representations.'
		);

		call_user_func( $billing_address['update_callback'], $billing_address['id'], 'Warehouse', $order );
		call_user_func( $shipping_address['update_callback'], $shipping_address['id'], 'Loading bay', $order );
		call_user_func( $billing_fields[ $contact_field ]['update_callback'], $billing_fields[ $contact_field ]['id'], 'phone', $order );
		call_user_func( $shipping_fields[ $order_field ]['update_callback'], $shipping_fields[ $order_field ]['id'], '0', $order );
		$order->save();

		$reloaded_order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $reloaded_order, 'The updated order should reload from storage.' );
		if ( ! $reloaded_order instanceof WC_Order ) {
			throw new \RuntimeException( 'The updated order could not be reloaded from storage.' );
		}
		$this->assertSame( 'Warehouse', $this->controller->get_field_from_object( $address_field, $reloaded_order, 'billing' ), 'Billing updates should remain in the billing group.' );
		$this->assertSame( 'Loading bay', $this->controller->get_field_from_object( $address_field, $reloaded_order, 'shipping' ), 'Shipping updates should remain in the shipping group.' );
		$this->assertSame( 'phone', $this->controller->get_field_from_object( $contact_field, $reloaded_order, 'other' ), 'Contact updates should remain in the other group.' );
		$this->assertFalse( $this->controller->get_field_from_object( $order_field, $reloaded_order, 'other' ), 'Unchecked order fields should reload as false from the other group.' );
	}

	/**
	 * Register a checkout field and track it for cleanup.
	 *
	 * @param array $field Field registration arguments.
	 */
	private function register_checkout_field( array $field ): void {
		woocommerce_register_additional_checkout_field( $field );
		$this->registered_fields[] = $field['id'];
	}

	/**
	 * Assert selected field properties without rejecting compatible additions.
	 *
	 * @param array  $expected Expected field properties.
	 * @param array  $actual Actual field properties.
	 * @param string $message Assertion context.
	 */
	private function assert_field_properties( array $expected, array $actual, string $message ): void {
		foreach ( $expected as $property => $value ) {
			$this->assertArrayHasKey( $property, $actual, sprintf( '%s Missing property: %s.', $message, $property ) );
			$this->assertSame( $value, $actual[ $property ], sprintf( '%s Unexpected property: %s.', $message, $property ) );
		}
	}

	/**
	 * Assert that an injected address field immediately follows the state field.
	 *
	 * @param array  $fields Admin field definitions.
	 * @param string $field_id Generated address field ID.
	 * @param string $group Address group name.
	 */
	private function assert_field_follows_state( array $fields, string $field_id, string $group ): void {
		$field_keys   = array_keys( $fields );
		$field_values = array_values( $fields );
		$state_index  = array_search( 'state', $field_keys, true );

		$this->assertNotFalse( $state_index, sprintf( 'The %s state field should remain in the field list.', $group ) );
		$this->assertSame(
			$field_id,
			$field_values[ false === $state_index ? 0 : $state_index + 1 ]['id'] ?? null,
			sprintf( 'The %s address field should be injected immediately after state.', $group )
		);
	}

	/**
	 * Count formatted admin fields with a generated ID.
	 *
	 * @param array  $fields Admin field definitions.
	 * @param string $field_id Generated field ID.
	 */
	private function count_fields_by_id( array $fields, string $field_id ): int {
		return count(
			array_filter(
				$fields,
				static function ( array $field ) use ( $field_id ): bool {
					return ( $field['id'] ?? '' ) === $field_id;
				}
			)
		);
	}

	/**
	 * Find a formatted admin field by its generated ID.
	 *
	 * @param array  $fields Admin field definitions.
	 * @param string $field_id Generated field ID.
	 * @return array
	 */
	private function find_field_by_id( array $fields, string $field_id ): array {
		foreach ( $fields as $field ) {
			if ( ( $field['id'] ?? '' ) === $field_id ) {
				return $field;
			}
		}

		$this->fail( sprintf( 'Expected to find admin field with ID %s.', $field_id ) );
		return array();
	}
}
