<?php
/**
 * Tests that every action a stage method fires while the order is marked goes through do_staged_action().
 *
 * @package WooCommerce\Tests\Abstracts
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Matches the WC_Data class it tests.
/**
 * Marks an order, runs each stage method, and records each action that fires while the mark is set.
 */
class WC_Data_Staged_Actions_Test extends WC_Unit_Test_Case {

	/**
	 * Actions that fire while the order is marked and are not fired with do_staged_action(), with the reason.
	 * A `*` matches any part of a dynamic name.
	 */
	private const NOT_WRAPPED = array(
		'Loads an object that is not the staged order or one of its items; a listener cannot tell staging apart from a read.' => array(
			'woocommerce_coupon_loaded',
			'woocommerce_product_read',
			'woocommerce_customer_loaded',
		),
		'Deprecated hook notice that WooCommerce fires itself, not a hook about the order.' => array(
			'deprecated_hook_run',
		),
	);

	/**
	 * Products created once per test.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Rows: stage method and the state of the order.
	 *
	 * @return array
	 */
	public function provide_rows(): array {
		$rows = array();
		foreach ( array( 'stage_status', 'stage_product_item', 'stage_taxes', 'stage_recalculated_coupons', 'stage_totals', 'stage_payment_complete', 'stage_payment_complete_invalid_status' ) as $method ) {
			foreach ( array( 'new', 'existing' ) as $state ) {
				$rows[ "$method $state" ] = array( $method, $state );
			}
		}
		return $rows;
	}

	/**
	 * @testdox Every action that a stage method fires on a marked order goes through do_staged_action() or is listed with a reason, and a listener without $staged refuses the call before it runs.
	 * @dataProvider provide_rows
	 *
	 * @param string $method Stage method.
	 * @param string $state  new or existing.
	 */
	public function test_every_action_of_a_staged_call_is_wrapped( string $method, string $state ): void {
		list( $wrapped, $unwrapped ) = $this->record( $method, $state );

		self::log_row( $method, $state, $wrapped, $unwrapped );

		$unlisted = array_values( array_filter( array_keys( $unwrapped ), static fn( $hook ) => ! self::is_listed( $hook ) ) );
		$this->assertSame( array(), $unlisted, 'These actions fire on a staged call without do_staged_action(): ' . wp_json_encode( array_intersect_key( $unwrapped, array_flip( $unlisted ) ) ) );

		foreach ( $wrapped as $hook => $count ) {
			$this->assert_old_listener_refuses( $method, $state, $hook, $count );
		}
	}

	/**
	 * @testdox A listener without $staged does not refuse the saving methods, and gets the arguments it always got.
	 */
	public function test_saving_path_refuses_nobody(): void {
		$order = $this->create_order( 'existing' );
		$seen  = array();
		foreach ( array( 'woocommerce_order_before_calculate_totals', 'woocommerce_order_after_calculate_totals', 'woocommerce_order_before_calculate_taxes', 'woocommerce_order_item_after_calculate_taxes', 'woocommerce_order_edit_status', 'woocommerce_pre_payment_complete' ) as $hook ) {
			add_action(
				$hook,
				static function ( ...$args ) use ( &$seen, $hook ) {
					$seen[ $hook ] = count( $args );
				},
				10,
				2
			);
		}

		$order->recalculate_coupons();
		$order->set_status( OrderStatus::ON_HOLD, '', true );
		$order->save();
		$order->payment_complete( 'txn' );

		$this->assertSame( OrderStatus::PROCESSING, wc_get_order( $order->get_id() )->get_status() );
		$this->assertCount( 6, $seen );
	}

	/**
	 * @testdox A direct stage call is refused when the class overrides the saving method and not the stage method.
	 */
	public function test_unadopted_override_is_refused(): void {
		$order = new WC_Data_Staged_Actions_Test_Order();

		$this->expectException( WC_Data_Exception::class );
		$this->expectExceptionMessage( 'The class WC_Data_Staged_Actions_Test_Order overrides calculate_totals() and needs to override stage_totals() too.' );

		$order->stage_recalculated_coupons();
	}

	/**
	 * Run a row with a spy on all hooks while the order is marked.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Order state.
	 * @return array{0: array<string, int>, 1: array<string, string>} Wrapped actions with their argument count, and unwrapped actions with their call site.
	 */
	private function record( string $method, string $state ): array {
		$order      = $this->create_order( $state );
		$wrapped    = array();
		$unwrapped  = array();
		$plugin_dir = wp_normalize_path( dirname( __DIR__, 4 ) ) . '/';
		$spy        = static function ( string $hook, ...$args ) use ( &$wrapped, &$unwrapped, $plugin_dir ) {
			$frames = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- The caller tells how the hook was fired.
			foreach ( $frames as $index => $frame ) {
				if ( in_array( $frame['function'], array( 'apply_filters', 'apply_filters_ref_array' ), true ) ) {
					return;
				}
				if ( ! in_array( $frame['function'], array( 'do_action', 'do_action_ref_array' ), true ) ) {
					continue;
				}
				$caller = $frames[ $index + 1 ] ?? array();
				if ( 'do_staged_action' === ( $caller['function'] ?? '' ) ) {
					$wrapped[ $hook ] = count( $args );
					return;
				}
				$file = wp_normalize_path( $frame['file'] ?? '' );
				if ( 0 === strpos( $file, $plugin_dir ) ) {
					$unwrapped[ $hook ] = str_replace( $plugin_dir, '', $file ) . ':' . ( $frame['line'] ?? 0 );
				}
				return;
			}
		};

		$order->set_staged( true );
		add_action( 'all', $spy );
		try {
			$this->call( $order, $method );
		} finally {
			remove_action( 'all', $spy );
			$order->set_staged( false );
		}

		return array( $wrapped, $unwrapped );
	}

	/**
	 * Run the row again with a listener that accepts one argument less than the action passes, and assert the
	 * call is refused before the listener runs, with a message that names the action.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Order state.
	 * @param string $hook   Action name.
	 * @param int    $count  Number of arguments, $staged included.
	 */
	private function assert_old_listener_refuses( string $method, string $state, string $hook, int $count ): void {
		$order    = $this->create_order( $state );
		$ran      = false;
		$listener = static function () use ( &$ran ) {
			$ran = true;
		};
		add_action( $hook, $listener, 10, $count - 1 );

		$refusal = null;
		$order->set_staged( true );
		try {
			$this->call( $order, $method );
		} catch ( WC_Data_Exception $e ) {
			$refusal = $e;
		} finally {
			$order->set_staged( false );
			remove_action( $hook, $listener, 10 );
		}

		$this->assertInstanceOf( WC_Data_Exception::class, $refusal, "$hook: a listener without \$staged did not refuse $method ($state)" );
		$this->assertStringContainsString( $hook . ' action', $refusal->getMessage() );
		$this->assertFalse( $ran, "$hook: the listener ran" );
	}

	/**
	 * Call the stage method of a row.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $method Stage method.
	 */
	private function call( WC_Order $order, string $method ): void {
		switch ( $method ) {
			case 'stage_status':
				$order->stage_status( OrderStatus::PROCESSING, 'Staged', true );
				break;
			case 'stage_product_item':
				$order->stage_product_item( $this->products[1], 2, array( 'order' => $order ) );
				break;
			case 'stage_payment_complete':
				$order->stage_payment_complete( 'txn' );
				break;
			case 'stage_payment_complete_invalid_status':
				$order->set_status( OrderStatus::COMPLETED );
				$order->stage_payment_complete( 'txn' );
				break;
			default:
				$order->$method();
		}
	}

	/**
	 * Whether an action is in NOT_WRAPPED.
	 *
	 * @param string $hook Action name.
	 * @return bool
	 */
	private static function is_listed( string $hook ): bool {
		foreach ( self::NOT_WRAPPED as $hooks ) {
			foreach ( $hooks as $pattern ) {
				if ( fnmatch( $pattern, $hook ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Append a row's recorded actions to a JSON lines file when STAGED_ACTIONS_LOG is set, for the spike report.
	 *
	 * @param string $method    Stage method.
	 * @param string $state     Order state.
	 * @param array  $wrapped   Wrapped actions.
	 * @param array  $unwrapped Unwrapped actions.
	 */
	private static function log_row( string $method, string $state, array $wrapped, array $unwrapped ): void {
		$file = getenv( 'STAGED_ACTIONS_LOG' );
		if ( $file ) {
			file_put_contents( $file, wp_json_encode( compact( 'method', 'state', 'wrapped', 'unwrapped' ) ) . "\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	/**
	 * Create an order with taxes on: a product line, a shipping line, a positive and a negative fee and a coupon.
	 * A new order has the lines in memory and no ID.
	 *
	 * @param string $state new or existing.
	 * @return WC_Order
	 */
	private function create_order( string $state ): WC_Order {
		if ( empty( $this->products ) ) {
			update_option( 'woocommerce_calc_taxes', 'yes' );
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate'          => '10.0000',
					'tax_rate_name'     => 'Tax',
					'tax_rate_shipping' => '1',
				)
			);
			$this->products = array( WC_Helper_Product::create_simple_product(), WC_Helper_Product::create_simple_product() );
			WC_Helper_Coupon::create_coupon( 'staged10', array( 'discount_type' => 'percent', 'coupon_amount' => '10' ) );
		}

		$order = new WC_Order();
		$order->set_billing_country( 'US' );
		$line = new WC_Order_Item_Product();
		$line->set_props(
			array(
				'product'  => $this->products[0],
				'quantity' => 1,
				'subtotal' => 10,
				'total'    => 10,
			)
		);
		$order->add_item( $line );
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat',
				'method_id'    => 'flat_rate',
				'total'        => '5',
			)
		);
		$order->add_item( $shipping );
		foreach ( array( '2', '-1' ) as $total ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_props(
				array(
					'name'       => 'Fee ' . $total,
					'total'      => $total,
					'tax_status' => 'taxable',
				)
			);
			$order->add_item( $fee );
		}
		$coupon = new WC_Order_Item_Coupon();
		$coupon->set_props(
			array(
				'code'     => 'staged10',
				'discount' => 1,
			)
		);
		$order->add_item( $coupon );

		if ( 'existing' === $state ) {
			$order->save();
			$order = wc_get_order( $order->get_id() );
		}

		return $order;
	}
}

/**
 * An order class that overrides calculate_totals() and not stage_totals().
 */
class WC_Data_Staged_Actions_Test_Order extends WC_Order {
	/**
	 * Override without a stage twin.
	 *
	 * @param bool $and_taxes Calc taxes if true.
	 * @return float
	 */
	public function calculate_totals( $and_taxes = true ) {
		return parent::calculate_totals( $and_taxes );
	}
}
