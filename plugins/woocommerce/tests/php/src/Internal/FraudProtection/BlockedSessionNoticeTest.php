<?php
/**
 * BlockedSessionNoticeTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\BlockedSessionNotice;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * Tests for BlockedSessionNotice.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\BlockedSessionNotice
 */
class BlockedSessionNoticeTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BlockedSessionNotice
	 */
	private $sut;

	/**
	 * Mock session clearance manager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_session_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mock_session_manager = $this->createMock( SessionClearanceManager::class );

		$this->sut = new BlockedSessionNotice();
		$this->sut->init( $this->mock_session_manager );
		$this->sut->register();

		// Set a custom support email.
		update_option( 'woocommerce_email_from_address', 'support@example.com' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_actions( 'woocommerce_before_checkout_form' );
		remove_all_actions( 'before_woocommerce_add_payment_method' );
		delete_option( 'woocommerce_email_from_address' );
	}

	/**
	 * @testdox Should display checkout-specific error notice when woocommerce_before_checkout_form action fires for blocked sessions.
	 */
	public function test_checkout_action_displays_blocked_message(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		ob_start();
		do_action( 'woocommerce_before_checkout_form' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$output = ob_get_clean();

		$this->assertStringContainsString( 'unable to process this request online', $output, 'Should display blocked message on checkout' );
		$this->assertStringContainsString( 'to complete your purchase', $output, 'Should display checkout-specific message' );
		$this->assertStringContainsString( 'support@example.com', $output, 'Should include support email in message' );
		$this->assertStringContainsString( 'mailto:support@example.com', $output, 'Should include mailto link' );
	}

	/**
	 * @testdox Should not display message when checkout action fires for non-blocked sessions.
	 */
	public function test_checkout_action_no_message_for_non_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( false );

		ob_start();
		do_action( 'woocommerce_before_checkout_form' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Non-blocked sessions should not display any message' );
	}

	/**
	 * @testdox Should display generic error notice when before_woocommerce_add_payment_method action fires for blocked sessions.
	 */
	public function test_add_payment_method_action_displays_blocked_message(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		ob_start();
		do_action( 'before_woocommerce_add_payment_method' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$output = ob_get_clean();

		$this->assertStringContainsString( 'unable to process this request online', $output, 'Should display blocked message on add payment method page' );
		$this->assertStringContainsString( 'for assistance', $output, 'Should display generic message' );
		$this->assertStringContainsString( 'support@example.com', $output, 'Should include support email in message' );
		$this->assertStringContainsString( 'mailto:support@example.com', $output, 'Should include mailto link' );
	}

	/**
	 * @testdox Should not display message when add payment method action fires for non-blocked sessions.
	 */
	public function test_add_payment_method_action_no_message_for_non_blocked_session(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( false );

		ob_start();
		do_action( 'before_woocommerce_add_payment_method' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Non-blocked sessions should not display any message' );
	}

	/**
	 * @testdox get_message_html should return checkout-specific message when context is 'checkout'.
	 */
	public function test_get_message_html_checkout_context(): void {
		$message = $this->sut->get_message_html( 'checkout' );

		$this->assertEquals(
			'We are unable to process this request online. Please <a href="mailto:support@example.com">contact support (support@example.com)</a> to complete your purchase.',
			$message
		);
	}

	/**
	 * @testdox get_message_html should return generic message when context is 'generic' or not specified.
	 */
	public function test_get_message_html_generic_context(): void {
		$message_default  = $this->sut->get_message_html();
		$message_explicit = $this->sut->get_message_html( 'generic' );

		$expected = 'We are unable to process this request online. Please <a href="mailto:support@example.com">contact support (support@example.com)</a> for assistance.';

		$this->assertEquals( $expected, $message_default, 'Default context should return generic message' );
		$this->assertEquals( $expected, $message_explicit, 'Explicit generic context should return generic message' );
	}

	/**
	 * @testdox get_message_plaintext should return checkout-specific message when context is 'checkout'.
	 */
	public function test_get_message_plaintext_checkout_context(): void {
		$message = $this->sut->get_message_plaintext( 'checkout' );

		$this->assertEquals(
			'We are unable to process this request online. Please contact support (support@example.com) to complete your purchase.',
			$message
		);
	}

	/**
	 * @testdox get_message_plaintext should return generic message when context is 'generic' or not specified.
	 */
	public function test_get_message_plaintext_generic_context(): void {
		$message_default  = $this->sut->get_message_plaintext();
		$message_explicit = $this->sut->get_message_plaintext( 'generic' );

		$expected = 'We are unable to process this request online. Please contact support (support@example.com) for assistance.';

		$this->assertEquals( $expected, $message_default, 'Default context should return generic message' );
		$this->assertEquals( $expected, $message_explicit, 'Explicit generic context should return generic message' );
	}
}
