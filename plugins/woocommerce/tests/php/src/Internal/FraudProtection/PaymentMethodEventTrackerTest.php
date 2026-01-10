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
