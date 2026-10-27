<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\PaymentMode;

/**
 * Tests for the WC_Order class.
 */
class WC_Order_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should keep the payment mode a gateway sets after the order is saved and read again.
	 * @testWith ["test"]
	 *           ["live"]
	 *
	 * @param string $mode Payment mode.
	 */
	public function test_set_payment_mode_persists( string $mode ): void {
		$order = wc_create_order();

		$order->set_payment_mode( $mode );
		$order->save();

		$this->assertSame( $mode, wc_get_order( $order->get_id() )->get_payment_mode(), 'The saved payment mode should be read back' );
	}

	/**
	 * @testdox Should return an empty payment mode when the gateway didn't set one.
	 */
	public function test_get_payment_mode_is_empty_by_default(): void {
		$order = wc_create_order();

		$this->assertSame( '', wc_get_order( $order->get_id() )->get_payment_mode(), 'WooCommerce should not set a payment mode' );
	}

	/**
	 * @testdox Should clear the payment mode when set to an empty string.
	 */
	public function test_set_payment_mode_clears_with_empty_string(): void {
		$order = wc_create_order();
		$order->set_payment_mode( PaymentMode::TEST );
		$order->save();

		$order->set_payment_mode( '' );
		$order->save();

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( '', $order->get_payment_mode(), 'The payment mode should be cleared' );
		$this->assertFalse( $order->meta_exists( '_wc_payment_mode' ), 'The payment mode meta should be deleted' );
	}

	/**
	 * @testdox Should reject an unknown payment mode and keep the existing one.
	 */
	public function test_set_payment_mode_rejects_unknown_value(): void {
		$order = wc_create_order();
		$order->set_payment_mode( PaymentMode::LIVE );

		try {
			$order->set_payment_mode( 'sandbox' );
			$this->fail( 'An unknown payment mode should throw' );
		} catch ( WC_Data_Exception $e ) {
			$this->assertSame( 'order_invalid_payment_mode', $e->getErrorCode(), 'The error code should name the payment mode' );
		}

		$this->assertSame( PaymentMode::LIVE, $order->get_payment_mode(), 'The existing payment mode should be kept' );
	}

	/**
	 * @testdox Should return an empty payment mode when the stored value is not a known mode.
	 */
	public function test_get_payment_mode_ignores_unknown_stored_value(): void {
		$order = wc_create_order();
		$order->update_meta_data( '_wc_payment_mode', 'sandbox' );
		$order->save();

		$this->assertSame( '', wc_get_order( $order->get_id() )->get_payment_mode(), 'An unknown stored value should not be returned' );
	}
}
