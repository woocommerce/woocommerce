<?php

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

/**
 * Tests relating to the WC_Privacy class.
 */
class WC_Privacy_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox When user accounts are deleted, their content should be preserved.
	 *
	 * @return void
	 */
	public function test_delete_inactive_accounts(): void {
		$customer = WC_Helper_Customer::create_customer();
		$post_id  = $this->factory->post->create( array( 'post_author' => $customer->get_id() ) );
		update_user_meta( $customer->get_id(), 'wc_last_active', time() - YEAR_IN_SECONDS );

		update_option(
			'woocommerce_delete_inactive_accounts',
			array(
				'number' => 1,
				'unit'   => 'months',
			)
		);

		$this->assertEquals( $customer->get_id(), get_post_field( 'post_author', $post_id ), 'The test post was successfully assigned to the customer.' );
		$this->assertEquals( 1, WC_Privacy::delete_inactive_accounts(), 'Dormant customer accounts are removed in accordance with configured privacy settings.' );
		$this->assertFalse( get_user_by( 'id', $customer->get_id() ), 'The deleted user account no longer exists.' );
		$this->assertEquals( '0', get_post_field( 'post_author', $post_id ), 'Posts belonging to the removed user still exist, but do not have an author.' );
	}

	/**
	 * @testdox Erasing the personal data of an order blanks the stored coupon usage identities.
	 *
	 * @return void
	 */
	public function test_remove_order_personal_data_blanks_coupon_usage_identities(): void {
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'guest@example.com' );
		$order->update_meta_data( '_coupon_usage_identities', array( 123 => 'guest@example.com' ) );
		$order->save();

		WC_Privacy_Erasers::remove_order_personal_data( $order );

		$identities = wc_get_order( $order->get_id() )->get_meta( '_coupon_usage_identities' );
		$this->assertSame( array( 123 => '' ), $identities, 'The entries are kept with blank values.' );
		$this->assertStringNotContainsString( 'guest@example.com', wp_json_encode( $identities ), 'The email is gone.' );
	}

	/**
	 * @testdox Cancelling an erased order lowers the coupon count without removing another order's row.
	 *
	 * @return void
	 */
	public function test_cancel_after_erasure_keeps_other_orders_coupon_usage(): void {
		WC_Helper_Coupon::create_coupon( 'erased_release' );

		$order_a = WC_Helper_Order::create_order( 0 );
		$order_a->set_billing_email( 'guest@example.com' );
		$order_a->set_status( OrderStatus::PENDING );
		$order_a->save();
		$order_a->apply_coupon( 'erased_release' );

		$order_b = WC_Helper_Order::create_order( 0 );
		$order_b->set_billing_email( 'other@example.com' );
		$order_b->set_status( OrderStatus::PENDING );
		$order_b->save();
		$order_b->apply_coupon( 'erased_release' );

		$this->assertEquals( 2, ( new WC_Coupon( 'erased_release' ) )->get_usage_count(), 'Both orders are counted.' );

		$order_a = wc_get_order( $order_a->get_id() );
		WC_Privacy_Erasers::remove_order_personal_data( $order_a );

		$order_a = wc_get_order( $order_a->get_id() );
		$order_a->set_billing_email( 'other@example.com' );
		$order_a->save();
		$order_a->update_status( OrderStatus::CANCELLED );

		$coupon = new WC_Coupon( 'erased_release' );
		$this->assertEquals( 1, $coupon->get_usage_count(), 'The count drops by one.' );
		$this->assertContains( 'other@example.com', $coupon->get_used_by(), 'The other order keeps its row.' );
	}

	/**
	 * @testdox The order eraser erases every order of a customer with more than one page of orders.
	 *
	 * @return void
	 */
	public function test_order_data_eraser_erases_all_pages_of_orders(): void {
		update_option( 'woocommerce_erasure_request_removes_order_data', 'yes' );
		$order_ids = $this->create_orders_for_email( 'guest@example.com', 25 );

		$messages = $this->run_order_data_eraser( 'guest@example.com' );

		$this->assertCount( 25, $messages, 'Each order is processed once.' );
		foreach ( $order_ids as $order_id ) {
			$this->assertSame( 'yes', wc_get_order( $order_id )->get_meta( '_anonymized' ), "Order $order_id is anonymized." );
		}
	}

	/**
	 * @testdox The order eraser erases the remaining orders when some orders are retained.
	 *
	 * @return void
	 */
	public function test_order_data_eraser_skips_retained_orders_once(): void {
		update_option( 'woocommerce_erasure_request_removes_order_data', 'yes' );
		$order_ids    = $this->create_orders_for_email( 'guest@example.com', 25 );
		$retained_ids = array_slice( $order_ids, 0, 15 );
		add_filter(
			'woocommerce_privacy_erase_order_personal_data',
			function ( $erase, $order ) use ( $retained_ids ) {
				return in_array( $order->get_id(), $retained_ids, true ) ? false : $erase;
			},
			10,
			2
		);

		$messages = $this->run_order_data_eraser( 'guest@example.com' );

		$this->assertCount( 25, $messages, 'Each order is processed once.' );
		foreach ( $order_ids as $order_id ) {
			$expected = in_array( $order_id, $retained_ids, true ) ? '' : 'yes';
			$this->assertSame( $expected, wc_get_order( $order_id )->get_meta( '_anonymized' ), "Order $order_id has the expected anonymized state." );
		}
	}

	/**
	 * @testdox The order eraser erases orders whose anonymized marker is set to a value other than yes.
	 *
	 * @return void
	 */
	public function test_order_data_eraser_erases_orders_not_marked_anonymized(): void {
		update_option( 'woocommerce_erasure_request_removes_order_data', 'yes' );
		$order_ids = $this->create_orders_for_email( 'guest@example.com', 2 );
		$order     = wc_get_order( $order_ids[0] );
		$order->update_meta_data( '_anonymized', 'no' );
		$order->save();

		$messages = $this->run_order_data_eraser( 'guest@example.com' );

		$this->assertCount( 2, $messages, 'Each order is processed once.' );
		foreach ( $order_ids as $order_id ) {
			$this->assertSame( 'yes', wc_get_order( $order_id )->get_meta( '_anonymized' ), "Order $order_id is anonymized." );
		}
	}

	/**
	 * @testdox The order eraser erases HPOS orders that have no metadata rows.
	 *
	 * @return void
	 */
	public function test_order_data_eraser_erases_orders_without_metadata(): void {
		global $wpdb;
		update_option( 'woocommerce_erasure_request_removes_order_data', 'yes' );
		$order_ids = $this->create_orders_for_email( 'guest@example.com', 2 );
		$wpdb->delete( OrdersTableDataStore::get_meta_table_name(), array( 'order_id' => $order_ids[0] ) );
		wp_cache_flush();

		$messages = $this->run_order_data_eraser( 'guest@example.com' );

		$this->assertCount( 2, $messages, 'Each order is processed once.' );
		foreach ( $order_ids as $order_id ) {
			$this->assertSame( 'yes', wc_get_order( $order_id )->get_meta( '_anonymized' ), "Order $order_id is anonymized." );
		}
	}

	/**
	 * @testdox The order eraser erases every order once when orders share the same creation date.
	 *
	 * @return void
	 */
	public function test_order_data_eraser_handles_orders_with_identical_dates(): void {
		update_option( 'woocommerce_erasure_request_removes_order_data', 'yes' );
		$order_ids    = $this->create_orders_for_email( 'guest@example.com', 25, 0 );
		$retained_ids = array_slice( $order_ids, 5, 10 );
		add_filter(
			'woocommerce_privacy_erase_order_personal_data',
			function ( $erase, $order ) use ( $retained_ids ) {
				return in_array( $order->get_id(), $retained_ids, true ) ? false : $erase;
			},
			10,
			2
		);

		$messages = $this->run_order_data_eraser( 'guest@example.com' );

		$this->assertCount( 25, $messages, 'Each order is processed once.' );
		foreach ( $order_ids as $order_id ) {
			$expected = in_array( $order_id, $retained_ids, true ) ? '' : 'yes';
			$this->assertSame( $expected, wc_get_order( $order_id )->get_meta( '_anonymized' ), "Order $order_id has the expected anonymized state." );
		}
	}

	/**
	 * @testdox The order eraser skips orders that are already anonymized but still match the email.
	 *
	 * @return void
	 */
	public function test_order_data_eraser_skips_anonymized_orders_matching_email(): void {
		update_option( 'woocommerce_erasure_request_removes_order_data', 'yes' );
		$order_ids = $this->create_orders_for_email( 'guest@example.com', 2 );
		$order     = wc_get_order( $order_ids[0] );
		$order->update_meta_data( '_anonymized', 'yes' );
		$order->save();

		$messages = $this->run_order_data_eraser( 'guest@example.com' );

		$this->assertCount( 1, $messages, 'Only the order that is not anonymized is processed.' );
		$this->assertSame( 'guest@example.com', wc_get_order( $order_ids[0] )->get_billing_email(), 'The anonymized order is not touched again.' );
		$this->assertSame( 'yes', wc_get_order( $order_ids[1] )->get_meta( '_anonymized' ), 'The other order is anonymized.' );
	}

	/**
	 * Create orders for a billing email, each created $step seconds before the previous one.
	 *
	 * @param string $email Billing email.
	 * @param int    $count Number of orders.
	 * @param int    $step  Seconds between the creation dates of consecutive orders.
	 * @return int[] Order IDs.
	 */
	private function create_orders_for_email( string $email, int $count, int $step = HOUR_IN_SECONDS ): array {
		$order_ids = array();
		$now       = time();
		for ( $i = 0; $i < $count; $i++ ) {
			$order = WC_Helper_Order::create_order( 0 );
			$order->set_billing_email( $email );
			$order->set_date_created( $now - $i * $step );
			$order->save();
			$order_ids[] = $order->get_id();
		}
		return $order_ids;
	}

	/**
	 * Run the order eraser page by page, like the WordPress erasure request does.
	 *
	 * @param string $email Email address.
	 * @return string[] Messages from all pages.
	 */
	private function run_order_data_eraser( string $email ): array {
		$messages = array();
		$page     = 1;
		do {
			$response = WC_Privacy_Erasers::order_data_eraser( $email, $page++ );
			$messages = array_merge( $messages, $response['messages'] );
		} while ( ! $response['done'] && $page < 20 );
		return $messages;
	}
}
