<?php
/**
 * Integration tests for the public Payments broker.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use RuntimeException;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Payments;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Payments
 */
class PaymentsTest extends EngineIntegrationTestCase {

	private const GATEWAY = 'cod';

	private const CHARGE_HOOK = 'woocommerce_subscriptions_engine_scheduled_payment_cod';

	private const REUSABLE_FILTER = 'woocommerce_subscriptions_engine_requires_reusable_payment_method';

	/**
	 * Arguments of each charge action call, in order.
	 *
	 * @var array<int, array{amount: mixed, order: mixed}>
	 */
	private $charges = array();

	public function set_up(): void {
		parent::set_up();
		$this->charges = array();
		add_action(
			self::CHARGE_HOOK,
			function ( $amount, $order ): void {
				$this->charges[] = array(
					'amount' => $amount,
					'order'  => $order,
				);
			},
			10,
			2
		);
	}

	/**
	 * @testdox supports_gateway follows the gateway's supports(), including the core filter.
	 */
	public function test_supports_gateway_reads_the_gateway_feature(): void {
		$this->assertFalse( Payments::supports_gateway( self::GATEWAY ), 'A core gateway does not support engine charges by default.' );

		$this->support_engine_charges( self::GATEWAY );

		$this->assertTrue( Payments::supports_gateway( self::GATEWAY ) );
		$this->assertFalse( Payments::supports_gateway( 'bacs' ), 'Only the gateway that declared the feature.' );
	}

	/**
	 * @testdox supports_gateway with features needs the engine feature and every listed one.
	 */
	public function test_supports_gateway_checks_the_listed_features_with_the_engine_feature(): void {
		$this->support_feature( self::GATEWAY, 'subscriptions' );

		$this->assertFalse( Payments::supports_gateway( self::GATEWAY, 'subscriptions' ), 'A listed feature without the engine feature is not enough.' );

		$this->support_engine_charges( self::GATEWAY );

		$this->assertTrue( Payments::supports_gateway( self::GATEWAY, 'subscriptions' ) );
		$this->assertFalse( Payments::supports_gateway( self::GATEWAY, 'subscriptions', 'subscription_payment_method_change' ), 'Every listed feature is needed.' );
		$this->assertFalse( Payments::supports_gateway( 'bacs', 'subscriptions' ) );
	}

	/**
	 * @testdox supports_gateway is false for an empty or unknown gateway id.
	 */
	public function test_supports_gateway_is_false_for_unknown_ids(): void {
		$this->support_engine_charges( 'no-such-gateway' );

		$this->assertFalse( Payments::supports_gateway( '' ) );
		$this->assertFalse( Payments::supports_gateway( 'no-such-gateway' ) );
	}

	/**
	 * @testdox charge fires the gateway's scheduled-payment action once with the amount and order.
	 */
	public function test_charge_fires_once_for_a_supported_gateway(): void {
		$this->support_engine_charges( self::GATEWAY );
		$order = $this->make_order();

		$this->assertTrue( Payments::charge( $order ) );

		$this->assertCount( 1, $this->charges );
		$this->assertSame( 12.5, $this->charges[0]['amount'] );
		$this->assertSame( $order, $this->charges[0]['order'] );
	}

	/**
	 * @testdox charge does not fire when a precondition fails.
	 *
	 * @dataProvider provide_uncharged_orders
	 *
	 * @param bool   $supported Whether the gateway supports engine charges.
	 * @param string $method    Order payment method.
	 * @param string $total     Order total.
	 * @param string $status    Order status.
	 */
	public function test_charge_does_not_fire_when_a_precondition_fails( bool $supported, string $method, string $total, string $status ): void {
		if ( $supported ) {
			$this->support_engine_charges( self::GATEWAY );
		}
		$order = $this->make_order( $method, $total, $status );

		$this->assertFalse( Payments::charge( $order ) );
		$this->assertSame( array(), $this->charges );
	}

	/**
	 * @return array<string, array{0: bool, 1: string, 2: string, 3: string}>
	 */
	public function provide_uncharged_orders(): array {
		return array(
			'unsupported gateway' => array( false, self::GATEWAY, '12.50', 'pending' ),
			'zero total'          => array( true, self::GATEWAY, '0', 'pending' ), // Core needs_payment() is false.
			'paid order'          => array( true, self::GATEWAY, '12.50', 'processing' ),
			'no payment method'   => array( true, '', '12.50', 'pending' ),
		);
	}

	/**
	 * @testdox charge fires for a $0 order an extension filtered to need payment, with amount 0.
	 */
	public function test_charge_fires_for_a_zero_order_that_needs_payment(): void {
		$this->support_engine_charges( self::GATEWAY );
		$order = $this->make_order( self::GATEWAY, '0' );
		add_filter( 'woocommerce_order_needs_payment', '__return_true' );

		$this->assertTrue( Payments::charge( $order ) );

		$this->assertCount( 1, $this->charges );
		$this->assertSame( 0.0, $this->charges[0]['amount'] );
	}

	/**
	 * @testdox A throwing gateway callback is logged with the order id and charge returns false.
	 */
	public function test_a_throwing_gateway_is_logged(): void {
		$this->support_engine_charges( self::GATEWAY );
		add_action(
			self::CHARGE_HOOK,
			static function (): void {
				throw new RuntimeException( 'Gateway failure.' );
			}
		);
		$order = $this->make_order();

		$errors = $this->capture_engine_log(
			'error',
			array( 'order_id' => $order->get_id() ),
			function () use ( $order ): void {
				$this->assertFalse( Payments::charge( $order ), 'A gateway that threw is not a charge in flight.' );
			}
		);

		$this->assertNotSame( array(), $errors );
	}

	/**
	 * @testdox requires_reusable_payment_method is false by default and casts the filter answer to bool.
	 */
	public function test_requires_reusable_payment_method_answers(): void {
		$this->assertFalse( Payments::requires_reusable_payment_method() );

		add_filter( self::REUSABLE_FILTER, '__return_true' );
		$this->assertTrue( Payments::requires_reusable_payment_method() );
		remove_filter( self::REUSABLE_FILTER, '__return_true' );

		$answer = 'yes';
		$filter = static function () use ( &$answer ) {
			return $answer;
		};
		add_filter( self::REUSABLE_FILTER, $filter );
		$this->assertTrue( Payments::requires_reusable_payment_method(), 'A truthy answer counts as true.' );

		$answer = 0;
		$this->assertFalse( Payments::requires_reusable_payment_method(), 'A falsy answer counts as false.' );
	}

	/**
	 * @testdox requires_reusable_payment_method passes the order (or null for the cart) to the filter.
	 */
	public function test_requires_reusable_payment_method_passes_the_order(): void {
		$seen = array();
		add_filter(
			self::REUSABLE_FILTER,
			static function ( $requires, $order ) use ( &$seen ) {
				$seen[] = $order;
				return $requires;
			},
			10,
			2
		);
		$order = $this->make_order();

		Payments::requires_reusable_payment_method( $order );
		Payments::requires_reusable_payment_method();

		$this->assertSame( array( $order, null ), $seen );
	}

	/**
	 * Declare the engine feature for a gateway through core's supports filter.
	 *
	 * @param string $gateway_id Gateway id.
	 */
	private function support_engine_charges( string $gateway_id ): void {
		$this->support_feature( $gateway_id, Payments::GATEWAY_FEATURE );
	}

	/**
	 * Declare a gateway feature through core's supports filter.
	 *
	 * @param string $gateway_id Gateway id.
	 * @param string $supported  Feature.
	 */
	private function support_feature( string $gateway_id, string $supported ): void {
		add_filter(
			'woocommerce_payment_gateway_supports',
			static function ( $supports, $feature, $gateway ) use ( $gateway_id, $supported ) {
				if ( $supported === $feature && is_object( $gateway ) && isset( $gateway->id ) && $gateway_id === $gateway->id ) {
					return true;
				}
				return $supports;
			},
			10,
			3
		);
	}

	/**
	 * Create a saved order.
	 *
	 * @param string $method Payment method.
	 * @param string $total  Total.
	 * @param string $status Status.
	 */
	private function make_order( string $method = self::GATEWAY, string $total = '12.50', string $status = 'pending' ): WC_Order {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( $method );
		$order->set_total( $total );
		$order->set_status( $status );
		$order->save();

		return $order;
	}
}
