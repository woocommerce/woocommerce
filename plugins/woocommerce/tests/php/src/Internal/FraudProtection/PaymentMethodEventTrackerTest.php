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
	 * The System Under Test.
	 *
	 * @var PaymentMethodEventTracker
	 */
	private $sut;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable the fraud protection feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		$container = wc_get_container();
		$container->reset_all_resolved();

		$this->sut = $container->get( PaymentMethodEventTracker::class );
	}

	/**
	 * @testdox Should register hooks when feature is enabled.
	 */
	public function test_hooks_registered_when_feature_enabled(): void {
		$this->sut->register();

		$this->assertNotFalse( has_action( 'woocommerce_new_payment_token', array( $this->sut, 'handle_payment_method_added' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_updated', array( $this->sut, 'handle_payment_method_updated' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_set_default', array( $this->sut, 'handle_payment_method_set_default' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_payment_token_deleted', array( $this->sut, 'handle_payment_method_deleted' ) ) );
	}

	/**
	 * @testdox Should not register hooks when feature is disabled.
	 */
	public function test_hooks_not_registered_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		$container = wc_get_container();
		$container->reset_all_resolved();
		$this->sut = $container->get( PaymentMethodEventTracker::class );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_new_payment_token', array( $this->sut, 'handle_payment_method_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_updated', array( $this->sut, 'handle_payment_method_updated' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_set_default', array( $this->sut, 'handle_payment_method_set_default' ) ) );
		$this->assertFalse( has_action( 'woocommerce_payment_token_deleted', array( $this->sut, 'handle_payment_method_deleted' ) ) );
	}

	/**
	 * @testdox Should track payment method added event.
	 */
	public function test_handle_payment_method_added(): void {
		$this->sut->register();

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
		$this->assertEquals( 'added', $this->captured_logs[0]['context']['collected_data']['event_data']['action'] );
		$this->assertEquals( $token->get_id(), $this->captured_logs[0]['context']['collected_data']['event_data']['token_id'] );
		$this->assertEquals( 'stripe', $this->captured_logs[0]['context']['collected_data']['event_data']['gateway_id'] );
	}

	/**
	 * @testdox Should track payment method updated event.
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

		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_updated', $token->get_id() );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_updated', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'updated', $this->captured_logs[0]['context']['collected_data']['event_data']['action'] );
	}

	/**
	 * @testdox Should track payment method set as default event.
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

		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_set_default', $token->get_id(), $token );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_set_default', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'set_default', $this->captured_logs[0]['context']['collected_data']['event_data']['action'] );
		$this->assertTrue( $this->captured_logs[0]['context']['collected_data']['event_data']['is_default'] );
	}

	/**
	 * @testdox Should track payment method deleted event.
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

		$this->sut->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_deleted', $token->get_id(), $token );

		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'payment_method_deleted', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( 'deleted', $this->captured_logs[0]['context']['collected_data']['event_data']['action'] );
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

		// Clean up options.
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );

		// Reset container.
		wc_get_container()->reset_all_resolved();
	}
}
