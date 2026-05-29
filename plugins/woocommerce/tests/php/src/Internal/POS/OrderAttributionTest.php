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
 * the user-meta model (Capabilities::set_pos_role) rather than dedicated WP roles.
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
	 * @param string $pos_role     One of Capabilities::POS_ROLE_CASHIER / POS_ROLE_MANAGER.
	 * @param array  $user_args    Optional overrides for the user factory.
	 * @return int                 The created user ID.
	 */
	private function make_pos_user( string $pos_role, array $user_args = array() ): int {
		$user_id = self::factory()->user->create( array_merge( array( 'role' => 'subscriber' ), $user_args ) );
		Capabilities::set_pos_role( $user_id, $pos_role );
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
	 * @testdox Should return a WP_Error when _pos_staff_user_id references a missing user.
	 */
	public function test_pre_insert_rejects_unknown_staff_user(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, 99999999 );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return a WP_Error when the staff user lacks POS access.
	 */
	public function test_pre_insert_rejects_staff_user_without_pos_access(): void {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order    = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $customer );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );

		wp_delete_user( $customer );
	}

	/**
	 * @testdox Should return the draft order unchanged when only valid attribution is present.
	 */
	public function test_pre_insert_accepts_valid_attribution(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_ROLE_CASHIER );
		$order   = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertSame( $order, $result );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should reject the override pair when only one half is present.
	 */
	public function test_pre_insert_rejects_partial_override_pair(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_ROLE_CASHIER );
		$manager = $this->make_pos_user( Capabilities::POS_ROLE_MANAGER );

		$order_missing_reason = wc_create_order();
		$order_missing_reason->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order_missing_reason->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_USER_ID, $manager );

		$result = $this->sut->handle_pre_insert( $order_missing_reason, new WP_REST_Request(), true );
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_override', $result->get_error_code() );

		$order_missing_user = wc_create_order();
		$order_missing_user->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order_missing_user->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_REASON, Capabilities::CAP_ISSUE_REFUNDS );

		$result = $this->sut->handle_pre_insert( $order_missing_user, new WP_REST_Request(), true );
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_override', $result->get_error_code() );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject a self-override (granted_by equals staff_user_id).
	 */
	public function test_pre_insert_rejects_self_override(): void {
		$manager = $this->make_pos_user( Capabilities::POS_ROLE_MANAGER );

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $manager );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_USER_ID, $manager );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_REASON, Capabilities::CAP_ISSUE_REFUNDS );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_self_override', $result->get_error_code() );

		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject override when reason is not an overridable POS capability.
	 */
	public function test_pre_insert_rejects_non_overridable_reason(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_ROLE_CASHIER );
		$manager = $this->make_pos_user( Capabilities::POS_ROLE_MANAGER );

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_USER_ID, $manager );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_REASON, 'manage_woocommerce' );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_override', $result->get_error_code() );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject override when the granter does not hold the named POS cap.
	 */
	public function test_pre_insert_rejects_forbidden_granter(): void {
		$cashier         = $this->make_pos_user( Capabilities::POS_ROLE_CASHIER );
		$another_cashier = $this->make_pos_user( Capabilities::POS_ROLE_CASHIER );

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_USER_ID, $another_cashier );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_REASON, Capabilities::CAP_ISSUE_REFUNDS );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_override_forbidden', $result->get_error_code() );

		wp_delete_user( $cashier );
		wp_delete_user( $another_cashier );
	}

	/**
	 * @testdox Should accept a valid override pair (manager has the elevated POS cap).
	 */
	public function test_pre_insert_accepts_valid_override(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_ROLE_CASHIER );
		$manager = $this->make_pos_user( Capabilities::POS_ROLE_MANAGER );

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_USER_ID, $manager );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_REASON, Capabilities::CAP_ISSUE_REFUNDS );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertSame( $order, $result );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should add a simple attribution note when no override is present.
	 */
	public function test_post_insert_writes_attribution_note_without_override(): void {
		$cashier = $this->make_pos_user(
			Capabilities::POS_ROLE_CASHIER,
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
	 * @testdox Should add a single combined override note when override is present (no separate attribution note).
	 */
	public function test_post_insert_writes_combined_override_note(): void {
		$cashier = $this->make_pos_user(
			Capabilities::POS_ROLE_CASHIER,
			array(
				'display_name' => 'Mike Cashier',
				'user_login'   => 'mike',
			)
		);
		$manager = $this->make_pos_user(
			Capabilities::POS_ROLE_MANAGER,
			array(
				'display_name' => 'Sarah Manager',
				'user_login'   => 'sarah',
			)
		);

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_STAFF_USER_ID, $cashier );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_USER_ID, $manager );
		$order->update_meta_data( OrderAttribution::META_KEY_OVERRIDE_REASON, Capabilities::CAP_ISSUE_REFUNDS );
		$order->save();

		$this->sut->handle_post_insert( $order, new WP_REST_Request(), true );

		$this->assert_order_note_contains(
			$order,
			'POS override: ' . Capabilities::CAP_ISSUE_REFUNDS . ' granted to Mike Cashier (mike), approved by Sarah Manager (sarah).'
		);
		$this->assert_order_note_not_contains( $order, 'POS: created by' );

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
