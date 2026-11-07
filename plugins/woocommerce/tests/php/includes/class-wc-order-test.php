<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\PaymentMode;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for the WC_Order class.
 */
class WC_Order_Test extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start, so the sync test can run with posts authoritative.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
	}

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
	 * @testdox Should clear the payment mode when the order switches to another gateway, and keep it otherwise.
	 */
	public function test_set_payment_method_clears_payment_mode_from_another_gateway(): void {
		$order = wc_create_order();
		$order->set_payment_method( WC_Gateway_BACS::ID );
		$order->set_payment_mode( PaymentMode::TEST );
		$order->save();

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( PaymentMode::TEST, $order->get_payment_mode(), 'Loading the order should keep its payment mode' );

		$order->set_payment_method( WC_Gateway_BACS::ID );
		$this->assertSame( PaymentMode::TEST, $order->get_payment_mode(), 'Setting the same gateway should keep the payment mode' );

		$order->set_payment_method( WC_Gateway_COD::ID );
		$order->save();

		$this->assertSame( '', wc_get_order( $order->get_id() )->get_payment_mode(), 'Switching gateway should clear the payment mode' );
	}

	/**
	 * @testdox Should keep a payment mode the gateway set on the loaded order when it then switches the gateway ID, even after saving the mode first.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $save_before_switch Whether the gateway saves the order after recording the mode and before switching its gateway ID.
	 */
	public function test_set_payment_method_keeps_payment_mode_set_on_the_loaded_order( bool $save_before_switch ): void {
		$order = wc_create_order();
		$order->set_payment_method( WC_Gateway_BACS::ID );
		$order->save();

		$order = wc_get_order( $order->get_id() );
		$order->set_payment_mode( PaymentMode::TEST );
		if ( $save_before_switch ) {
			$order->save();
		}
		$order->set_payment_method( WC_Gateway_COD::ID );
		$order->save();

		$this->assertSame( PaymentMode::TEST, wc_get_order( $order->get_id() )->get_payment_mode(), 'A mode set before the gateway switch should be kept' );

		$order->set_payment_method( WC_Gateway_BACS::ID );
		$order->save();

		$this->assertSame( '', wc_get_order( $order->get_id() )->get_payment_mode(), 'A second gateway switch should clear the mode' );
	}

	/**
	 * @testdox Should sync a gateway switch to the backup copy of the order, keeping the mode only when the gateway recorded it before the switch.
	 * @testWith [true, "test"]
	 *           [false, ""]
	 *
	 * @param bool   $set_before_switch Whether the gateway records the mode on the loaded order before switching its gateway ID.
	 * @param string $expected_mode     The payment mode expected in the backup copy after the switch.
	 */
	public function test_payment_mode_gateway_switch_reaches_synced_copy( bool $set_before_switch, string $expected_mode ): void {
		$sync_option_filter = 'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION;
		add_filter( $sync_option_filter, array( $this, 'return_yes' ) );

		try {
			$order = wc_create_order();
			$order->set_payment_method( WC_Gateway_BACS::ID );
			$order->set_payment_mode( PaymentMode::TEST );
			$order->save();
			$this->assertSame( PaymentMode::TEST, $this->get_synced_payment_mode( $order->get_id() ), 'The backup copy should receive the payment mode' );

			$order = wc_get_order( $order->get_id() );
			if ( $set_before_switch ) {
				$order->set_payment_mode( PaymentMode::TEST );
			}
			$order->set_payment_method( WC_Gateway_COD::ID );
			$order->save();

			$this->assertSame( $expected_mode, $this->get_synced_payment_mode( $order->get_id() ), 'The backup copy should match the authoritative payment mode' );
		} finally {
			remove_filter( $sync_option_filter, array( $this, 'return_yes' ) );
		}
	}

	/**
	 * Read the payment mode from the backup copy of an order: the posts copy when HPOS is authoritative, the HPOS copy otherwise.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private function get_synced_payment_mode( int $order_id ): string {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return (string) get_post_meta( $order_id, '_wc_payment_mode', true );
		}

		$meta_table = OrdersTableDataStore::get_meta_table_name();
		return (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $meta_table WHERE order_id = %d AND meta_key = '_wc_payment_mode'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is provided by the data store.
				$order_id
			)
		);
	}

	/**
	 * Filter callback that returns 'yes'.
	 *
	 * @return string
	 */
	public function return_yes(): string {
		return 'yes';
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
