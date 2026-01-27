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
}
