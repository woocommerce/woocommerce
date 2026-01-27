<?php
/**
 * SessionClearanceManagerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * Tests for SessionClearanceManager.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager
 */
class SessionClearanceManagerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var SessionClearanceManager
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		WC()->session = new \WC_Session_Handler();
		WC()->session->init();

		$this->sut = new SessionClearanceManager();
	}

	/**
	 * Test that session status constants are defined correctly.
	 */
	public function test_session_status_constants() {
		$this->assertEquals( 'pending', SessionClearanceManager::STATUS_PENDING );
		$this->assertEquals( 'allowed', SessionClearanceManager::STATUS_ALLOWED );
		$this->assertEquals( 'blocked', SessionClearanceManager::STATUS_BLOCKED );
		// Default status is PENDING for Blackbox integration (payment methods hidden until verified).
		$this->assertEquals( SessionClearanceManager::STATUS_PENDING, SessionClearanceManager::DEFAULT_STATUS );
	}

	/**
	 * Test default session status when session is not available.
	 */
	public function test_default_session_status_without_session() {
		// If session is not available, should return DEFAULT_STATUS.
		$status = $this->sut->get_session_status();
		$this->assertEquals( SessionClearanceManager::DEFAULT_STATUS, $status );
	}

	/**
	 * Test that is_session_allowed returns true for allowed status.
	 */
	public function test_is_session_allowed_returns_true_for_allowed() {
		$this->sut->allow_session();
		$this->assertTrue( $this->sut->is_session_allowed() );
		$this->assertFalse( $this->sut->is_session_blocked() );
	}

	/**
	 * Test that pending session is neither allowed nor blocked.
	 */
	public function test_is_session_allowed_returns_false_for_pending() {
		$this->sut->challenge_session();
		$this->assertFalse( $this->sut->is_session_allowed() );
		$this->assertFalse( $this->sut->is_session_blocked() );
	}

	/**
	 * Test blocked status.
	 */
	public function test_is_session_allowed_returns_false_for_blocked() {
		$this->sut->block_session();
		$this->assertFalse( $this->sut->is_session_allowed() );
		$this->assertTrue( $this->sut->is_session_blocked() );
	}

	/**
	 * Test block_session empties the cart.
	 */
	public function test_block_session_empties_cart() {
		// Add item to cart.
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertGreaterThan( 0, WC()->cart->get_cart_contents_count() );

		// Block session should empty cart.
		$this->sut->block_session();
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test reset_session sets status to DEFAULT_STATUS.
	 */
	public function test_reset_session_sets_status_to_default() {
		// Set to blocked first.
		$this->sut->block_session();
		$this->assertEquals( SessionClearanceManager::STATUS_BLOCKED, $this->sut->get_session_status() );

		// Reset should go back to DEFAULT_STATUS.
		$this->sut->reset_session();
		$this->assertEquals( SessionClearanceManager::DEFAULT_STATUS, $this->sut->get_session_status() );
	}

	/**
	 * Test session status transitions.
	 */
	public function test_session_status_transitions() {
		// Start with allowed.
		$this->sut->allow_session();
		$this->assertEquals( SessionClearanceManager::STATUS_ALLOWED, $this->sut->get_session_status() );

		// Transition to pending.
		$this->sut->challenge_session();
		$this->assertEquals( SessionClearanceManager::STATUS_PENDING, $this->sut->get_session_status() );

		// Transition to blocked.
		$this->sut->block_session();
		$this->assertEquals( SessionClearanceManager::STATUS_BLOCKED, $this->sut->get_session_status() );

		// Transition back to allowed.
		$this->sut->allow_session();
		$this->assertEquals( SessionClearanceManager::STATUS_ALLOWED, $this->sut->get_session_status() );
	}

	/**
	 * Test get_session_status returns default status for invalid stored values.
	 */
	public function test_get_session_status_returns_default_status_for_invalid_values() {
		// Set an invalid value directly in session.
		WC()->session->set( '_fraud_protection_clearance_status', 'invalid_status' );

		// Should return default status for invalid values.
		$this->assertEquals( SessionClearanceManager::DEFAULT_STATUS, $this->sut->get_session_status() );
	}

	/**
	 * Test should_render_payment_methods returns true only for allowed status.
	 */
	public function test_should_render_payment_methods_returns_true_only_for_allowed() {
		// Default (pending) - should NOT render.
		$this->assertFalse( $this->sut->should_render_payment_methods() );

		// Allowed - should render.
		$this->sut->allow_session();
		$this->assertTrue( $this->sut->should_render_payment_methods() );

		// Pending - should NOT render.
		$this->sut->challenge_session();
		$this->assertFalse( $this->sut->should_render_payment_methods() );

		// Blocked - should NOT render.
		$this->sut->block_session();
		$this->assertFalse( $this->sut->should_render_payment_methods() );
	}

	/**
	 * Test set_blackbox_session_id and get_blackbox_session_id.
	 */
	public function test_blackbox_session_id_storage() {
		// Initially null.
		$this->assertNull( $this->sut->get_blackbox_session_id() );

		// Set and retrieve.
		$session_id = 'bb_test_session_123';
		$this->sut->set_blackbox_session_id( $session_id );
		$this->assertEquals( $session_id, $this->sut->get_blackbox_session_id() );

		// Can update to new value.
		$new_session_id = 'bb_test_session_456';
		$this->sut->set_blackbox_session_id( $new_session_id );
		$this->assertEquals( $new_session_id, $this->sut->get_blackbox_session_id() );
	}

	/**
	 * Test that default status (PENDING) means payment methods are not rendered.
	 */
	public function test_default_pending_status_hides_payment_methods() {
		// Fresh session should default to PENDING.
		$this->assertEquals( SessionClearanceManager::STATUS_PENDING, $this->sut->get_session_status() );

		// Payment methods should not render for PENDING status.
		$this->assertFalse( $this->sut->should_render_payment_methods() );

		// After verification (allow), payment methods should render.
		$this->sut->allow_session();
		$this->assertTrue( $this->sut->should_render_payment_methods() );
	}

	/**
	 * Test queue_event adds event to session.
	 */
	public function test_queue_event_adds_event_to_session() {
		$event_type = 'cart_item_added';
		$event_data = array(
			'session'    => array( 'session_id' => 'test-123' ),
			'product_id' => 456,
		);

		$this->sut->queue_event( $event_type, $event_data );

		$queue = $this->sut->get_event_queue();
		$this->assertCount( 1, $queue );
		$this->assertEquals( $event_type, $queue[0]['event_type'] );
		$this->assertEquals( $event_data, $queue[0]['event_data'] );
		$this->assertArrayHasKey( 'timestamp', $queue[0] );
	}

	/**
	 * Test queue_event includes ISO 8601 timestamp.
	 */
	public function test_queue_event_includes_timestamp() {
		$this->sut->queue_event( 'cart_item_added', array( 'product_id' => 123 ) );

		$queue     = $this->sut->get_event_queue();
		$timestamp = $queue[0]['timestamp'];

		// Verify timestamp is in ISO 8601 format (e.g., 2024-01-27T10:00:00+00:00).
		$parsed = \DateTime::createFromFormat( \DateTime::ATOM, $timestamp );
		$this->assertNotFalse( $parsed, 'Timestamp should be in ISO 8601 format' );
	}

	/**
	 * Test get_event_queue returns empty array when no events queued.
	 */
	public function test_get_event_queue_returns_empty_array_when_no_events() {
		$queue = $this->sut->get_event_queue();
		$this->assertIsArray( $queue );
		$this->assertEmpty( $queue );
	}

	/**
	 * Test get_event_queue returns all queued events in order.
	 */
	public function test_get_event_queue_returns_all_events_in_order() {
		$this->sut->queue_event( 'cart_item_added', array( 'product_id' => 1 ) );
		$this->sut->queue_event( 'cart_item_updated', array( 'product_id' => 2 ) );
		$this->sut->queue_event( 'checkout_page_loaded', array() );

		$queue = $this->sut->get_event_queue();
		$this->assertCount( 3, $queue );
		$this->assertEquals( 'cart_item_added', $queue[0]['event_type'] );
		$this->assertEquals( 'cart_item_updated', $queue[1]['event_type'] );
		$this->assertEquals( 'checkout_page_loaded', $queue[2]['event_type'] );
	}

	/**
	 * Test clear_event_queue empties the queue.
	 */
	public function test_clear_event_queue_empties_queue() {
		// Add some events.
		$this->sut->queue_event( 'cart_item_added', array( 'product_id' => 1 ) );
		$this->sut->queue_event( 'cart_item_updated', array( 'product_id' => 2 ) );

		$this->assertCount( 2, $this->sut->get_event_queue() );

		// Clear the queue.
		$this->sut->clear_event_queue();

		$this->assertEmpty( $this->sut->get_event_queue() );
	}

	/**
	 * Test event queue limits to max size to prevent session bloat.
	 */
	public function test_event_queue_limits_to_max_size() {
		// Queue more than the limit (50 events).
		for ( $i = 1; $i <= 60; $i++ ) {
			$this->sut->queue_event( 'cart_item_added', array( 'product_id' => $i ) );
		}

		$queue = $this->sut->get_event_queue();

		// Should be limited to 50 events.
		$this->assertCount( 50, $queue );

		// Should keep the most recent events (11-60).
		$first_event = $queue[0];
		$this->assertEquals( 11, $first_event['event_data']['product_id'] );

		$last_event = $queue[49];
		$this->assertEquals( 60, $last_event['event_data']['product_id'] );
	}

	/**
	 * Test event queue persists across multiple SessionClearanceManager instances.
	 */
	public function test_event_queue_persists_across_instances() {
		// Queue event with first instance.
		$this->sut->queue_event( 'cart_item_added', array( 'product_id' => 123 ) );

		// Create new instance and verify event is still there.
		$new_instance = new SessionClearanceManager();
		$queue        = $new_instance->get_event_queue();

		$this->assertCount( 1, $queue );
		$this->assertEquals( 'cart_item_added', $queue[0]['event_type'] );
	}
}
