<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use WC_Order;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the OrderAttribution lifecycle hooks.
 *
 * Hooks are exercised directly (rather than through the full REST stack) to keep the
 * test focused on the validation + note-writing behavior. POS access is granted via
 * the preset meta + pos_staff role model (Capabilities::set_pos_preset).
 */
class OrderAttributionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderAttribution
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderAttribution();
	}

	/**
	 * Create a user with a specific assignable POS role via user meta.
	 *
	 * @param string $pos_preset   One of Capabilities::POS_PRESET_CASHIER / POS_PRESET_MANAGER.
	 * @param array  $user_args    Optional overrides for the user factory.
	 * @return int                 The created user ID.
	 */
	private function make_pos_user( string $pos_preset, array $user_args = array() ): int {
		$user_id = self::factory()->user->create( array_merge( array( 'role' => 'subscriber' ), $user_args ) );
		Capabilities::set_pos_preset( $user_id, $pos_preset );
		return $user_id;
	}

	/**
	 * @testdox Should pass orders without any POS meta through unchanged.
	 */
	public function test_pre_insert_passes_orders_without_pos_meta(): void {
		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );

		$result = $this->sut->handle_pre_insert( $order, $request, true );

		$this->assertSame( $order, $result );
	}

	/**
	 * @testdox Should pass an order with an unknown _pos_staff_user_id through pre-insert (operator attribution is best-effort, not a hard gate).
	 */
	public function test_pre_insert_passes_unknown_staff_user_through(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, 99999999 );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertSame( $order, $result );
	}

	/**
	 * @testdox Should pass an order whose operator lacks POS access through pre-insert (no hard rollback on the operator id).
	 */
	public function test_pre_insert_passes_staff_user_without_pos_access_through(): void {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order    = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $customer );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertSame( $order, $result );

		wp_delete_user( $customer );
	}

	/**
	 * @testdox Should return the draft order unchanged when only valid attribution is present.
	 */
	public function test_pre_insert_accepts_valid_attribution(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );
		$order   = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertSame( $order, $result );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should reject override meta on a plain order (process_sales is universal, no override needed).
	 */
	public function test_pre_insert_rejects_override_on_plain_order(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );
		$manager = $this->make_pos_user( Capabilities::POS_PRESET_MANAGER );

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID, $manager );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_override', $result->get_error_code() );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject a self-override on a refund (approver equals staff_user_id).
	 */
	public function test_pre_insert_rejects_self_override(): void {
		$manager = $this->make_pos_user( Capabilities::POS_PRESET_MANAGER );

		$parent_order = wc_create_order();
		$parent_order->save();
		$refund = wc_create_refund( array( 'order_id' => $parent_order->get_id() ) );
		$refund->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $manager );
		$refund->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID, $manager );

		$result = $this->sut->handle_pre_insert( $refund, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_self_override', $result->get_error_code() );

		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject refund override when the approver lacks issue_refunds.
	 */
	public function test_pre_insert_rejects_forbidden_refund_approver(): void {
		$cashier         = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );
		$another_cashier = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );

		$parent_order = wc_create_order();
		$parent_order->save();
		$refund = wc_create_refund( array( 'order_id' => $parent_order->get_id() ) );
		$refund->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$refund->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID, $another_cashier );

		$result = $this->sut->handle_pre_insert( $refund, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_override_forbidden', $result->get_error_code() );

		wp_delete_user( $cashier );
		wp_delete_user( $another_cashier );
	}

	/**
	 * @testdox Should accept a valid refund override (approver holds issue_refunds).
	 */
	public function test_pre_insert_accepts_valid_refund_override(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );
		$manager = $this->make_pos_user( Capabilities::POS_PRESET_MANAGER );

		$parent_order = wc_create_order();
		$parent_order->save();
		$refund = wc_create_refund( array( 'order_id' => $parent_order->get_id() ) );
		$refund->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$refund->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID, $manager );

		$result = $this->sut->handle_pre_insert( $refund, new WP_REST_Request(), true );

		$this->assertSame( $refund, $result );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should add a simple attribution note when no override is present.
	 */
	public function test_post_insert_writes_attribution_note_without_override(): void {
		$cashier = $this->make_pos_user(
			Capabilities::POS_PRESET_CASHIER,
			array(
				'display_name' => 'Mike Cashier',
				'user_login'   => 'mike',
			)
		);

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->save();

		$this->sut->handle_post_insert( $order, new WP_REST_Request(), true );

		$this->assert_order_note_contains( $order, 'POS: created by Mike Cashier (mike).' );
		$this->assert_order_note_not_contains( $order, 'POS override:' );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should add a single combined override note when refund override is present (no separate attribution note).
	 */
	public function test_post_insert_writes_combined_refund_override_note(): void {
		$cashier = $this->make_pos_user(
			Capabilities::POS_PRESET_CASHIER,
			array(
				'display_name' => 'Mike Cashier',
				'user_login'   => 'mike',
			)
		);
		$manager = $this->make_pos_user(
			Capabilities::POS_PRESET_MANAGER,
			array(
				'display_name' => 'Sarah Manager',
				'user_login'   => 'sarah',
			)
		);

		$parent_order = wc_create_order();
		$parent_order->save();
		$refund = wc_create_refund( array( 'order_id' => $parent_order->get_id() ) );
		$refund->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$refund->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID, $manager );
		$refund->save();

		$this->sut->handle_post_insert( $refund, new WP_REST_Request(), true );

		$this->assert_order_note_contains(
			$parent_order,
			'POS override: refund by Mike Cashier (mike), approved by Sarah Manager (sarah).'
		);
		$this->assert_order_note_not_contains( $parent_order, 'POS: refunded by' );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should not write an order note when no attribution meta is present.
	 */
	public function test_post_insert_noop_without_attribution(): void {
		$order = wc_create_order();
		$order->save();

		$notes_before = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$this->sut->handle_post_insert( $order, new WP_REST_Request(), true );

		$notes_after = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$this->assertCount( count( $notes_before ), $notes_after, 'No new notes should be written.' );
	}

	/**
	 * @testdox Should write the same attribution note when attribute_order() is called directly (the path-agnostic entry a Store API hook would use).
	 */
	public function test_attribute_order_writes_note_directly(): void {
		$cashier = $this->make_pos_user(
			Capabilities::POS_PRESET_CASHIER,
			array(
				'display_name' => 'Mike Cashier',
				'user_login'   => 'mike',
			)
		);

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->save();

		$this->sut->attribute_order( $order, true );

		$this->assert_order_note_contains( $order, 'POS: created by Mike Cashier (mike).' );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should skip the attribution note when the operator id references a missing user.
	 */
	public function test_attribute_order_skips_note_for_unknown_staff_user(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, 99999999 );
		$order->save();

		$notes_before = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->sut->attribute_order( $order, true );
		$notes_after = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$this->assertCount( count( $notes_before ), $notes_after, 'No note should be written for an unknown operator.' );
	}

	/**
	 * @testdox Should skip the attribution note when the operator lacks POS access.
	 */
	public function test_attribute_order_skips_note_for_staff_user_without_pos_access(): void {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $customer );
		$order->save();

		$notes_before = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->sut->attribute_order( $order, true );
		$notes_after = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$this->assertCount( count( $notes_before ), $notes_after, 'No note should be written when the operator lacks POS access.' );

		wp_delete_user( $customer );
	}

	/**
	 * Assert that the order has at least one note containing the given substring.
	 *
	 * @param WC_Order $order    The order to check.
	 * @param string   $expected Substring to look for in any note's content.
	 */
	private function assert_order_note_contains( WC_Order $order, string $expected ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'order_by' => 'date_created',
				'order'    => 'DESC',
				'limit'    => 10,
			)
		);

		foreach ( $notes as $note ) {
			if ( false !== strpos( $note->content, $expected ) ) {
				$this->assertTrue( true );
				return;
			}
		}

		$this->fail( sprintf( 'Expected an order note containing "%s" — none found.', $expected ) );
	}

	/**
	 * Assert that the order has no note containing the given substring.
	 *
	 * @param WC_Order $order    The order to check.
	 * @param string   $expected Substring that should NOT appear in any note.
	 */
	private function assert_order_note_not_contains( WC_Order $order, string $expected ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'order_by' => 'date_created',
				'order'    => 'DESC',
				'limit'    => 10,
			)
		);

		foreach ( $notes as $note ) {
			if ( false !== strpos( $note->content, $expected ) ) {
				$this->fail( sprintf( 'Did not expect any order note containing "%s" but one was found.', $expected ) );
			}
		}

		$this->assertTrue( true );
	}
}
