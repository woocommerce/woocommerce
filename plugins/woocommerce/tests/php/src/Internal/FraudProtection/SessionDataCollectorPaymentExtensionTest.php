<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use WC_Unit_Test_Case;

/**
 * Tests for SessionDataCollector payment gateway extension points.
 *
 * @since 10.5.0
 */
class SessionDataCollectorPaymentExtensionTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var SessionDataCollector
	 */
	private $collector;

	/**
	 * Session clearance manager mock.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_manager;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create session clearance manager instance.
		$this->session_manager = wc_get_container()->get( SessionClearanceManager::class );

		// Create and initialize data collector.
		$this->collector = new SessionDataCollector();
		$this->collector->init( $this->session_manager );

		// Initialize WooCommerce session.
		if ( ! WC()->session ) {
			WC()->session = new \WC_Session_Handler();
			WC()->session->init();
		}
	}

	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove all filters we added during tests.
		remove_all_filters( 'woocommerce_fraud_protection_payment_data' );
		remove_all_filters( 'woocommerce_fraud_protection_payment_data_stripe' );
		remove_all_filters( 'woocommerce_fraud_protection_payment_data_paypal' );
		remove_all_filters( 'woocommerce_fraud_protection_payment_data_custom_gateway' );
	}

	/**
	 * Test that payment data fields are initialized with null values by default.
	 */
	public function test_payment_data_initialized_with_null_values() {
		$data = $this->collector->collect();

		// Verify all payment fields exist in the payment section.
		$this->assertArrayHasKey( 'payment', $data );
		$payment_data = $data['payment'];

		$this->assertArrayHasKey( 'payment_gateway_name', $payment_data );
		$this->assertArrayHasKey( 'payment_method_type', $payment_data );
		$this->assertArrayHasKey( 'card_bin', $payment_data );
		$this->assertNull( $payment_data['card_bin'] );

		$this->assertArrayHasKey( 'card_last4', $payment_data );
		$this->assertNull( $payment_data['card_last4'] );

		$this->assertArrayHasKey( 'card_brand', $payment_data );
		$this->assertNull( $payment_data['card_brand'] );

		$this->assertArrayHasKey( 'payer_id', $payment_data );
		$this->assertNull( $payment_data['payer_id'] );

		$this->assertArrayHasKey( 'outcome', $payment_data );
		$this->assertNull( $payment_data['outcome'] );

		$this->assertArrayHasKey( 'decline_reason', $payment_data );
		$this->assertNull( $payment_data['decline_reason'] );

		$this->assertArrayHasKey( 'avs_result', $payment_data );
		$this->assertNull( $payment_data['avs_result'] );

		$this->assertArrayHasKey( 'cvc_result', $payment_data );
		$this->assertNull( $payment_data['cvc_result'] );

		$this->assertArrayHasKey( 'tokenized_card_identifier', $payment_data );
		$this->assertNull( $payment_data['tokenized_card_identifier'] );
	}

	/**
	 * Test the general payment data filter is applied.
	 */
	public function test_general_payment_data_filter_is_applied() {
		// Set up a chosen payment method in session.
		WC()->session->set( 'chosen_payment_method', 'test_gateway' );

		// Add a filter to modify payment data.
		add_filter( 'woocommerce_fraud_protection_payment_data', function( $payment_data, $chosen_payment_method, $data_collector ) {
			$this->assertEquals( 'test_gateway', $chosen_payment_method );
			$this->assertInstanceOf( SessionDataCollector::class, $data_collector );

			$payment_data['payment_gateway_name'] = 'Test Gateway';
			$payment_data['card_brand']           = 'visa';

			return $payment_data;
		}, 10, 3 );

		$data         = $this->collector->collect();
		$payment_data = $data['payment'];

		$this->assertEquals( 'Test Gateway', $payment_data['payment_gateway_name'] );
		$this->assertEquals( 'visa', $payment_data['card_brand'] );
	}

	/**
	 * Test the gateway-specific payment data filter is applied.
	 */
	public function test_gateway_specific_filter_is_applied() {
		// Set up a chosen payment method in session.
		WC()->session->set( 'chosen_payment_method', 'stripe' );

		// Add a gateway-specific filter.
		add_filter( 'woocommerce_fraud_protection_payment_data_stripe', function( $payment_data, $data_collector ) {
			$this->assertInstanceOf( SessionDataCollector::class, $data_collector );

			$payment_data['payment_gateway_name']      = 'Stripe';
			$payment_data['payment_method_type']       = 'card';
			$payment_data['card_bin']                  = '424242';
			$payment_data['card_last4']                = '4242';
			$payment_data['card_brand']                = 'visa';
			$payment_data['outcome']                   = 'authorized';
			$payment_data['avs_result']                = 'Y';
			$payment_data['cvc_result']                = 'pass';
			$payment_data['tokenized_card_identifier'] = 'pm_abc123';

			return $payment_data;
		}, 10, 2 );

		$data         = $this->collector->collect();
		$payment_data = $data['payment'];

		// Verify all card payment fields are populated.
		$this->assertEquals( 'Stripe', $payment_data['payment_gateway_name'] );
		$this->assertEquals( 'card', $payment_data['payment_method_type'] );
		$this->assertEquals( '424242', $payment_data['card_bin'] );
		$this->assertEquals( '4242', $payment_data['card_last4'] );
		$this->assertEquals( 'visa', $payment_data['card_brand'] );
		$this->assertEquals( 'authorized', $payment_data['outcome'] );
		$this->assertEquals( 'Y', $payment_data['avs_result'] );
		$this->assertEquals( 'pass', $payment_data['cvc_result'] );
		$this->assertEquals( 'pm_abc123', $payment_data['tokenized_card_identifier'] );
	}

	/**
	 * Test getting payment method from POST data (checkout flow).
	 */
	public function test_payment_method_from_post_data() {
		// Ensure session doesn't have a payment method set.
		WC()->session->set( 'chosen_payment_method', null );

		// Simulate POST data from checkout.
		$_POST['payment_method'] = 'stripe';

		add_filter( 'woocommerce_fraud_protection_payment_data_stripe', function( $payment_data ) {
			$payment_data['payment_gateway_name'] = 'Stripe';
			return $payment_data;
		}, 10, 2 );

		$data         = $this->collector->collect();
		$payment_data = $data['payment'];

		$this->assertEquals( 'Stripe', $payment_data['payment_gateway_name'] );

		// Clean up.
		unset( $_POST['payment_method'] );
	}
}
