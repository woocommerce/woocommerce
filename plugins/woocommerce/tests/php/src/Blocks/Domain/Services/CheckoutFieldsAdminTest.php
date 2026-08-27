<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsAdmin;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Tests for \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsAdmin.
 */
class CheckoutFieldsAdminTest extends \WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var CheckoutFieldsAdmin
	 */
	private $sut;

	/**
	 * Helper controller.
	 *
	 * @var CheckoutFields
	 */
	private $controller;

	/**
	 * Field IDs registered during a test, to be deregistered in tearDown.
	 *
	 * @var string[]
	 */
	private $registered_fields = array();

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut        = Package::container()->get( CheckoutFieldsAdmin::class );
		$this->controller = Package::container()->get( CheckoutFields::class );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		foreach ( $this->registered_fields as $field_id ) {
			__internal_woocommerce_blocks_deregister_checkout_field( $field_id );
		}
		$this->registered_fields = array();

		parent::tearDown();
	}

	/**
	 * Register a checkout field and track it for cleanup in tearDown.
	 *
	 * @param array $args Field registration arguments.
	 */
	private function register_checkout_field( array $args ): void {
		woocommerce_register_additional_checkout_field( $args );
		$this->registered_fields[] = $args['id'];
	}

	/**
	 * Creates a Store API order carrying a value for the given field.
	 *
	 * @param string $field_id The field to set.
	 * @param string $value    The value to persist.
	 * @return \WC_Order
	 */
	private function create_order_with_field( string $field_id, string $value ) {
		$order = \WC_Helper_Order::create_order();
		$order->set_created_via( 'store-api' );
		$this->controller->persist_field_for_order( $field_id, $value, $order, 'other', false );
		$order->save();

		return $order;
	}

	/**
	 * @testDox Absolute date constraints are passed to the order meta box as input attributes.
	 */
	public function test_absolute_date_constraints_are_passed_to_the_meta_box() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/delivery_date',
				'label'    => 'Delivery date',
				'location' => 'order',
				'type'     => 'date',
				'min'      => '2026-01-01',
				'max'      => '2026-12-31',
			)
		);

		$order  = $this->create_order_with_field( 'mynamespace/delivery_date', '2026-06-15' );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertArrayHasKey( 'mynamespace/delivery_date', $fields );
		$this->assertSame(
			array(
				'min' => '2026-01-01',
				'max' => '2026-12-31',
			),
			$fields['mynamespace/delivery_date']['custom_attributes']
		);
	}

	/**
	 * @testDox Duration constraints are resolved against the current date when the meta box is rendered.
	 */
	public function test_duration_constraints_are_resolved_for_the_meta_box() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/delivery_date',
				'label'    => 'Delivery date',
				'location' => 'order',
				'type'     => 'date',
				'min'      => 'P1D',
				'max'      => 'P30D',
			)
		);

		$today = new \DateTimeImmutable( 'today', wp_timezone() );

		$order  = $this->create_order_with_field( 'mynamespace/delivery_date', $today->format( 'Y-m-d' ) );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertSame(
			array(
				'min' => $today->modify( '+1 day' )->format( 'Y-m-d' ),
				'max' => $today->modify( '+30 days' )->format( 'Y-m-d' ),
			),
			$fields['mynamespace/delivery_date']['custom_attributes']
		);
	}

	/**
	 * @testDox Only the constraints that were registered are passed to the meta box.
	 */
	public function test_partial_constraints_are_passed_to_the_meta_box() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/delivery_date',
				'label'    => 'Delivery date',
				'location' => 'order',
				'type'     => 'date',
				'min'      => '2026-01-01',
			)
		);

		$order  = $this->create_order_with_field( 'mynamespace/delivery_date', '2026-06-15' );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertSame( array( 'min' => '2026-01-01' ), $fields['mynamespace/delivery_date']['custom_attributes'] );
	}

	/**
	 * @testDox An unconstrained date field is passed to the meta box without min or max attributes.
	 */
	public function test_unconstrained_date_field_has_no_constraint_attributes() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/delivery_date',
				'label'    => 'Delivery date',
				'location' => 'order',
				'type'     => 'date',
			)
		);

		$order  = $this->create_order_with_field( 'mynamespace/delivery_date', '2026-06-15' );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertSame( array(), $fields['mynamespace/delivery_date']['custom_attributes'] );
	}

	/**
	 * @testDox Date constraints are also passed to the meta box for contact and address fields.
	 *
	 * @testWith ["contact", "admin_contact_fields", "other"]
	 *           ["address", "admin_address_fields", "billing"]
	 *
	 * @param string $location The field location.
	 * @param string $method   The CheckoutFieldsAdmin method that injects the location's fields.
	 * @param string $group    The group the value is persisted under.
	 */
	public function test_constraints_are_passed_for_all_locations( string $location, string $method, string $group ) {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/some_date',
				'label'    => 'Some date',
				'location' => $location,
				'type'     => 'date',
				'min'      => '2026-01-01',
			)
		);

		$order = \WC_Helper_Order::create_order();
		$order->set_created_via( 'store-api' );
		$this->controller->persist_field_for_order( 'mynamespace/some_date', '2026-06-15', $order, $group, false );
		$order->save();

		// admin_address_fields() splices its fields in after "state", so the array it is given needs that key.
		$fields = $this->sut->{$method}( array( 'state' => array() ), $order );
		$field  = $this->find_field_by_id( $fields, CheckoutFields::get_group_key( $group ) . 'mynamespace/some_date' );

		$this->assertNotNull( $field );
		$this->assertSame( array( 'min' => '2026-01-01' ), $field['custom_attributes'] );
	}

	/**
	 * Finds a meta box field by its id.
	 *
	 * admin_address_fields() splices its fields into the list with array_splice(), which drops their string
	 * keys, so address fields have to be located by the id they carry rather than by key.
	 *
	 * @param array  $fields The fields returned by CheckoutFieldsAdmin.
	 * @param string $id     The id to look for.
	 * @return array|null The field, or null when it is absent.
	 */
	private function find_field_by_id( array $fields, string $id ) {
		foreach ( $fields as $field ) {
			if ( is_array( $field ) && ( $field['id'] ?? null ) === $id ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * @testDox Select options are still mapped to the value => label shape the meta box expects.
	 */
	public function test_select_options_are_mapped_for_the_meta_box() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/gift_wrap',
				'label'    => 'Gift wrap',
				'location' => 'order',
				'type'     => 'select',
				'options'  => array(
					array(
						'value' => 'none',
						'label' => 'None',
					),
					array(
						'value' => 'paper',
						'label' => 'Paper',
					),
				),
			)
		);

		$order  = $this->create_order_with_field( 'mynamespace/gift_wrap', 'paper' );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertSame(
			array(
				'none'  => 'None',
				'paper' => 'Paper',
			),
			$fields['mynamespace/gift_wrap']['options']
		);
	}

	/**
	 * @testDox Checkbox fields still get the checked and unchecked values the meta box submits.
	 */
	public function test_checkbox_values_are_set_for_the_meta_box() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/is_gift',
				'label'    => 'Is a gift',
				'location' => 'order',
				'type'     => 'checkbox',
			)
		);

		$order  = $this->create_order_with_field( 'mynamespace/is_gift', '1' );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertSame( '1', $fields['mynamespace/is_gift']['checked_value'] );
		$this->assertSame( '0', $fields['mynamespace/is_gift']['unchecked_value'] );
	}

	/**
	 * @testDox Text fields are passed to the meta box without type-specific arguments.
	 */
	public function test_text_field_has_no_type_specific_arguments() {
		$this->register_checkout_field(
			array(
				'id'       => 'mynamespace/note',
				'label'    => 'Note',
				'location' => 'order',
				'type'     => 'text',
			)
		);

		$order  = $this->create_order_with_field( 'mynamespace/note', 'Leave at the door' );
		$fields = $this->sut->admin_order_fields( array(), $order );

		$this->assertSame( 'Leave at the door', $fields['mynamespace/note']['value'] );
		$this->assertArrayNotHasKey( 'custom_attributes', $fields['mynamespace/note'] );
		$this->assertArrayNotHasKey( 'options', $fields['mynamespace/note'] );
	}
}
