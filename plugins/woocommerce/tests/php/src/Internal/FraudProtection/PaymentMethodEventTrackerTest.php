<?php
/**
 * PaymentMethodEventTrackerTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\PaymentMethodEventTracker;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;

/**
 * Tests for the PaymentMethodEventTracker class.
 */
class PaymentMethodEventTrackerTest extends \WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable the fraud protection feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );
	}

	/**
	 * Get a testable tracker instance.
	 *
	 * @return PaymentMethodEventTracker
	 */
	private function get_testable_tracker(): PaymentMethodEventTracker {
		$container = wc_get_container();
		$container->reset_all_resolved();

		// Get tracker with real controller - logs will be captured by LoggerSpyTrait.
		$tracker = $container->get( PaymentMethodEventTracker::class );

		return $tracker;
	}

	/**
	 * Test that hooks are registered when feature is enabled.
	 */
	public function test_hooks_registered_when_feature_enabled(): void {
		$tracker = $this->get_testable_tracker();
		$tracker->register();

		$this->assertNotFalse( has_action( 'woocommerce_new_payment_token', array( $tracker, 'handle_payment_method_added' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_updated', array( $tracker, 'handle_payment_method_updated' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_set_default', array( $tracker, 'handle_payment_method_set_default' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_deleted', array( $tracker, 'handle_payment_method_deleted' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_add_failed', array( $tracker, 'handle_payment_method_add_failed' ) ) );
	}

	/**
	 * Test that hooks are not registered when feature is disabled.
	 */
	public function test_hooks_not_registered_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		$tracker = $this->get_testable_tracker();
		$tracker->register();

		$this->assertFalse( has_action( 'woocommerce_new_payment_token', array( $tracker, 'handle_payment_method_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_updated', array( $tracker, 'handle_payment_method_updated' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_set_default', array( $tracker, 'handle_payment_method_set_default' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_deleted', array( $tracker, 'handle_payment_method_deleted' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_add_failed', array( $tracker, 'handle_payment_method_add_failed' ) ) );
	}

	/**
	 * Test payment method added event tracking.
	 */
	public function test_handle_payment_method_added(): void {
		$tracker = $this->get_testable_tracker();
		$tracker->register();

		$user_id = $this->factory->user->create();
		
		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'test_token_123' );
		$token->set_gateway_id( 'stripe' );
		$token->set_card_type( 'visa' );
		$token->set_last4( '4242' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2025' );
		$token->set_user_id( $user_id );
		$token->save();

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'info', $this->captured_logs[0]['level'] );
		$this->assertStringContainsString( 'payment_method_added', $this->captured_logs[0]['message'] );
		$this->assertEquals( 'payment_method_added', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'added', $this->captured_logs[0]['context']['event_data']['action'] );
		$this->assertEquals( $token->get_id(), $this->captured_logs[0]['context']['event_data']['token_id'] );
		$this->assertEquals( 'stripe', $this->captured_logs[0]['context']['event_data']['gateway_id'] );
	}

	/**
	 * Test payment method updated event tracking.
	 */
	public function test_handle_payment_method_updated(): void {
		$user_id = $this->factory->user->create();

		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'test_token_456' );
		$token->set_gateway_id( 'stripe' );
		$token->set_card_type( 'mastercard' );
		$token->set_last4( '5555' );
		$token->set_expiry_month( '06' );
		$token->set_expiry_year( '2026' );
		$token->set_user_id( $user_id );
		$token->save();

		$tracker = $this->get_testable_tracker();
		$tracker->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_updated', $token->get_id() );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_updated', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'updated', $this->captured_logs[0]['context']['event_data']['action'] );
	}

	/**
	 * Test payment method set as default event tracking.
	 */
	public function test_handle_payment_method_set_default(): void {
		$user_id = $this->factory->user->create();

		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'test_token_789' );
		$token->set_gateway_id( 'stripe' );
		$token->set_card_type( 'amex' );
		$token->set_last4( '0005' );
		$token->set_expiry_month( '03' );
		$token->set_expiry_year( '2027' );
		$token->set_user_id( $user_id );
		$token->set_default( true );
		$token->save();

		$tracker = $this->get_testable_tracker();
		$tracker->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_set_default', $token->get_id(), $token );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_set_default', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'set_default', $this->captured_logs[0]['context']['event_data']['action'] );
		$this->assertTrue( $this->captured_logs[0]['context']['event_data']['is_default'] );
	}

	/**
	 * Test payment method deleted event tracking.
	 */
	public function test_handle_payment_method_deleted(): void {
		$user_id = $this->factory->user->create();

		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'test_token_delete' );
		$token->set_gateway_id( 'stripe' );
		$token->set_card_type( 'visa' );
		$token->set_last4( '1111' );
		$token->set_expiry_month( '09' );
		$token->set_expiry_year( '2028' );
		$token->set_user_id( $user_id );
		$token->save();

		$tracker = $this->get_testable_tracker();
		$tracker->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_deleted', $token->get_id(), $token );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_deleted', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'deleted', $this->captured_logs[0]['context']['event_data']['action'] );
	}

	/**
	 * Test payment method add failed event tracking.
	 */
	public function test_handle_payment_method_add_failed(): void {
		$user_id = $this->factory->user->create();

		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'test_token_failed' );
		$token->set_gateway_id( 'stripe' );
		$token->set_card_type( 'visa' );
		$token->set_last4( '0002' );
		$token->set_expiry_month( '01' );
		$token->set_expiry_year( '2024' );
		$token->set_user_id( $user_id );

		$tracker = $this->get_testable_tracker();
		$tracker->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_add_failed', $token, 'card_declined' );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_add_failed', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'add_failed', $this->captured_logs[0]['context']['event_data']['action'] );
		$this->assertEquals( 'card_declined', $this->captured_logs[0]['context']['event_data']['failure_reason'] );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove all hooks.
		remove_all_actions( 'woocommerce_new_payment_token' );
		remove_all_actions( 'woocommerce_payment_token_updated' );
		remove_all_actions( 'woocommerce_payment_token_set_default' );
		remove_all_actions( 'woocommerce_payment_token_deleted' );
		remove_all_actions( 'woocommerce_payment_token_add_failed' );

		// Clean up options.
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );

		// Reset container.
		wc_get_container()->reset_all_resolved();
	}
}
