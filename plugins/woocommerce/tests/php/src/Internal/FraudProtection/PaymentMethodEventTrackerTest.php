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

		$this->assertLogged(
			'info',
			'payment_method_added',
			array(
				'source'         => 'woo-fraud-protection',
				'event_type'     => 'payment_method_added',
				'collected_data' => array(
					'event_data' => array(
						'action'     => 'added',
						'token_id'   => $token->get_id(),
						'gateway_id' => 'stripe',
					),
				),
			)
		);
	}

	/**
	 * @testdox Should track payment method updated event.
	 */
	public function test_handle_payment_method_updated(): void {
		$this->sut->register();

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

		// Update the token to trigger the 'updated' event.
		$token->set_expiry_year( '2027' );
		$token->save();

		$this->assertLogged(
			'info',
			'payment_method_updated',
			array(
				'source'         => 'woo-fraud-protection',
				'event_type'     => 'payment_method_updated',
				'collected_data' => array(
					'event_data' => array(
						'action'     => 'updated',
						'token_id'   => $token->get_id(),
						'gateway_id' => 'stripe',
					),
				),
			)
		);
	}

	/**
	 * @testdox Should track payment method set as default event.
	 */
	public function test_handle_payment_method_set_default(): void {
		$this->sut->register();

		$user_id = $this->factory->user->create();

		// Create first token (will be automatically set as default since it's the user's first token).
		$token1 = new \WC_Payment_Token_CC();
		$token1->set_token( 'test_token_first' );
		$token1->set_gateway_id( 'stripe' );
		$token1->set_card_type( 'visa' );
		$token1->set_last4( '1111' );
		$token1->set_expiry_month( '01' );
		$token1->set_expiry_year( '2026' );
		$token1->set_user_id( $user_id );
		$token1->save();

		// Create second token (won't be default).
		$token2 = new \WC_Payment_Token_CC();
		$token2->set_token( 'test_token_789' );
		$token2->set_gateway_id( 'stripe' );
		$token2->set_card_type( 'amex' );
		$token2->set_last4( '0005' );
		$token2->set_expiry_month( '03' );
		$token2->set_expiry_year( '2027' );
		$token2->set_user_id( $user_id );
		$token2->save();

		// Note: We use do_action() here because WC_Payment_Tokens::set_users_default()
		// relies on get_customer_tokens() which doesn't retrieve tokens properly in the test environment.
		// In production, the hook is triggered by WC_Payment_Tokens::set_users_default().
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WooCommerce.Commenting.CommentHooks.MissingSinceComment
		do_action( 'woocommerce_payment_token_set_default', $token2->get_id(), $token2 );

		$this->assertLogged(
			'info',
			'payment_method_set_default',
			array(
				'source'         => 'woo-fraud-protection',
				'event_type'     => 'payment_method_set_default',
				'collected_data' => array(
					'event_data' => array(
						'action'     => 'set_default',
						'token_id'   => $token2->get_id(),
						'gateway_id' => 'stripe',
						'is_default' => true,
					),
				),
			)
		);
	}

	/**
	 * @testdox Should track payment method deleted event.
	 */
	public function test_handle_payment_method_deleted(): void {
		$this->sut->register();

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

		// Delete the token to trigger the 'deleted' event.
		\WC_Payment_Tokens::delete( $token->get_id() );

		$this->assertLogged(
			'info',
			'payment_method_deleted',
			array(
				'source'         => 'woo-fraud-protection',
				'event_type'     => 'payment_method_deleted',
				'collected_data' => array(
					'event_data' => array(
						'action'     => 'deleted',
						'token_id'   => $token->get_id(),
						'gateway_id' => 'stripe',
					),
				),
			)
		);
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
