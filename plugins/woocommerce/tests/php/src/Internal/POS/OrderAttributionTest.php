<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use Automattic\WooCommerce\Internal\POS\POSPreset;
use WC_Unit_Test_Case;

/**
 * Tests for OrderAttribution — initiator recording on POS order/refund writes.
 */
class OrderAttributionTest extends WC_Unit_Test_Case {

	/**
	 * Acting staff member (current user) id.
	 *
	 * @var int
	 */
	private int $actor_id;

	/**
	 * Initiator staff member id (POS-access holder distinct from the actor).
	 *
	 * @var int
	 */
	private int $initiator_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->actor_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->actor_id, POSPreset::MANAGER );

		$this->initiator_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->initiator_id, POSPreset::CASHIER );

		wp_set_current_user( $this->actor_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Build the SUT with a POS-request flag.
	 *
	 * @param bool $is_pos Whether the request is POS-originated.
	 * @return OrderAttribution
	 */
	private function make_sut( bool $is_pos = true ): OrderAttribution {
		$ctx = $this->createMock( POSRequestContext::class );
		$ctx->method( 'is_pos_request' )->willReturn( $is_pos );

		$sut = new OrderAttribution();
		$sut->init( $ctx );
		return $sut;
	}

	/**
	 * Collect POS attribution notes for an order.
	 *
	 * @param int $order_id Order id.
	 * @return string[] Matching note contents.
	 */
	private function pos_notes( int $order_id ): array {
		$notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
		$out   = array();
		foreach ( $notes as $note ) {
			if ( false !== strpos( $note->content, 'POS:' ) ) {
				$out[] = $note->content;
			}
		}
		return $out;
	}

	/**
	 * @testdox Writes one combined note naming the actor and the initiator.
	 */
	public function test_writes_combined_initiator_note(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, $this->initiator_id );
		$order->save();

		$this->make_sut()->handle_post_insert( $order, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes, 'Exactly one POS attribution note should be written' );
		$this->assertStringContainsString( 'initiated by', $notes[0] );
	}

	/**
	 * @testdox Writes no note when there is no initiator meta (plain write).
	 */
	public function test_no_note_without_initiator(): void {
		$order = wc_create_order();
		$order->save();

		$this->make_sut()->handle_post_insert( $order, null, true );

		$this->assertCount( 0, $this->pos_notes( $order->get_id() ), 'A plain POS write needs no initiator note' );
	}

	/**
	 * @testdox Writes no note when the request is not POS-originated.
	 */
	public function test_no_note_when_not_pos_request(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, $this->initiator_id );
		$order->save();

		$this->make_sut( false )->handle_post_insert( $order, null, true );

		$this->assertCount( 0, $this->pos_notes( $order->get_id() ) );
	}

	/**
	 * @testdox Writes no note when the initiator equals the actor.
	 */
	public function test_no_note_when_initiator_is_actor(): void {
		$order = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, $this->actor_id );
		$order->save();

		$this->make_sut()->handle_post_insert( $order, null, true );

		$this->assertCount( 0, $this->pos_notes( $order->get_id() ), 'No separate initiator means no extra note' );
	}

	/**
	 * @testdox Skips (does not note) an initiator without POS access.
	 */
	public function test_skips_initiator_without_pos_access(): void {
		$stranger = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$order    = wc_create_order();
		$order->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, $stranger );
		$order->save();

		$this->make_sut()->handle_post_insert( $order, null, true );

		$this->assertCount( 0, $this->pos_notes( $order->get_id() ), 'A non-POS initiator id is skipped, not noted' );
	}

	/**
	 * @testdox Attaches the refund initiator note to the parent order.
	 */
	public function test_refund_note_attaches_to_parent_order(): void {
		$order = wc_create_order();
		$order->set_total( 10 );
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5,
			)
		);
		$this->assertNotWPError( $refund );
		$refund->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, $this->initiator_id );
		$refund->save();

		$this->make_sut()->handle_post_insert( $refund, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes, 'The refund initiator note should land on the parent order' );
		$this->assertStringContainsString( 'refunded by', $notes[0] );
	}
}
