<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Orders\PointOfSaleEmailHandler;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the PointOfSaleEmailHandler class.
 *
 * @covers \Automattic\WooCommerce\Internal\Orders\PointOfSaleEmailHandler
 */
class PointOfSaleEmailHandlerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PointOfSaleEmailHandler
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new PointOfSaleEmailHandler();
	}

	/**
	 * @testdox maybe_suppress_email returns false for POS-created orders.
	 */
	public function test_suppresses_email_for_pos_created_order(): void {
		$order = new WC_Order();
		$order->set_created_via( 'pos-rest-api' );
		$order->save();

		$this->assertFalse( $this->sut->maybe_suppress_email( true, $order ) );
	}

	/**
	 * @testdox maybe_suppress_email returns false for POS card reader payment.
	 */
	public function test_suppresses_email_for_pos_card_reader_payment(): void {
		$order = new WC_Order();
		$order->set_created_via( 'bookings' );
		$order->update_meta_data( '_wcpay_ipp_channel', 'mobile_pos' );
		$order->save();

		$this->assertFalse( $this->sut->maybe_suppress_email( true, $order ) );
	}

	/**
	 * @testdox maybe_suppress_email returns false for cash payment at POS.
	 */
	public function test_suppresses_email_for_cash_payment(): void {
		$order = new WC_Order();
		$order->set_created_via( 'admin' );
		$order->update_meta_data( '_cash_change_amount', '5.00' );
		$order->save();

		$this->assertFalse( $this->sut->maybe_suppress_email( true, $order ) );
	}

	/**
	 * @testdox maybe_suppress_email returns original value for regular web orders.
	 */
	public function test_returns_original_value_for_regular_order(): void {
		$order = new WC_Order();
		$order->set_created_via( 'checkout' );
		$order->save();

		$this->assertTrue( $this->sut->maybe_suppress_email( true, $order ) );
	}

	/**
	 * @testdox maybe_suppress_email keeps already-disabled emails disabled for regular orders.
	 */
	public function test_already_disabled_email_stays_disabled(): void {
		$order = new WC_Order();
		$order->set_created_via( 'checkout' );
		$order->save();

		$this->assertFalse( $this->sut->maybe_suppress_email( false, $order ) );
	}

	/**
	 * @testdox maybe_suppress_email returns original value when order is null.
	 */
	public function test_returns_original_value_for_null_order(): void {
		$this->assertTrue( $this->sut->maybe_suppress_email( true, null ) );
	}

	/**
	 * @testdox maybe_suppress_email returns original value when object is not an order.
	 */
	public function test_returns_original_value_for_non_order_object(): void {
		$this->assertTrue( $this->sut->maybe_suppress_email( true, new \stdClass() ) );
	}

	/**
	 * @testdox register adds filters only when POS feature is enabled.
	 */
	public function test_register_adds_filters_when_pos_enabled(): void {
		update_option( 'woocommerce_feature_point_of_sale_enabled', 'yes' );

		$handler = new PointOfSaleEmailHandler();
		$handler->register();

		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_customer_completed_order', array( $handler, 'maybe_suppress_email' ) )
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_customer_processing_order', array( $handler, 'maybe_suppress_email' ) )
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_customer_on_hold_order', array( $handler, 'maybe_suppress_email' ) )
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_new_order', array( $handler, 'maybe_suppress_email' ) )
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_customer_refunded_order', array( $handler, 'maybe_suppress_email' ) )
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_email_enabled_customer_partially_refunded_order', array( $handler, 'maybe_suppress_email' ) )
		);

		remove_all_filters( 'woocommerce_email_enabled_customer_completed_order' );
		remove_all_filters( 'woocommerce_email_enabled_customer_processing_order' );
		remove_all_filters( 'woocommerce_email_enabled_customer_on_hold_order' );
		remove_all_filters( 'woocommerce_email_enabled_customer_refunded_order' );
		remove_all_filters( 'woocommerce_email_enabled_customer_partially_refunded_order' );
		remove_all_filters( 'woocommerce_email_enabled_new_order' );
	}

	/**
	 * @testdox register does not add filters when POS feature is disabled.
	 */
	public function test_register_does_not_add_filters_when_pos_disabled(): void {
		remove_all_filters( 'woocommerce_email_enabled_customer_completed_order' );
		update_option( 'woocommerce_feature_point_of_sale_enabled', 'no' );

		$handler = new PointOfSaleEmailHandler();
		$handler->register();

		$this->assertFalse(
			has_filter( 'woocommerce_email_enabled_customer_completed_order', array( $handler, 'maybe_suppress_email' ) )
		);

		delete_option( 'woocommerce_feature_point_of_sale_enabled' );
	}
}
