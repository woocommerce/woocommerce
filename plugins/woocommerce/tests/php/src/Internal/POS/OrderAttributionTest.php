<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use WC_Install;
use WC_Order;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the OrderAttribution lifecycle hooks.
 *
 * Hooks are exercised directly (rather than through the full REST stack) to keep the
 * test focused on the validation + note-writing behavior.
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
		WC_Install::create_roles();
		$this->sut = new OrderAttribution();
	}

	/**
	 * @testdox Should pass orders without POS attribution through unchanged.
	 */
	public function test_pre_insert_passes_orders_without_attribution(): void {
		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );

		$result = $this->sut->handle_pre_insert( $order, $request, true );

		$this->assertSame( $order, $result );
	}

	/**
	 * @testdox Should return a WP_Error when staff_user_id references a missing user.
	 */
	public function test_pre_insert_rejects_unknown_user_id(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY, array( 'staff_user_id' => 99999999 ) );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return a WP_Error when the attribution user lacks view_pos.
	 */
	public function test_pre_insert_rejects_user_without_view_pos(): void {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order    = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY, array( 'staff_user_id' => $customer ) );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );

		wp_delete_user( $customer );
	}

	/**
	 * @testdox Should return a WP_Error when the attribution payload is malformed.
	 */
	public function test_pre_insert_rejects_malformed_payload(): void {
		$cases = array(
			'string'        => 'not-an-array',
			'missing_field' => array( 'foo' => 'bar' ),
			'negative_id'   => array( 'staff_user_id' => -1 ),
			'non_numeric'   => array( 'staff_user_id' => 'mike' ),
		);

		foreach ( $cases as $label => $payload ) {
			$order = wc_create_order();
			$order->update_meta_data( OrderAttribution::META_KEY, $payload );

			$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

			$this->assertWPError( $result, sprintf( 'Case "%s" should produce a WP_Error.', $label ) );
		}
	}

	/**
	 * @testdox Should return the draft order unchanged when attribution is valid.
	 */
	public function test_pre_insert_accepts_valid_attribution(): void {
		$cashier = self::factory()->user->create( array( 'role' => Capabilities::ROLE_CASHIER ) );
		$order   = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY, array( 'staff_user_id' => $cashier ) );

		$result = $this->sut->handle_pre_insert( $order, new WP_REST_Request(), true );

		$this->assertSame( $order, $result );

		wp_delete_user( $cashier );
	}

	/**
	 * @testdox Should add an order note naming the attributed user on post-insert.
	 */
	public function test_post_insert_writes_order_note(): void {
		$cashier = self::factory()->user->create(
			array(
				'role'         => Capabilities::ROLE_CASHIER,
				'display_name' => 'Mike Cashier',
				'user_login'   => 'mike',
			)
		);

		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY, array( 'staff_user_id' => $cashier ) );
		$order->save();

		$this->sut->handle_post_insert( $order, new WP_REST_Request(), true );

		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'order_by' => 'date_created',
				'order'    => 'DESC',
				'limit'    => 5,
			)
		);

		$found = false;
		foreach ( $notes as $note ) {
			if ( false !== strpos( $note->content, 'POS: created by Mike Cashier (mike).' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected order note "POS: created by Mike Cashier (mike)." to exist.' );

		wp_delete_user( $cashier );
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
}
