<?php
/**
 * PaymentMethodEventTrackerTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\PaymentMethodEventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * Tests for the PaymentMethodEventTracker class.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\PaymentMethodEventTracker
 */
class PaymentMethodEventTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PaymentMethodEventTracker
	 */
	private $sut;

	/**
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_collector;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mock.
		$this->mock_collector = $this->createMock( SessionDataCollector::class );

		// Create system under test with mock.
		$this->sut = new PaymentMethodEventTracker();
		$this->sut->init( $this->mock_collector );
	}

	/**
	 * Test add payment method page loaded collects data.
	 *
	 * @testdox track_add_payment_method_page_loaded() collects session data with empty event data.
	 */
	public function test_track_add_payment_method_page_loaded_collects_data(): void {
		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'add_payment_method_page_loaded' ),
				$this->equalTo( array() )
			);

		$this->sut->track_add_payment_method_page_loaded();
	}

	/**
	 * Test payment method added collects data.
	 *
	 * @testdox track_payment_method_added() collects session data with token details.
	 */
	public function test_track_payment_method_added_collects_data(): void {
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

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'payment_method_added' ),
				$this->callback(
					function ( $event_data ) use ( $token ) {
						$this->assertArrayHasKey( 'action', $event_data );
						$this->assertEquals( 'added', $event_data['action'] );
						$this->assertArrayHasKey( 'token_id', $event_data );
						$this->assertEquals( $token->get_id(), $event_data['token_id'] );
						$this->assertArrayHasKey( 'token_type', $event_data );
						$this->assertArrayHasKey( 'gateway_id', $event_data );
						$this->assertEquals( 'stripe', $event_data['gateway_id'] );
						$this->assertArrayHasKey( 'card_type', $event_data );
						$this->assertEquals( 'visa', $event_data['card_type'] );
						$this->assertArrayHasKey( 'card_last4', $event_data );
						$this->assertEquals( '4242', $event_data['card_last4'] );
						return true;
					}
				)
			);

		$this->sut->track_payment_method_added( $token->get_id(), $token );

		$token->delete();
	}

	/**
	 * Test payment method added includes expiry for CC tokens.
	 *
	 * @testdox track_payment_method_added() includes expiry info for CC tokens.
	 */
	public function test_track_payment_method_added_includes_expiry_for_cc_tokens(): void {
		$user_id = $this->factory->user->create();

		$token = new \WC_Payment_Token_CC();
		$token->set_token( 'test_token_456' );
		$token->set_gateway_id( 'stripe' );
		$token->set_card_type( 'mastercard' );
		$token->set_last4( '5555' );
		$token->set_expiry_month( '06' );
		$token->set_expiry_year( '2028' );
		$token->set_user_id( $user_id );
		$token->save();

		$this->mock_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( 'payment_method_added' ),
				$this->callback(
					function ( $event_data ) {
						$this->assertArrayHasKey( 'expiry_month', $event_data );
						$this->assertEquals( '06', $event_data['expiry_month'] );
						$this->assertArrayHasKey( 'expiry_year', $event_data );
						$this->assertEquals( '2028', $event_data['expiry_year'] );
						return true;
					}
				)
			);

		$this->sut->track_payment_method_added( $token->get_id(), $token );

		$token->delete();
	}
}
