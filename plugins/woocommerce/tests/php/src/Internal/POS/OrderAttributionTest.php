<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use Automattic\WooCommerce\Internal\POS\POSPreset;
use WC_Unit_Test_Case;

/**
 * Tests for OrderAttribution — staff attribution on POS order/refund writes.
 *
 * The actor is the effective current user (the swapped staff); the optional initiator rides the
 * X-WC-POS-Initiator-Id header, fed here through the mocked context.
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
	 * Build the SUT with the POS-request flag and the initiator the header would carry.
	 *
	 * @param bool     $is_pos       Whether the request is POS-originated.
	 * @param int|null $initiator_id The initiator id the X-WC-POS-Initiator-Id header carries (null = none).
	 * @return OrderAttribution
	 */
	private function make_sut( bool $is_pos = true, ?int $initiator_id = null ): OrderAttribution {
		$ctx = $this->createMock( POSRequestContext::class );
		$ctx->method( 'is_pos_request' )->willReturn( $is_pos );
		$ctx->method( 'get_initiator_id' )->willReturn( $initiator_id );

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
	 * @testdox Records the actor (meta + note) on a plain POS write.
	 */
	public function test_records_actor_on_plain_write(): void {
		$order = wc_create_order();
		$order->save();

		$this->make_sut( true )->handle_post_insert( $order, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes, 'A POS write should record one actor note' );
		$this->assertStringContainsString( 'created by', $notes[0] );
		$this->assertStringNotContainsString( 'initiated by', $notes[0] );

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( (string) $this->actor_id, (string) $saved->get_meta( OrderAttribution::META_KEY_ACTOR_USER_ID ) );
		$this->assertSame( '', (string) $saved->get_meta( OrderAttribution::META_KEY_INITIATOR_USER_ID ) );
	}

	/**
	 * @testdox Records both the actor and the initiator when the header is present.
	 */
	public function test_records_actor_and_initiator(): void {
		$order = wc_create_order();
		$order->save();

		$this->make_sut( true, $this->initiator_id )->handle_post_insert( $order, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'initiated by', $notes[0] );

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( (string) $this->actor_id, (string) $saved->get_meta( OrderAttribution::META_KEY_ACTOR_USER_ID ) );
		$this->assertSame( (string) $this->initiator_id, (string) $saved->get_meta( OrderAttribution::META_KEY_INITIATOR_USER_ID ) );
	}

	/**
	 * @testdox Records nothing when the request is not POS-originated.
	 */
	public function test_no_attribution_when_not_pos_request(): void {
		$order = wc_create_order();
		$order->save();

		$this->make_sut( false, $this->initiator_id )->handle_post_insert( $order, null, true );

		$this->assertCount( 0, $this->pos_notes( $order->get_id() ) );
		$this->assertSame( '', (string) wc_get_order( $order->get_id() )->get_meta( OrderAttribution::META_KEY_ACTOR_USER_ID ) );
	}

	/**
	 * @testdox Records nothing when the effective user is not POS staff (swap did not land).
	 */
	public function test_no_attribution_when_actor_lacks_pos_access(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$order = wc_create_order();
		$order->save();

		$this->make_sut( true )->handle_post_insert( $order, null, true );

		$this->assertCount( 0, $this->pos_notes( $order->get_id() ), 'A non-staff current user is not attributed' );
	}

	/**
	 * @testdox Ignores an initiator equal to the actor (still records the actor).
	 */
	public function test_initiator_equal_to_actor_is_ignored(): void {
		$order = wc_create_order();
		$order->save();

		$this->make_sut( true, $this->actor_id )->handle_post_insert( $order, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes );
		$this->assertStringNotContainsString( 'initiated by', $notes[0] );
		$this->assertSame( '', (string) wc_get_order( $order->get_id() )->get_meta( OrderAttribution::META_KEY_INITIATOR_USER_ID ) );
	}

	/**
	 * @testdox Skips an initiator without POS access but still records the actor.
	 */
	public function test_skips_invalid_initiator_but_records_actor(): void {
		$stranger = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$order    = wc_create_order();
		$order->save();

		$this->make_sut( true, $stranger )->handle_post_insert( $order, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes );
		$this->assertStringNotContainsString( 'initiated by', $notes[0] );
		$this->assertSame( (string) $this->actor_id, (string) wc_get_order( $order->get_id() )->get_meta( OrderAttribution::META_KEY_ACTOR_USER_ID ) );
	}

	/**
	 * @testdox Attaches the refund attribution note to the parent order.
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

		$this->make_sut( true, $this->initiator_id )->handle_post_insert( $refund, null, true );

		$notes = $this->pos_notes( $order->get_id() );
		$this->assertCount( 1, $notes, 'The refund note should land on the parent order' );
		$this->assertStringContainsString( 'refunded by', $notes[0] );
		$this->assertStringContainsString( 'initiated by', $notes[0] );
	}
}
