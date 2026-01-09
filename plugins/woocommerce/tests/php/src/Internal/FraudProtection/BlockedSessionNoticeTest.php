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
		delete_option( 'woocommerce_email_from_address' );
	}

	/**
	 * @testdox Should display error notice when woocommerce_before_checkout_form action fires for blocked sessions.
	 */
	public function test_checkout_action_displays_blocked_message(): void {
		$this->mock_session_manager->method( 'is_session_blocked' )->willReturn( true );

		ob_start();
		do_action( 'woocommerce_before_checkout_form' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$output = ob_get_clean();

		$this->assertStringContainsString( 'unable to process this request online', $output, 'Should display blocked message on checkout' );
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
	 * @testdox get_message_html should return the expected HTML message with mailto link.
	 */
	public function test_get_message_html(): void {
		$message = $this->sut->get_message_html();

		$this->assertEquals(
			'We are unable to process this request online. Please <a href="mailto:support@example.com">contact support (support@example.com)</a> to complete your purchase.',
			$message
		);
	}

	/**
	 * @testdox get_message_plaintext should return the expected plaintext message.
	 */
	public function test_get_message_plaintext(): void {
		$message = $this->sut->get_message_plaintext();

		$this->assertEquals(
			'We are unable to process this request online. Please contact support (support@example.com) to complete your purchase.',
			$message
		);
	}
}
