<?php
/**
 * CartBlockingTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\BlockedSessionNotice;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * Tests for cart blocking when session is blocked by fraud protection.
 *
 * Tests WC_Cart method integration (add_to_cart, remove_cart_item, set_quantity).
 *
 * @covers \WC_Cart
 */
class CartBlockingTest extends \WC_Unit_Test_Case {

	/**
	 * Mock FraudProtectionController.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $fraud_controller_mock;

	/**
	 * Mock SessionClearanceManager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $session_manager_mock;

	/**
	 * Mock BlockedSessionNotice.
	 *
	 * @var BlockedSessionNotice|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $blocked_notice_mock;

	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->product = \WC_Helper_Product::create_simple_product();

		$this->fraud_controller_mock = $this->createMock( FraudProtectionController::class );
		$this->session_manager_mock  = $this->createMock( SessionClearanceManager::class );
		$this->blocked_notice_mock   = $this->createMock( BlockedSessionNotice::class );

		wc_get_container()->replace( FraudProtectionController::class, $this->fraud_controller_mock );
		wc_get_container()->replace( SessionClearanceManager::class, $this->session_manager_mock );
		wc_get_container()->replace( BlockedSessionNotice::class, $this->blocked_notice_mock );

		wc_empty_cart();
		wc_clear_notices();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		wc_get_container()->reset_replacement( FraudProtectionController::class );
		wc_get_container()->reset_replacement( SessionClearanceManager::class );
		wc_get_container()->reset_replacement( BlockedSessionNotice::class );

		$this->product->delete( true );
		wc_empty_cart();
		wc_clear_notices();

		parent::tearDown();
	}

	/**
	 * Test add to cart blocked when session blocked.
	 *
	 * @testdox add_to_cart returns false and adds notice when session is blocked.
	 */
	public function test_add_to_cart_blocked_when_session_blocked(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( true );
		$this->blocked_notice_mock->method( 'get_message_html' )->willReturn( 'Blocked message' );

		$result = WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		$this->assertFalse( $result );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );
		$this->assertTrue( wc_has_notice( 'Blocked message', 'error' ) );
	}

	/**
	 * Test add to cart allowed when session allowed.
	 *
	 * @testdox add_to_cart succeeds when session is allowed.
	 */
	public function test_add_to_cart_allowed_when_session_allowed(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( false );

		$result = WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		$this->assertNotFalse( $result );
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test add to cart allowed when feature disabled.
	 *
	 * @testdox add_to_cart succeeds when fraud protection is disabled.
	 */
	public function test_add_to_cart_allowed_when_feature_disabled(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( false );
		$this->session_manager_mock->expects( $this->never() )->method( 'is_session_blocked' );

		$result = WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		$this->assertNotFalse( $result );
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test remove cart item blocked when session blocked.
	 *
	 * @testdox remove_cart_item returns false when session is blocked.
	 */
	public function test_remove_cart_item_blocked_when_session_blocked(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock
			->method( 'is_session_blocked' )
			->willReturnOnConsecutiveCalls( false, true ); // Allow add, block remove.
		$this->blocked_notice_mock->method( 'get_message_html' )->willReturn( 'Blocked message' );

		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$result        = WC()->cart->remove_cart_item( $cart_item_key );

		$this->assertFalse( $result );
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test remove cart item allowed when session allowed.
	 *
	 * @testdox remove_cart_item succeeds when session is allowed.
	 */
	public function test_remove_cart_item_allowed_when_session_allowed(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( false );

		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$result        = WC()->cart->remove_cart_item( $cart_item_key );

		$this->assertTrue( $result );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test remove cart item allowed when feature disabled.
	 *
	 * @testdox remove_cart_item succeeds when fraud protection is disabled.
	 */
	public function test_remove_cart_item_allowed_when_feature_disabled(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( false );

		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$result        = WC()->cart->remove_cart_item( $cart_item_key );

		$this->assertTrue( $result );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test set quantity blocked when session blocked.
	 *
	 * @testdox set_quantity returns false when session is blocked.
	 */
	public function test_set_quantity_blocked_when_session_blocked(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock
			->method( 'is_session_blocked' )
			->willReturnOnConsecutiveCalls( false, true ); // Allow add, block update.
		$this->blocked_notice_mock->method( 'get_message_html' )->willReturn( 'Blocked message' );

		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$result        = WC()->cart->set_quantity( $cart_item_key, 5 );

		$this->assertFalse( $result );
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test set quantity allowed when session allowed.
	 *
	 * @testdox set_quantity succeeds when session is allowed.
	 */
	public function test_set_quantity_allowed_when_session_allowed(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( false );

		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$result        = WC()->cart->set_quantity( $cart_item_key, 5 );

		$this->assertTrue( $result );
		$this->assertEquals( 5, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test set quantity allowed when feature disabled.
	 *
	 * @testdox set_quantity succeeds when fraud protection is disabled.
	 */
	public function test_set_quantity_allowed_when_feature_disabled(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( false );

		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$result        = WC()->cart->set_quantity( $cart_item_key, 5 );

		$this->assertTrue( $result );
		$this->assertEquals( 5, WC()->cart->get_cart_contents_count() );
	}
}
