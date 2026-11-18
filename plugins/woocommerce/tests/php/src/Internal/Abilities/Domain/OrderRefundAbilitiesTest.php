<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the order-refund-create and order-refunds-query abilities.
 */
class OrderRefundAbilitiesTest extends WC_Unit_Test_Case {

	/**
	 * Hooks of a refund, in the order they fire.
	 */
	private const REFUND_HOOKS = array(
		'woocommerce_create_refund',
		'woocommerce_order_partially_refunded',
		'woocommerce_order_fully_refunded',
		'woocommerce_refund_created',
		'woocommerce_order_refunded',
	);

	/**
	 * Gateway that can refund, and records its refunds.
	 *
	 * @var \WC_Payment_Gateway
	 */
	private $gateway;

	/**
	 * Gateways registered before the test.
	 *
	 * @var array
	 */
	private $original_gateways;

	/**
	 * Hooks fired during the test, by name.
	 *
	 * @var string[]
	 */
	private $fired = array();

	/**
	 * A shop manager that every test acts as.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'shop_manager' ) );
	}

	/**
	 * Log in as a shop manager and register a gateway that supports refunds.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$user_id );

		$this->gateway = new class() extends \WC_Payment_Gateway {
			/**
			 * Result that process_refund() returns.
			 *
			 * @var bool|\WP_Error
			 */
			public $refund_result = true;

			/**
			 * Amounts that process_refund() was called with.
			 *
			 * @var array
			 */
			public $refunded = array();

			/**
			 * Order IDs that process_refund() was called with.
			 *
			 * @var int[]
			 */
			public $refunded_orders = array();

			/**
			 * Set the ID and the refund support.
			 */
			public function __construct() {
				$this->id       = 'refunding_gateway';
				$this->enabled  = 'yes';
				$this->supports = array( PaymentGatewayFeature::PRODUCTS, PaymentGatewayFeature::REFUNDS );
			}

			/**
			 * Record the refund.
			 *
			 * @param int        $order_id Order ID.
			 * @param float|null $amount   Amount.
			 * @param string     $reason   Reason.
			 * @return bool|\WP_Error
			 */
			public function process_refund( $order_id, $amount = null, $reason = '' ) {
				$this->refunded[]        = (string) $amount;
				$this->refunded_orders[] = (int) $order_id;
				return $this->refund_result;
			}
		};

		$gateways                                        = WC()->payment_gateways();
		$this->original_gateways                         = $gateways->payment_gateways;
		$gateways->payment_gateways['refunding_gateway'] = $this->gateway;

		foreach ( self::REFUND_HOOKS as $hook ) {
			add_action(
				$hook,
				function () use ( $hook ) {
					$this->fired[] = $hook;
				}
			);
		}
	}

	/**
	 * Restore the registered gateways.
	 */
	public function tearDown(): void {
		WC()->payment_gateways()->payment_gateways = $this->original_gateways;
		parent::tearDown();
	}

	/**
	 * @testdox Should record a manual partial refund without calling the gateway.
	 */
	public function test_records_manual_partial_refund(): void {
		$order = $this->create_order();

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '10',
				'reason' => 'Damaged box',
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( '10', $result['refund']['amount'] );
		$this->assertSame( 'Damaged box', $result['refund']['reason'] );
		$this->assertFalse( $result['refund']['refunded_payment'] );
		$this->assertSame( array(), $this->gateway->refunded, 'A manual refund should not reach the gateway.' );

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $order->get_refunds() );
		$this->assertSame( $result['refund']['id'], $order->get_refunds()[0]->get_id() );
		$this->assertSame( OrderStatus::PROCESSING, $order->get_status() );
	}

	/**
	 * @testdox Should refund a line through the gateway and mark the refund as paid back.
	 */
	public function test_refunds_line_through_gateway(): void {
		$order   = $this->create_order();
		$line_id = $this->get_product_line_id( $order );

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'line_items'     => array(
					array(
						'line_item_id' => $line_id,
						'quantity'     => 1,
					),
				),
				'refund_payment' => true,
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( array( 10.0 ), array_map( 'floatval', $this->gateway->refunded ) );
		$this->assertTrue( $result['refund']['refunded_payment'] );
		$this->assertSame( $line_id, $result['refund']['line_items'][0]['refunded_item_id'] );
		$this->assertSame( 1.0, (float) $result['refund']['line_items'][0]['quantity'] );
		$this->assertTrue( wc_get_order( $result['refund']['id'] )->get_refunded_payment() );
	}

	/**
	 * @testdox Should report a refunded credit line with a negative total.
	 */
	public function test_reports_credit_line_as_negative(): void {
		$order  = $this->create_order();
		$credit = new \WC_Order_Item_Fee();
		$credit->set_name( 'Store credit' );
		$credit->set_total( '-5' );
		$order->add_item( $credit );
		$order->calculate_totals( false );
		$order->save();

		$result = $this->refund(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $this->get_product_line_id( $order ),
						'quantity'     => 1,
					),
					array(
						'line_item_id' => $credit->get_id(),
						'refund_total' => -5,
					),
				),
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$lines = array_column( $result['refund']['line_items'], 'total', 'refunded_item_id' );
		$this->assertSame( -5.0, (float) $lines[ $credit->get_id() ] );
		$this->assertSame( 10.0, (float) $lines[ $this->get_product_line_id( $order ) ] );
	}

	/**
	 * @testdox Should pass woocommerce_create_refund callbacks a refund that already has its ID, as wc_create_refund() does.
	 */
	public function test_create_refund_hook_gets_refund_id(): void {
		$order   = $this->create_order_without_lines();
		$hook_id = null;
		add_action(
			'woocommerce_create_refund',
			static function ( $refund ) use ( &$hook_id ) {
				$hook_id = $refund->get_id();
			}
		);

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '5',
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( $result['refund']['id'], $hook_id );
	}

	/**
	 * @testdox Should return the message of an extension that declines the refund by throwing, and save nothing.
	 */
	public function test_returns_declined_refund_message(): void {
		$order    = $this->create_order_without_lines();
		$declined = null;
		add_action(
			'woocommerce_create_refund',
			static function ( $refund ) use ( &$declined ) {
				$declined = $refund;
				throw new \Exception( 'Team seats are still in use.' );
			}
		);

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '5',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_declined', $result->get_error_code() );
		$this->assertSame( 'Team seats are still in use.', $result->get_error_message() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
		$this->assertInstanceOf( \WC_Order_Refund::class, $declined );
		$this->assertSame( 0, $declined->get_id(), 'The refund should be deleted, not only rolled back, for tables without transactions.' );
	}

	/**
	 * @testdox Should refuse a repeat of a refund it just made, and return that refund, so a retried call cannot refund twice.
	 */
	public function test_refuses_repeated_refund(): void {
		$order = $this->create_order_without_lines();
		$input = array(
			'id'             => $order->get_id(),
			'amount'         => '10',
			'refund_payment' => true,
		);
		$first = $this->refund( $input );

		$second = $this->refund( $input );

		$this->assertIsArray( $first, is_wp_error( $first ) ? $first->get_error_message() : '' );
		$this->assertWPError( $second );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $second->get_error_code() );
		$this->assertSame( 409, $second->get_error_data()['status'] );
		$this->assertSame( $first['refund']['id'], $second->get_error_data()['refund']['id'] );
		$this->assertSame( array( '10' ), $this->gateway->refunded );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse a retry that lists the same lines in another order as a repeat of the refund.
	 */
	public function test_refuses_repeated_refund_with_lines_reordered(): void {
		$order    = $this->create_order();
		$product  = array(
			'line_item_id' => $this->get_product_line_id( $order ),
			'quantity'     => 1,
		);
		$shipping = array(
			'line_item_id' => array_key_first( $order->get_items( 'shipping' ) ),
			'refund_total' => 5,
		);
		$first    = $this->refund(
			array(
				'id'         => $order->get_id(),
				'line_items' => array( $product, $shipping ),
			)
		);

		$second = $this->refund(
			array(
				'id'         => $order->get_id(),
				'line_items' => array( $shipping, $product ),
			)
		);

		$this->assertIsArray( $first, is_wp_error( $first ) ? $first->get_error_message() : '' );
		$this->assertWPError( $second );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $second->get_error_code() );
		$this->assertSame( $first['refund']['id'], $second->get_error_data()['refund']['id'] );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse a retry of a full refund as a repeat with that refund, not as an order that cannot be refunded.
	 */
	public function test_refuses_repeated_full_refund_as_duplicate(): void {
		$order = $this->create_order_without_lines();
		$input = array(
			'id'     => $order->get_id(),
			'amount' => '50',
		);
		$first = $this->refund( $input );

		$second = $this->refund( $input );

		$this->assertIsArray( $first, is_wp_error( $first ) ? $first->get_error_message() : '' );
		$this->assertWPError( $second );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $second->get_error_code() );
		$this->assertSame( 409, $second->get_error_data()['status'] );
		$this->assertSame( $first['refund']['id'], $second->get_error_data()['refund']['id'] );
	}

	/**
	 * @testdox Should allow a different refund of the same order right after another one.
	 */
	public function test_allows_different_refund_right_after(): void {
		$order = $this->create_order_without_lines();
		$this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '10',
			)
		);

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '5',
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertCount( 2, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should allow the same refund again once the duplicate window has passed.
	 */
	public function test_allows_same_refund_after_window(): void {
		$order = $this->create_order_without_lines();
		$input = array(
			'id'     => $order->get_id(),
			'amount' => '10',
		);
		$first = $this->refund( $input );
		$this->assertIsArray( $first, is_wp_error( $first ) ? $first->get_error_message() : '' );
		$refund = wc_get_order( $first['refund']['id'] );
		$refund->set_date_created( time() - 11 * MINUTE_IN_SECONDS );
		$refund->save();

		$result = $this->refund( $input );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertCount( 2, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should allow a retry after the gateway refused, since no refund was made.
	 */
	public function test_allows_retry_after_gateway_refusal(): void {
		$order                        = $this->create_order_without_lines();
		$input                        = array(
			'id'             => $order->get_id(),
			'amount'         => '10',
			'refund_payment' => true,
		);
		$this->gateway->refund_result = new \WP_Error( 'declined', 'Card issuer declined the refund.' );
		$refused                      = $this->refund( $input );

		$this->assertWPError( $refused );
		$this->assertSame( 'woocommerce_ability_order_refund_gateway_failed', $refused->get_error_code() );
		$this->assertCount( 1, $this->gateway->refunded );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );

		$this->gateway->refund_result = true;
		$result                       = $this->refund( $input );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertCount( 2, $this->gateway->refunded );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse a refund while another refund of the order holds its lock.
	 */
	public function test_refuses_refund_while_order_is_locked(): void {
		$order = $this->create_order_without_lines();
		add_option( 'woocommerce_ability_order_refund_lock_' . $order->get_id(), time() + MINUTE_IN_SECONDS, '', false );

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '10',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_in_progress', $result->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should release the lock of the order after a refund, so the next refund can run.
	 */
	public function test_releases_order_lock(): void {
		$order = $this->create_order_without_lines();

		$this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '10',
			)
		);

		wp_cache_delete( 'notoptions', 'options' );
		$this->assertFalse( get_option( 'woocommerce_ability_order_refund_lock_' . $order->get_id() ) );
	}

	/**
	 * @testdox Should save nothing when another call took over the lock of the order while the refund was checked.
	 */
	public function test_refuses_refund_when_lock_was_taken_over(): void {
		global $wpdb;

		$order     = $this->create_order_without_lines();
		$lock_name = 'woocommerce_ability_order_refund_lock_' . $order->get_id();
		$taken     = (string) ( time() + HOUR_IN_SECONDS );
		$hook      = null;
		$hook      = static function ( $results ) use ( $wpdb, $lock_name, $taken, &$hook ) {
			remove_filter( 'woocommerce_order_query', $hook );
			$wpdb->update( $wpdb->options, array( 'option_value' => $taken ), array( 'option_name' => $lock_name ) );
			return $results;
		};
		add_filter( 'woocommerce_order_query', $hook );

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_lock_lost', $result->get_error_code() );
		$this->assertSame( 409, $result->get_error_data()['status'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
		$this->assertSame( array(), $this->gateway->refunded );
		$this->assertSame( $taken, $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $lock_name ) ), 'The lock of the other call should be kept.' );
	}

	/**
	 * @testdox Should refuse a refund inside a transaction that the caller started, before the gateway is called.
	 */
	public function test_refuses_refund_inside_caller_transaction(): void {
		$order = $this->create_order_without_lines();

		DatabaseTransaction::track_caller( 'start' );
		try {
			$result = $this->refund(
				array(
					'id'             => $order->get_id(),
					'amount'         => '10',
					'refund_payment' => true,
				)
			);
		} finally {
			DatabaseTransaction::track_caller( 'commit' );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_in_transaction', $result->get_error_code() );
		$this->assertSame( array(), $this->gateway->refunded );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse a refund that a woocommerce_create_refund callback starts inside the transaction of another refund, and finish the outer refund.
	 */
	public function test_refuses_refund_from_create_refund_hook(): void {
		$order   = $this->create_order_without_lines();
		$other   = $this->create_order_without_lines();
		$started = false;
		$nested  = null;
		add_action(
			'woocommerce_create_refund',
			function () use ( $other, &$started, &$nested ) {
				if ( ! $started ) {
					$started = true;
					$nested  = $this->refund(
						array(
							'id'     => $other->get_id(),
							'amount' => '5',
						)
					);
				}
			}
		);

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $nested );
		$this->assertSame( 'woocommerce_ability_order_refund_in_transaction', $nested->get_error_code() );
		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( '10', $result['refund']['amount'] );
		$this->assertSame( $order->get_id(), $result['refund']['order_id'] );
		$this->assertSame( array( '10' ), $this->gateway->refunded );
		$this->assertCount( 0, wc_get_order( $other->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should finish the outer refund against its own order when a woocommerce_create_refund callback refunds another order without transactions.
	 */
	public function test_nested_refund_keeps_outer_refund_state(): void {
		Constants::set_constant( 'WC_USE_TRANSACTIONS', false );
		$order   = $this->create_order_without_lines();
		$other   = $this->create_order_without_lines();
		$started = false;
		$nested  = null;
		add_action(
			'woocommerce_create_refund',
			function () use ( $other, &$started, &$nested ) {
				if ( ! $started ) {
					$started = true;
					$nested  = $this->refund(
						array(
							'id'             => $other->get_id(),
							'amount'         => '5',
							'refund_payment' => true,
						)
					);
				}
			}
		);

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertIsArray( $nested, is_wp_error( $nested ) ? $nested->get_error_message() : '' );
		$this->assertSame( $other->get_id(), $nested['refund']['order_id'] );
		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( '10', $result['refund']['amount'] );
		$this->assertSame( $order->get_id(), $result['refund']['order_id'] );
		$this->assertSame( array( '5', '10' ), $this->gateway->refunded, 'Each order should reach the gateway once, with its own amount.' );
		$this->assertSame( array( $other->get_id(), $order->get_id() ), $this->gateway->refunded_orders );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertCount( 1, $refunds );
		$this->assertSame( 10.0, (float) $refunds[0]->get_amount() );
		$this->assertCount( 1, wc_get_order( $other->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should keep the refund and say not to refund again when a hook fails after the gateway refunded.
	 */
	public function test_keeps_refund_when_a_later_step_fails(): void {
		$order = $this->create_order_without_lines();
		$input = array(
			'id'             => $order->get_id(),
			'amount'         => '10',
			'refund_payment' => true,
		);
		add_action(
			'woocommerce_order_partially_refunded',
			static function () {
				throw new \Exception( 'Loyalty points service is down.' );
			}
		);

		$result = $this->refund( $input );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_incomplete', $result->get_error_code() );
		$this->assertStringContainsString( 'Loyalty points service is down.', $result->get_error_message() );
		$this->assertTrue( $result->get_error_data()['refunded_payment'] );
		$refund = wc_get_order( $result->get_error_data()['refund_id'] );
		$this->assertInstanceOf( \WC_Order_Refund::class, $refund );
		$this->assertTrue( $refund->get_refunded_payment() );
		$this->assertSame( array( '10' ), $this->gateway->refunded );

		$retry = $this->refund( $input );
		$this->assertWPError( $retry );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $retry->get_error_code() );
		$this->assertSame( array( '10' ), $this->gateway->refunded );
	}

	/**
	 * @testdox Should keep the refund and not call the gateway when the final save fails after woocommerce_create_refund ran.
	 */
	public function test_keeps_refund_when_the_save_after_the_create_hook_fails(): void {
		$order     = $this->create_order_without_lines();
		$hook_ran  = false;
		$completed = 0;
		add_action(
			'woocommerce_create_refund',
			static function () use ( &$hook_ran ) {
				$hook_ran = true;
			}
		);
		add_action(
			'woocommerce_before_order_refund_object_save',
			static function () use ( &$hook_ran ) {
				if ( $hook_ran ) {
					throw new \Exception( 'Refund table is read-only.' );
				}
			}
		);
		add_action(
			'woocommerce_order_refunded',
			static function () use ( &$completed ) {
				++$completed;
			}
		);

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_incomplete', $result->get_error_code() );
		$this->assertFalse( $result->get_error_data()['refunded_payment'] );
		$this->assertInstanceOf( \WC_Order_Refund::class, wc_get_order( $result->get_error_data()['refund_id'] ) );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
		$this->assertSame( array(), $this->gateway->refunded );
		$this->assertSame( 1, $completed );
		$this->assertStringContainsString( 'not asked to refund the payment', $result->get_error_message() );
	}

	/**
	 * @testdox Should refuse a retry of a refund kept without its payment, even after the duplicate window.
	 */
	public function test_refuses_retry_of_unpaid_refund_after_window(): void {
		$order    = $this->create_order_without_lines();
		$input    = array(
			'id'             => $order->get_id(),
			'amount'         => '10',
			'refund_payment' => true,
		);
		$hook_ran = false;
		$fail     = true;
		add_action(
			'woocommerce_create_refund',
			static function () use ( &$hook_ran ) {
				$hook_ran = true;
			}
		);
		add_action(
			'woocommerce_before_order_refund_object_save',
			static function () use ( &$hook_ran, &$fail ) {
				if ( $fail && $hook_ran ) {
					throw new \Exception( 'Refund table is read-only.' );
				}
			}
		);
		$kept = $this->refund( $input );
		$this->assertWPError( $kept );
		$fail   = false;
		$refund = wc_get_order( $kept->get_error_data()['refund_id'] );
		$refund->set_date_created( time() - 11 * MINUTE_IN_SECONDS );
		$refund->save();

		$retry = $this->refund( $input );

		$this->assertWPError( $retry );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $retry->get_error_code() );
		$this->assertStringContainsString( 'not asked to refund the payment', $retry->get_error_message() );
		$this->assertSame( array(), $this->gateway->refunded );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse the same refund sent again with its line total filled in, as a repeat of the refund.
	 */
	public function test_refuses_same_refund_sent_in_another_form(): void {
		$order   = $this->create_order();
		$line_id = $this->get_product_line_id( $order );
		$first   = $this->refund(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $line_id,
						'quantity'     => 1,
					),
				),
			)
		);
		$this->assertIsArray( $first, is_wp_error( $first ) ? $first->get_error_message() : '' );

		$second = $this->refund(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $line_id,
						'quantity'     => 1,
						'refund_total' => (float) $first['refund']['amount'],
					),
				),
			)
		);

		$this->assertWPError( $second );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $second->get_error_code() );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should save no refund and not fire woocommerce_create_refund when a refund line fails to save.
	 */
	public function test_refuses_refund_whose_line_fails_to_save(): void {
		$order   = $this->create_order();
		$line_id = $this->get_product_line_id( $order );
		add_action(
			'woocommerce_before_order_item_object_save',
			static function () {
				throw new \Exception( 'Order item table is read-only.' );
			}
		);

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'line_items'     => array(
					array(
						'line_item_id' => $line_id,
						'quantity'     => 1,
					),
				),
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertNotContains( 'woocommerce_create_refund', $this->fired );
		$this->assertSame( array(), $this->gateway->refunded );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should round the amount to the store decimals before the gateway gets it.
	 */
	public function test_rounds_amount_to_store_decimals(): void {
		$order = $this->create_order_without_lines();

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10.004',
				'refund_payment' => true,
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( array( '10' ), $this->gateway->refunded );
	}

	/**
	 * @testdox Should refuse to refund more than the order can still refund, and save nothing.
	 */
	public function test_refuses_more_than_refundable(): void {
		$order = $this->create_order_without_lines();

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '60',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_exceeds_remaining', $result->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
		$this->assertSame( array(), $this->fired );
	}

	/**
	 * @testdox Should refuse a gateway refund when the gateway of the order cannot refund.
	 */
	public function test_refuses_gateway_refund_without_support(): void {
		$order = $this->create_order_without_lines( 'bacs' );

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '5',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_gateway_unsupported', $result->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should delete the refund and fail when the gateway refuses.
	 */
	public function test_gateway_refusal_leaves_no_refund(): void {
		$order                        = $this->create_order_without_lines();
		$this->gateway->refund_result = new \WP_Error( 'declined', 'Card issuer declined the refund.' );

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_gateway_failed', $result->get_error_code() );
		$this->assertStringContainsString( 'Card issuer declined the refund.', $result->get_error_message() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
		$this->assertNotContains( 'woocommerce_order_refunded', $this->fired );
	}

	/**
	 * @testdox Should mark the order refunded when the refund covers the rest of it.
	 */
	public function test_full_refund_marks_order_refunded(): void {
		$order = $this->create_order();

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '50',
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( OrderStatus::REFUNDED, wc_get_order( $order->get_id() )->get_status() );
		$this->assertContains( 'woocommerce_order_fully_refunded', $this->fired );
	}

	/**
	 * @testdox Should return the refunded quantity to stock when asked.
	 */
	public function test_restocks_refunded_quantity(): void {
		$order   = $this->create_order();
		$product = wc_get_product( $order->get_items()[ $this->get_product_line_id( $order ) ]->get_product_id() );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();
		wc_reduce_stock_levels( $order->get_id() );

		$result = $this->refund(
			array(
				'id'            => $order->get_id(),
				'line_items'    => array(
					array(
						'line_item_id' => $this->get_product_line_id( $order ),
						'quantity'     => 2,
					),
				),
				'restock_items' => true,
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( 8, wc_get_product( $product->get_id() )->get_stock_quantity(), 'Four units were sold from 10, and two came back.' );
	}

	/**
	 * @testdox Should create the same refund and fire the same hooks as wc_create_refund().
	 */
	public function test_matches_wc_create_refund(): void {
		$args = array();
		foreach ( array( 'woocommerce_create_refund', 'woocommerce_refund_created' ) as $hook ) {
			add_action(
				$hook,
				static function ( $refund, $hook_args ) use ( $hook, &$args ) {
					unset( $refund ); // Avoid parameter not used PHPCS errors.
					$args[ $hook ][] = $hook_args;
				},
				10,
				2
			);
		}

		$legacy_order = $this->create_order();
		$legacy       = wc_create_refund(
			array(
				'order_id'   => $legacy_order->get_id(),
				'amount'     => 10,
				'reason'     => 'Parity',
				'line_items' => wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils::class )->prepare_refund_creation_or_error(
					$legacy_order,
					array(
						array(
							'line_item_id' => $this->get_product_line_id( $legacy_order ),
							'quantity'     => 1,
						),
					),
					false,
					null,
					'test'
				)['line_items'],
			)
		);
		$legacy_hooks = $this->fired;
		$this->fired  = array();

		$order  = $this->create_order();
		$result = $this->refund(
			array(
				'id'         => $order->get_id(),
				'reason'     => 'Parity',
				'line_items' => array(
					array(
						'line_item_id' => $this->get_product_line_id( $order ),
						'quantity'     => 1,
					),
				),
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( $legacy_hooks, $this->fired, 'The hooks should fire in the same order.' );
		foreach ( $args as $hook => $calls ) {
			$this->assertCount( 2, $calls, $hook );
			$this->assertSame( array_keys( $calls[0] ), array_keys( $calls[1] ), "$hook should get the arguments of wc_create_refund()." );
			$this->assertSame( $legacy_order->get_id(), $calls[0]['order_id'], $hook );
			$this->assertSame( $order->get_id(), $calls[1]['order_id'], $hook );
			$this->assertSame( $calls[0]['refund_id'], $calls[1]['refund_id'], $hook );
		}

		$this->assertInstanceOf( \WC_Order_Refund::class, $legacy );
		$legacy = wc_get_order( $legacy->get_id() );
		$refund = wc_get_order( $result['refund']['id'] );
		$this->assertInstanceOf( \WC_Order_Refund::class, $legacy );
		$this->assertInstanceOf( \WC_Order_Refund::class, $refund );
		$this->assertSame( $legacy->get_amount(), $refund->get_amount() );
		$this->assertSame( $legacy->get_total(), $refund->get_total() );
		$this->assertSame( $legacy->get_total_tax(), $refund->get_total_tax() );
		$this->assertSame(
			$this->describe_lines( $legacy, $legacy_order ),
			$this->describe_lines( $refund, $order )
		);
		$this->assertSame( wc_get_order( $legacy_order->get_id() )->get_status(), wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should list the refunds of an order newest first, and only those in include when it is sent.
	 */
	public function test_query_lists_refunds(): void {
		$order  = $this->create_order_without_lines();
		$first  = wc_create_refund(
			array(
				'order_id'     => $order->get_id(),
				'amount'       => 5,
				'date_created' => '2026-01-01 10:00:00',
			)
		);
		$second = wc_create_refund(
			array(
				'order_id'     => $order->get_id(),
				'amount'       => 7,
				'reason'       => 'Late delivery',
				'date_created' => '2026-01-02 10:00:00',
			)
		);
		$query  = wp_get_ability( 'woocommerce/order-refunds-query' );

		$all      = $query->execute( array( 'id' => $order->get_id() ) );
		$included = $query->execute(
			array(
				'id'      => $order->get_id(),
				'include' => array( $first->get_id() ),
			)
		);

		$this->assertIsArray( $all, is_wp_error( $all ) ? $all->get_error_message() : '' );
		$this->assertSame( 2, $all['total'] );
		$this->assertSame( array( $second->get_id(), $first->get_id() ), array_column( $all['refunds'], 'id' ) );
		$this->assertSame( 'Late delivery', $all['refunds'][0]['reason'] );
		$this->assertArrayNotHasKey( 'reason', $all['refunds'][1], 'A refund without a reason should omit the field.' );
		$this->assertSame( array( $first->get_id() ), array_column( $included['refunds'], 'id' ) );
		$this->assertSame( 1, $included['total'] );
	}

	/**
	 * Run the refund ability.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	private function refund( array $input ) {
		return wp_get_ability( 'woocommerce/order-refund-create' )->execute( $input );
	}

	/**
	 * Create a processing order of 4 units at 10 plus 10 shipping, a total of 50.
	 *
	 * @param string $payment_method Payment gateway ID.
	 * @return \WC_Order
	 */
	private function create_order( string $payment_method = 'refunding_gateway' ): \WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_payment_method( $payment_method );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		return $order;
	}

	/**
	 * Create a processing order with a total of 50 and no lines, for refunds of an amount only.
	 *
	 * @param string $payment_method Payment gateway ID.
	 * @return \WC_Order
	 */
	private function create_order_without_lines( string $payment_method = 'refunding_gateway' ): \WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( $payment_method );
		$order->set_total( '50' );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		return $order;
	}

	/**
	 * Get the ID of the product line of an order from create_order().
	 *
	 * @param \WC_Order $order Order.
	 * @return int
	 */
	private function get_product_line_id( \WC_Order $order ): int {
		return array_key_first( $order->get_items() );
	}

	/**
	 * Describe the lines of a refund by what both refund paths must agree on. The refunded line is named by
	 * its position in the order, since the two refunds belong to different orders.
	 *
	 * @param \WC_Order_Refund $refund Refund, loaded from the database.
	 * @param \WC_Order        $order  Order the refund belongs to.
	 * @return array
	 */
	private function describe_lines( \WC_Order_Refund $refund, \WC_Order $order ): array {
		$order_line_ids = array_keys( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) );

		return array_map(
			static fn( \WC_Order_Item $item ): array => array(
				$item->get_type(),
				$item->get_name(),
				array_search( absint( $item->get_meta( '_refunded_item_id' ) ), $order_line_ids, true ),
				is_callable( array( $item, 'get_quantity' ) ) ? $item->get_quantity() : null,
				is_callable( array( $item, 'get_total' ) ) ? $item->get_total() : null,
				is_callable( array( $item, 'get_total_tax' ) ) ? $item->get_total_tax() : null,
			),
			array_values( $refund->get_items() )
		);
	}

	/**
	 * @testdox Should omit the reason when a filter turns it into null.
	 */
	public function test_query_omits_reason_filtered_to_null(): void {
		$order = $this->create_order_without_lines();
		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5,
				'reason'   => 'Late delivery',
			)
		);
		add_filter( 'woocommerce_order_refund_get_reason', '__return_null' );

		$result = wp_get_ability( 'woocommerce/order-refunds-query' )->execute( array( 'id' => $order->get_id() ) );

		remove_filter( 'woocommerce_order_refund_get_reason', '__return_null' );
		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertArrayNotHasKey( 'reason', $result['refunds'][0] );
	}

	/**
	 * @testdox Should report an incomplete refund when the refund cannot be saved as paid back after the gateway refunded.
	 */
	public function test_reports_incomplete_when_paid_refund_save_fails(): void {
		$order = $this->create_order_without_lines();
		add_action(
			'woocommerce_before_order_refund_object_save',
			static function ( $refund ) {
				if ( $refund->get_refunded_payment() ) {
					throw new \Exception( 'Refund storage is read-only.' );
				}
			}
		);

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_incomplete', $result->get_error_code() );
		$this->assertStringContainsString( 'Refund storage is read-only.', $result->get_error_message() );
		$this->assertInstanceOf( \WC_Order_Refund::class, wc_get_order( $result->get_error_data()['refund_id'] ) );
		$this->assertSame( array( '10' ), $this->gateway->refunded );
		$this->assertContains( 'woocommerce_order_partially_refunded', $this->fired );
		$this->assertContains( 'woocommerce_order_refunded', $this->fired );

		$retry = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);
		$this->assertWPError( $retry );
		$this->assertSame( 'woocommerce_ability_order_refund_duplicate', $retry->get_error_code() );
		$this->assertStringContainsString( 'may have refunded the payment', $retry->get_error_message() );
		$this->assertStringNotContainsString( 'delete refund', $retry->get_error_message() );
		$this->assertSame( array( '10' ), $this->gateway->refunded );
	}

	/**
	 * @testdox Should report success when the status change fails once and the final save of the order stores the status.
	 */
	public function test_succeeds_when_final_save_stores_the_status(): void {
		$order  = $this->create_order_without_lines();
		$thrown = false;
		add_action(
			'woocommerce_before_order_object_save',
			static function ( $saved ) use ( $order, &$thrown ) {
				if ( ! $thrown && $saved->get_id() === $order->get_id() && OrderStatus::REFUNDED === $saved->get_status() ) {
					$thrown = true;
					throw new \Exception( 'Order storage is busy.' );
				}
			}
		);

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '50',
			)
		);

		$this->assertTrue( $thrown, 'The order save during update_status() should have failed.' );
		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertSame( OrderStatus::REFUNDED, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should report an incomplete refund, and still fire the closing refund hooks, when the order status cannot change.
	 */
	public function test_reports_incomplete_when_status_update_fails(): void {
		$order  = $this->create_order_without_lines();
		$thrown = false;
		add_action(
			'woocommerce_before_order_object_save',
			static function ( $saved ) use ( $order, &$thrown ) {
				if ( $saved->get_id() === $order->get_id() && OrderStatus::REFUNDED === $saved->get_status() ) {
					$thrown = true;
					throw new \Exception( 'Order storage is read-only.' );
				}
			}
		);

		$result = $this->refund(
			array(
				'id'     => $order->get_id(),
				'amount' => '50',
			)
		);

		$this->assertTrue( $thrown, 'The order save during update_status() should have failed.' );
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_refund_incomplete', $result->get_error_code() );
		$this->assertInstanceOf( \WC_Order_Refund::class, wc_get_order( $result->get_error_data()['refund_id'] ) );
		$this->assertContains( 'woocommerce_refund_created', $this->fired );
		$this->assertContains( 'woocommerce_order_refunded', $this->fired );
	}
}
