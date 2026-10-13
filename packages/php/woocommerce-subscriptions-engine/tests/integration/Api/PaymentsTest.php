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
	 * The order argument of each scheduled-payment action call, in order.
	 *
	 * @var array<int, mixed>
	 */
	private $charges = array();

	public function set_up(): void {
		parent::set_up();
		$this->charges = array();
		add_action(
			self::CHARGE_HOOK,
			function ( $order ): void {
				$this->charges[] = $order;
			}
		);
	}

	/**
	 * @testdox gateway_supports follows the gateway's supports(), including the core filter.
	 */
	public function test_gateway_supports_reads_the_gateway_feature(): void {
		$this->assertFalse( Payments::gateway_supports( self::GATEWAY ), 'A core gateway does not support engine charges by default.' );

		$this->support_engine_charges( self::GATEWAY );

		$this->assertTrue( Payments::gateway_supports( self::GATEWAY ) );
		$this->assertFalse( Payments::gateway_supports( 'bacs' ), 'Only the gateway that declared the feature.' );
	}

	/**
	 * @testdox gateway_supports with features needs the engine feature and every listed one.
	 */
	public function test_gateway_supports_checks_the_listed_features_with_the_engine_feature(): void {
		$this->support_feature( self::GATEWAY, 'subscriptions' );

		$this->assertFalse( Payments::gateway_supports( self::GATEWAY, 'subscriptions' ), 'A listed feature without the engine feature is not enough.' );

		$this->support_engine_charges( self::GATEWAY );

		$this->assertTrue( Payments::gateway_supports( self::GATEWAY, 'subscriptions' ) );
		$this->assertTrue( Payments::gateway_supports( self::GATEWAY, array( 'subscriptions' ) ), 'A list works like a single feature.' );
		$this->assertTrue( Payments::gateway_supports( self::GATEWAY, array() ), 'No listed feature checks the engine feature alone.' );
		$this->assertFalse( Payments::gateway_supports( self::GATEWAY, array( 'subscriptions', 'subscription_payment_method_change' ) ), 'Every listed feature is needed.' );
		$this->assertFalse( Payments::gateway_supports( 'bacs', 'subscriptions' ) );
	}

	/**
	 * @testdox gateway_supports is false for an empty or unknown gateway id.
	 */
	public function test_gateway_supports_is_false_for_unknown_ids(): void {
		$this->support_engine_charges( 'no-such-gateway' );

		$this->assertFalse( Payments::gateway_supports( '' ) );
		$this->assertFalse( Payments::gateway_supports( 'no-such-gateway' ) );
	}

	/**
	 * @testdox process_scheduled_payment fires the gateway's scheduled-payment action once with the order.
	 */
	public function test_process_scheduled_payment_fires_once_for_a_supported_gateway(): void {
		$this->support_engine_charges( self::GATEWAY );
		$order = $this->make_order();

		$this->assertTrue( Payments::process_scheduled_payment( $order ) );

		$this->assertSame( array( $order ), $this->charges );
	}

	/**
	 * @testdox process_scheduled_payment leaves whether the order should be paid to the caller.
	 */
	public function test_process_scheduled_payment_fires_whatever_the_order_state(): void {
		$this->support_engine_charges( self::GATEWAY );
		$paid = $this->make_order( self::GATEWAY, '12.50', 'processing' );
		$free = $this->make_order( self::GATEWAY, '0' );

		$this->assertTrue( Payments::process_scheduled_payment( $paid ) );
		$this->assertTrue( Payments::process_scheduled_payment( $free ) );

		$this->assertSame( array( $paid, $free ), $this->charges );
	}

	/**
	 * @testdox process_scheduled_payment does not fire when the order's gateway does not support the engine.
	 *
	 * @dataProvider provide_unsupported_payment_methods
	 *
	 * @param string $method Order payment method.
	 */
	public function test_process_scheduled_payment_does_not_fire_without_engine_support( string $method ): void {
		$order = $this->make_order( $method );

		$this->assertFalse( Payments::process_scheduled_payment( $order ) );
		$this->assertSame( array(), $this->charges );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function provide_unsupported_payment_methods(): array {
		return array(
			'unsupported gateway' => array( self::GATEWAY ),
			'no payment method'   => array( '' ),
		);
	}

	/**
	 * @testdox A throwing gateway callback is logged with the order id and process_scheduled_payment returns false.
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
				$this->assertFalse( Payments::process_scheduled_payment( $order ), 'A gateway that threw is not a payment in flight.' );
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
