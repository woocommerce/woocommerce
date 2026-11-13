<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
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
				$this->refunded[] = (string) $amount;
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
		$this->assertFalse( $result['dry_run'] );
		$this->assertSame( '50', $result['refundable_amount'], 'The refundable amount is the one before the refund.' );
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
		$order = $this->create_order_without_lines();
		add_action(
			'woocommerce_create_refund',
			static function () {
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
	 * @testdox Should return the refund, the refundable amount and the gateway support in a dry run, and change nothing.
	 */
	public function test_dry_run_changes_nothing(): void {
		$order = $this->create_order();

		$result = $this->refund(
			array(
				'id'             => $order->get_id(),
				'amount'         => '20',
				'refund_payment' => true,
				'dry_run'        => true,
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertTrue( $result['dry_run'] );
		$this->assertTrue( $result['gateway_supports_refunds'] );
		$this->assertSame( '50', $result['refundable_amount'] );
		$this->assertSame( '20', $result['refund']['amount'] );
		$this->assertArrayNotHasKey( 'id', $result['refund'] );
		$this->assertSame( array(), $this->gateway->refunded, 'A dry run should not move money.' );
		$this->assertSame( array(), $this->fired, 'No refund hook should fire in a dry run, since some callbacks move money.' );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should delete the refund and fail when the gateway refuses.
	 */
	public function test_gateway_refusal_leaves_no_refund(): void {
		$order                        = $this->create_order();
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
}
