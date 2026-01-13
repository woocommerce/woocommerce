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
	 * Mock fraud protection dispatcher.
	 *
	 * @var \Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_dispatcher;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Set jetpack_activation_source option to prevent "Cannot use bool as array" error
		// in Jetpack Connection Manager's apply_activation_source_to_args method.
		update_option( 'jetpack_activation_source', array( '', '' ) );

		// Enable the fraud protection feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		$container = wc_get_container();
		$container->reset_all_resolved();

		$this->sut = $container->get( PaymentMethodEventTracker::class );
	}

	/**
	 * Test add payment method page loaded event tracking.
	 *
	 * @testdox Should track add payment method page loaded event.
	 */
	public function test_track_add_payment_method_page_loaded_dispatches_event(): void {
		// Create mock dispatcher.
		$this->mock_dispatcher = $this->createMock( \Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher::class );

		// Create system under test with mock dispatcher.
		$sut = new PaymentMethodEventTracker();
		$sut->init( $this->mock_dispatcher );

		// Mock dispatcher to verify event is dispatched with empty event data.
		$this->mock_dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with(
				$this->equalTo( 'add_payment_method_page_loaded' ),
				$this->equalTo( array() )
			);

		// Call the method.
		$sut->track_add_payment_method_page_loaded();
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
		delete_option( 'jetpack_activation_source' );

		// Reset container.
		wc_get_container()->reset_all_resolved();
	}
}
