<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\OrderDefaultsPolicy;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Unit_Test_Case;

/**
 * Tests for the POS order-defaults policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\OrderDefaultsPolicy
 */
class OrderDefaultsPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderDefaultsPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderDefaultsPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox no_shipping_for_pos passes the original value through outside POS context.
	 */
	public function test_no_shipping_for_pos_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->no_shipping_for_pos( true ) );
		$this->assertFalse( $this->sut->no_shipping_for_pos( false ) );
	}

	/**
	 * @testdox no_shipping_for_pos returns false inside POS context regardless of input.
	 */
	public function test_no_shipping_for_pos_returns_false_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->no_shipping_for_pos( true ) );
		$this->assertFalse( $this->sut->no_shipping_for_pos( false ) );
	}

	/**
	 * @testdox reset_order_defaults_for_pos leaves the order untouched outside POS context.
	 */
	public function test_reset_order_defaults_does_nothing_outside_pos_context(): void {
		Context::set_test_override( false );

		$order = $this->build_seeded_order();

		$this->sut->reset_order_defaults_for_pos( $order );

		$this->assertSame( 42, $order->get_customer_id() );
		$this->assertSame( 'woocommerce_payments', $order->get_payment_method() );
		$this->assertSame( 'WooPayments', $order->get_payment_method_title() );
		$this->assertSame( 'Cashier', $order->get_billing_first_name() );
		$this->assertSame( 'cashier@example.com', $order->get_billing_email() );
		$this->assertSame( 'Shipper', $order->get_shipping_first_name() );
		$this->assertCount(
			1,
			$order->get_items( 'shipping' ),
			'Shipping line item should still be present outside POS context.'
		);
	}

	/**
	 * @testdox reset_order_defaults_for_pos zeroes out customer/payment/addresses and drops shipping inside POS.
	 */
	public function test_reset_order_defaults_wipes_fields_inside_pos_context(): void {
		Context::set_test_override( true );

		$order = $this->build_seeded_order();

		$this->sut->reset_order_defaults_for_pos( $order );

		$this->assertSame( 0, $order->get_customer_id(), 'POS orders must not be attributed to the cashier.' );
		$this->assertSame( '', $order->get_payment_method(), 'POS orders must not be stamped with a default gateway.' );
		$this->assertSame( '', $order->get_payment_method_title() );

		foreach ( $this->address_fields() as $field ) {
			$this->assertSame( '', $order->{"get_billing_{$field}"}(), "billing_{$field} should be empty for POS." );
			$this->assertSame( '', $order->{"get_shipping_{$field}"}(), "shipping_{$field} should be empty for POS." );
		}
		$this->assertSame( '', $order->get_billing_email(), 'billing_email should be empty for POS.' );

		$this->assertCount( 0, $order->get_items( 'shipping' ), 'In-person POS sales must not carry a shipping line.' );
		$this->assertSame( 0.0, (float) $order->get_shipping_total() );

		// The wipe should have been persisted, not just held in memory.
		$reloaded = wc_get_order( $order->get_id() );
		$this->assertSame( 0, $reloaded->get_customer_id() );
		$this->assertSame( '', $reloaded->get_payment_method() );
		$this->assertSame( '', $reloaded->get_billing_first_name() );
	}

	/**
	 * Build an order pre-populated with the exact set of fields the Store API
	 * stamps onto draft orders out of the box — so the test asserts on the
	 * delta between "as-built by Store API" and "as-required for POS".
	 *
	 * @return WC_Order
	 */
	private function build_seeded_order(): WC_Order {
		$order = new WC_Order();
		$order->set_customer_id( 42 );
		$order->set_payment_method( 'woocommerce_payments' );
		$order->set_payment_method_title( 'WooPayments' );

		$order->set_props(
			array(
				'billing_first_name'  => 'Cashier',
				'billing_last_name'   => 'McAdmin',
				'billing_company'     => 'StoreCo',
				'billing_address_1'   => '1 Cashier Lane',
				'billing_address_2'   => 'Unit 7',
				'billing_city'        => 'Townsville',
				'billing_state'       => 'CA',
				'billing_postcode'    => '90210',
				'billing_country'     => 'US',
				'billing_email'       => 'cashier@example.com',
				'billing_phone'       => '555-0100',
				'shipping_first_name' => 'Shipper',
				'shipping_last_name'  => 'McAdmin',
				'shipping_company'    => 'StoreCo',
				'shipping_address_1'  => '1 Cashier Lane',
				'shipping_address_2'  => 'Unit 7',
				'shipping_city'       => 'Townsville',
				'shipping_state'      => 'CA',
				'shipping_postcode'   => '90210',
				'shipping_country'    => 'US',
				'shipping_phone'      => '555-0100',
			)
		);

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat rate' );
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_total( '5.00' );
		$order->add_item( $shipping_item );
		$order->set_shipping_total( '5.00' );

		$order->save();
		return $order;
	}

	/**
	 * Address fields the policy is responsible for blanking out (excluding
	 * billing_email which is asserted separately).
	 *
	 * @return string[]
	 */
	private function address_fields(): array {
		return array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'phone',
		);
	}
}
