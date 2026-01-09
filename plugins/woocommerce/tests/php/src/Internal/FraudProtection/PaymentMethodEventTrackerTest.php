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
	 * Test payment method added event tracking.
	 *
	 * @testdox Should track payment method added event.
	 */
	public function test_handle_payment_method_added(): void {

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

		// Verify that the event was sent to the API with correct payload.
		$this->assertLogged(
			'info',
			'Sending fraud protection event: payment_method_added',
			array(
				'source'  => 'woo-fraud-protection',
				'payload' => array(
					'event_type' => 'payment_method_added',
					'event_data' => array(
						'action'     => 'added',
						'token_id'   => $token->get_id(),
						'gateway_id' => 'stripe',
						'card_type'  => 'visa',
						'card_last4' => '4242',
					),
				),
			)
		);
	}

	/**
	 * Test payment method updated event tracking.
	 *
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

		// Update the token to trigger the 'updated' event.
		$token->set_expiry_year( '2027' );
		$token->save();

		// Verify that the event was sent to the API with correct payload.
		$this->assertLogged(
			'info',
			'Sending fraud protection event: payment_method_updated',
			array(
				'source'  => 'woo-fraud-protection',
				'payload' => array(
					'event_type' => 'payment_method_updated',
					'event_data' => array(
						'action'     => 'updated',
						'token_id'   => $token->get_id(),
						'gateway_id' => 'stripe',
						'card_type'  => 'mastercard',
					),
				),
			)
		);
	}

	/**
	 * Test payment method set as default event tracking.
	 *
	 * @testdox Should track payment method set as default event.
	 */
	public function test_handle_payment_method_set_default(): void {

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
		$this->sut->track_payment_method_set_default( $token2->get_id(), $token2 );

		// Verify that the event was sent to the API with correct payload.
		$this->assertLogged(
			'info',
			'Sending fraud protection event: payment_method_set_default',
			array(
				'source'  => 'woo-fraud-protection',
				'payload' => array(
					'event_type' => 'payment_method_set_default',
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
	 * Test payment method deleted event tracking.
	 *
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

		// Delete the token to trigger the 'deleted' event.
		\WC_Payment_Tokens::delete( $token->get_id() );

		// Verify that the event was sent to the API with correct payload.
		$this->assertLogged(
			'info',
			'Sending fraud protection event: payment_method_deleted',
			array(
				'source'  => 'woo-fraud-protection',
				'payload' => array(
					'event_type' => 'payment_method_deleted',
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

		// Clean up options.
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );

		// Reset container.
		wc_get_container()->reset_all_resolved();
	}
}
