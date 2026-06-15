<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use WC_Order_Refund;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderPaymentStore class.
 */
class OrderPaymentStoreTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderPaymentStore
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( OrderPaymentStore::class );
	}

	/**
	 * @testdox Payment meta keys preserve the WooPayments Bucket-E persisted surface.
	 */
	public function test_payment_meta_keys_preserve_woopayments_bucket_e_surface(): void {
		$keys = OrderPaymentStore::get_payment_meta_keys();

		$this->assertSame( $keys, array_values( array_unique( $keys ) ), 'Payment meta keys must not contain duplicates.' );

		foreach (
			array(
				'_intent_id',
				'_payment_method_id',
				'_charge_id',
				'_intention_status',
				'_charge_risk_level',
				'_stripe_customer_id',
				'_wcpay_fraud_meta_box_type',
				'_wcpay_fraud_outcome_status',
				'_wcpay_intent_currency',
				'_wcpay_refund_id',
				'_wcpay_refund_transaction_id',
				'_wcpay_refund_status',
				'_wcpay_transaction_fee',
				'_wcpay_mode',
				'_wcpay_payment_transaction_id',
				'_wcpay_multibanco_entity',
				'_wcpay_multibanco_reference',
				'_wcpay_multibanco_expiry',
				'_wcpay_multibanco_url',
				'_wcpay_payment_method_details',
				'_wcpay_ipp_channel',
				'_wcpay_net',
				'_stripe_mandate_id',
				'_wcpay_express_checkout_payment_method',
				'_wcpay_multi_currency_stripe_exchange_rate',
				'_wcpay_multi_currency_order_exchange_rate',
				'_wcpay_multi_currency_order_default_currency',
				'_wcpay_fraud_outcome_manual_entry',
				'is_woopay',
				'last4',
				'_card_brand',
			) as $key
		) {
			$this->assertContains( $key, $keys, "{$key} must remain part of the preserved WooPayments persisted surface." );
		}
	}

	/**
	 * @testdox read_payment_surface returns a stable HPOS-safe projection without unrelated meta.
	 */
	public function test_read_payment_surface_returns_stable_payment_projection(): void {
		$order = wc_create_order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_transaction_id( 'txn_123' );
		$order->set_total( '12.34' );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->update_meta_data( '_wcpay_multi_currency_order_exchange_rate', '0.71' );
		$order->update_meta_data( '_wcpay_multi_currency_order_default_currency', 'USD' );
		$order->update_meta_data( '_wcpay_multi_currency_stripe_exchange_rate', '0.724' );
		$order->update_meta_data(
			'_wcpay_fraud_outcome_manual_entry',
			array(
				'status' => 'approved',
			)
		);
		$order->update_meta_data( '_not_a_payment_key', 'ignore-me' );
		$order->save();

		$surface = $this->sut->read_payment_surface( $order );

		$this->assertSame( $order->get_id(), $surface['order_id'] );
		$this->assertSame( $order->get_status(), $surface['status'] );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $surface['payment_method'] );
		$this->assertSame( 'txn_123', $surface['transaction_id'] );
		$this->assertSame( 'USD', $surface['currency'] );
		$this->assertSame( '12.34', $surface['total'] );
		$this->assertSame( 'pi_123', $surface['meta']['_intent_id'] );
		$this->assertSame( 'ch_123', $surface['meta']['_charge_id'] );
		$this->assertSame( '0.71', $surface['meta']['_wcpay_multi_currency_order_exchange_rate'] );
		$this->assertSame( 'USD', $surface['meta']['_wcpay_multi_currency_order_default_currency'] );
		$this->assertSame( '0.724', $surface['meta']['_wcpay_multi_currency_stripe_exchange_rate'] );
		$this->assertSame( '{"status":"approved"}', $surface['meta']['_wcpay_fraud_outcome_manual_entry'] );
		$this->assertArrayNotHasKey( '_not_a_payment_key', $surface['meta'] );
		$this->assertSame( array(), $surface['refunds'] );
	}

	/**
	 * @testdox read_payment_surface includes refund payment meta in the stable projection.
	 */
	public function test_read_payment_surface_includes_refund_payment_meta(): void {
		$order = wc_create_order();
		$order->set_currency( 'USD' );
		$order->set_total( '12.34' );
		$order->save();

		$refund = wc_create_refund(
			array(
				'amount'   => '3.21',
				'reason'   => 'partial refund',
				'order_id' => $order->get_id(),
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->update_meta_data( '_wcpay_multi_currency_order_exchange_rate', '0.71' );
		$refund->update_meta_data( '_wcpay_multi_currency_order_default_currency', 'USD' );
		$refund->update_meta_data( '_wcpay_multi_currency_stripe_exchange_rate', '0.724' );
		$refund->update_meta_data( '_not_a_payment_key', 'ignore-me' );
		$refund->save();

		$surface = $this->sut->read_payment_surface( wc_get_order( $order->get_id() ) );

		$this->assertCount( 1, $surface['refunds'] );
		$this->assertSame( $refund->get_id(), $surface['refunds'][0]['refund_id'] );
		$this->assertSame( '3.21', $surface['refunds'][0]['amount'] );
		$this->assertSame( 'partial refund', $surface['refunds'][0]['reason'] );
		$this->assertSame( 're_123', $surface['refunds'][0]['meta']['_wcpay_refund_id'] );
		$this->assertSame( '0.71', $surface['refunds'][0]['meta']['_wcpay_multi_currency_order_exchange_rate'] );
		$this->assertSame( 'USD', $surface['refunds'][0]['meta']['_wcpay_multi_currency_order_default_currency'] );
		$this->assertSame( '0.724', $surface['refunds'][0]['meta']['_wcpay_multi_currency_stripe_exchange_rate'] );
		$this->assertArrayNotHasKey( '_not_a_payment_key', $surface['refunds'][0]['meta'] );
	}

	/**
	 * @testdox Order payment locks use the WooPayments-compatible transient shape.
	 */
	public function test_order_payment_locks_use_woopayments_compatible_transient_shape(): void {
		$order = wc_create_order();

		$this->assertFalse( $this->sut->is_order_payment_locked( $order, 'pi_123' ) );

		$this->sut->lock_order_payment( $order, 'pi_123' );

		$this->assertTrue( $this->sut->is_order_payment_locked( $order, 'pi_123' ) );
		$this->assertFalse( $this->sut->is_order_payment_locked( $order, 'pi_other' ) );

		$this->sut->lock_order_payment( $order );

		$this->assertTrue( $this->sut->is_order_payment_locked( $order, 'pi_other' ), 'The sentinel lock must block every payment reference.' );

		$this->sut->unlock_order_payment( $order );

		$this->assertFalse( $this->sut->is_order_payment_locked( $order, 'pi_123' ) );
	}

	/**
	 * @testdox Native money-operation claims should block any active order payment lock.
	 */
	public function test_claim_order_payment_lock_blocks_any_active_lock(): void {
		$order = wc_create_order();

		$this->assertTrue( $this->sut->claim_order_payment_lock( $order, 'native_charge_key' ) );
		$this->assertFalse( $this->sut->claim_order_payment_lock( $order, 'native_refund_key' ) );
		$this->assertTrue( $this->sut->is_order_payment_locked( $order, 'native_charge_key' ) );
		$this->assertFalse( $this->sut->is_order_payment_locked( $order, 'native_refund_key' ) );

		$this->sut->unlock_order_payment( $order );

		$this->sut->lock_order_payment( $order, 'pi_legacy' );
		$this->assertFalse( $this->sut->is_order_payment_locked( $order, 'native_charge_key' ) );
		$this->assertFalse( $this->sut->claim_order_payment_lock( $order, 'native_charge_key' ) );

		$this->sut->unlock_order_payment( $order );
		$this->assertTrue( $this->sut->claim_order_payment_lock( $order, 'native_charge_key' ) );
		$this->sut->unlock_order_payment( $order );
	}
}
