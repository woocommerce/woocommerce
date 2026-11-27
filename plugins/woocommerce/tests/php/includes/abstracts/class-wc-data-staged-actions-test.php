<?php
/**
 * Tests that every action a stage method fires while the order is marked fires under a new name, and no old name fires.
 *
 * @package WooCommerce\Tests\Abstracts
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Tests\Internal\Abilities\ExtensibleAbilitiesTestTrait;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Matches the WC_Data class it tests.
/**
 * Marks an order, runs each stage method, and records each action that fires while the mark is set.
 *
 * The boundary: an action that WooCommerce fires (the do_action() call is in a file of the plugin) while the order
 * is marked, and that is about the staged order. It is about the staged order when the code that calls do_action()
 * runs on the order or on one of its items, or when an argument of the action is the order, one of its items, the
 * order ID or an item ID. Every action inside the boundary must be a new name (ending in _v2) that
 * do_mapped_action() fires. There is no list. Before the run, the test adds a listener to every action name that
 * the plugin code names, so actions behind has_action() fire too. It skips the old names that do_mapped_action()
 * and refuse_old_listeners() handle, because a listener there refuses the call by design.
 */
class WC_Data_Staged_Actions_Test extends WC_Unit_Test_Case {

	use ExtensibleAbilitiesTestTrait;

	/**
	 * Products created once per test.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Action names in the plugin code: names to listen to, and old names that the mapping handles.
	 *
	 * @var array{0: string[], 1: string[]}|null
	 */
	private static $hook_names = null;

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
	 * @testdox Every action about the staged order that a stage method fires is a new name, no old name fires, and a listener on the old name refuses the call before it runs.
	 * @dataProvider provide_rows
	 *
	 * @param string $method Stage method.
	 * @param string $state  new or existing.
	 */
	public function test_every_action_of_a_staged_call_is_a_new_name( string $method, string $state ): void {
		list( $mapped, $old, $outside ) = $this->record( $method, $state );

		self::log_row( $method, $state, $mapped, $old, $outside );

		$this->assertSame( array(), $old, 'These old action names about the staged order fire on the staged path: ' . wp_json_encode( $old ) );

		foreach ( array_keys( $mapped ) as $hook ) {
			$this->assert_old_listener_refuses( $method, $state, substr( $hook, 0, -3 ) );
		}
	}

	/**
	 * @testdox On the saving path the old names fire with their old arguments, then the new names fire with the object and false.
	 */
	public function test_saving_path_fires_old_and_new_names(): void {
		$order = $this->create_order( 'existing' );
		$seen  = array();
		foreach ( array( 'woocommerce_order_before_calculate_totals', 'woocommerce_order_after_calculate_totals', 'woocommerce_order_before_calculate_taxes', 'woocommerce_order_item_after_calculate_taxes', 'woocommerce_order_edit_status', 'woocommerce_pre_payment_complete' ) as $hook ) {
			foreach ( array( $hook, $hook . '_v2' ) as $name ) {
				add_action(
					$name,
					static function ( ...$args ) use ( &$seen, $name ) {
						$seen[ $name ][] = $args;
					},
					10,
					9
				);
			}
		}

		$order->recalculate_coupons();
		$order->set_status( OrderStatus::ON_HOLD, '', true );
		$order->save();
		$order->payment_complete( 'txn' );

		$this->assertSame( OrderStatus::PROCESSING, wc_get_order( $order->get_id() )->get_status() );
		$this->assertCount( 12, $seen );
		foreach ( $seen as $name => $calls ) {
			$is_new = '_v2' === substr( $name, -3 );
			$this->assertCount( $is_new ? 3 : 2, $calls[0], $name );
			if ( $is_new ) {
				$this->assertIsObject( $calls[0][0], $name );
				$this->assertFalse( $calls[0][1], $name );
				$this->assertCount( count( $seen[ substr( $name, 0, -3 ) ] ), $calls, "$name fires as often as the old name" );
			}
		}
		$this->assertSame( $order->get_id(), $seen['woocommerce_pre_payment_complete'][0][0] );
		$this->assertSame( $order, $seen['woocommerce_pre_payment_complete_v2'][0][0] );
	}

	/**
	 * @testdox A listener on the deprecated woocommerce_order_add_product action refuses a staged product line, and the message names it.
	 */
	public function test_deprecated_add_product_listener_refuses(): void {
		$order    = $this->create_order( 'existing' );
		$listener = static function () {};
		add_action( 'woocommerce_order_add_product', $listener );

		$order->set_staged( true );
		try {
			$order->stage_product_item( $this->products[1], 1, array( 'order' => $order ) );
			$this->fail( 'No refusal' );
		} catch ( WC_Data_Exception $e ) {
			$this->assertStringContainsString( 'woocommerce_order_add_product action', $e->getMessage() );
			$this->assertStringContainsString( 'has no replacement', $e->getMessage() );
		} finally {
			$order->set_staged( false );
			remove_action( 'woocommerce_order_add_product', $listener );
		}
	}

	/**
	 * @testdox A staged payment fires woocommerce_pre_payment_complete first, and Core's order sync adds the note and fires woocommerce_payment_complete after the save.
	 */
	public function test_staged_payment_complete(): void {
		$order = $this->create_order( 'existing' );
		$fired = array();
		$spy   = static function ( string $hook ) use ( &$fired ) {
			if ( in_array( $hook, array( 'woocommerce_pre_payment_complete', 'woocommerce_pre_payment_complete_v2', 'woocommerce_valid_order_statuses_for_payment_complete', 'woocommerce_payment_complete_order_status', 'woocommerce_payment_complete', 'woocommerce_order_status_processing' ), true ) ) {
				$fired[] = $hook;
			}
		};
		add_action( 'all', $spy );

		$order->set_staged( true );
		$completes = $order->stage_payment_complete( 'txn-1' );
		$order->set_staged( false );
		$fired[] = 'save';
		$order->save();
		AbilitiesLoader::sync_staged_payments( array( $order ) );
		AbilitiesLoader::sync_staged_payments( array( $order ) );
		remove_action( 'all', $spy );

		$this->assertTrue( $completes );
		$this->assertSame( array( 'woocommerce_pre_payment_complete_v2', 'woocommerce_valid_order_statuses_for_payment_complete', 'woocommerce_payment_complete_order_status', 'save', 'woocommerce_order_status_processing', 'woocommerce_payment_complete' ), $fired );
		$stored = wc_get_order( $order->get_id() );
		$this->assertSame( OrderStatus::PROCESSING, $stored->get_status() );
		$this->assertSame( 'txn-1', $stored->get_transaction_id() );
		$this->assertCount( 1, array_filter( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), static fn( $note ) => 'Payment complete.' === $note->content ) );
	}

	/**
	 * @testdox Spike measurement: a staged payment saved outside an ability gets no payment note and no woocommerce_payment_complete, because no sync runs.
	 */
	public function test_staged_payment_outside_an_ability_loses_the_note_and_action(): void {
		$order = $this->create_order( 'existing' );
		$fired = did_action( 'woocommerce_payment_complete' );

		$order->stage_payment_complete( 'txn-2' );
		$order->save();

		$this->assertSame( OrderStatus::PROCESSING, wc_get_order( $order->get_id() )->get_status() );
		$this->assertSame( $fired, did_action( 'woocommerce_payment_complete' ) );
		$this->assertCount( 0, array_filter( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), static fn( $note ) => false !== strpos( $note->content, 'Payment' ) ) );
	}

	/**
	 * @testdox Spike measurement: Core's order sync runs before an extension sync that registered earlier, inside an ability call, and a failure in it rolls the call back.
	 */
	public function test_core_payment_sync_in_an_ability(): void {
		$seen = array();
		// Priority -10 registers the extension sync before Core's sync, which registers at 0.
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () use ( &$seen ) {
				ExtensibleAbilities::register_validator(
					array(
						'resource'  => 'order',
						'namespace' => 'spike',
						'callback'  => static function ( $order ) {
							// Not allowed for a real validator: it changes the order. It stands in for a stage call of an ability.
							$order->stage_payment_complete( 'txn-3' );
							return null;
						},
					)
				);
				ExtensibleAbilities::register_sync(
					array(
						'resource'  => 'order',
						'namespace' => 'spike',
						'callback'  => static function ( array $orders ) use ( &$seen ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- A sync callback receives the orders; this one only records the action count.
							$seen[] = did_action( 'woocommerce_payment_complete' );
						},
					)
				);
			},
			-10
		);
		$this->enable_extensible_abilities();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$syncs = ExtensibleAbilities::get_syncs( 'order' );
		$this->assertArrayNotHasKey( 'namespace', $syncs[0] );
		$this->assertSame( 'spike', $syncs[1]['namespace'] );

		$order  = $this->create_order( 'existing' );
		$before = did_action( 'woocommerce_payment_complete' );
		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => OrderStatus::ON_HOLD,
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( $before + 1 ), $seen );
		$this->assertSame( OrderStatus::PROCESSING, wc_get_order( $order->get_id() )->get_status() );
		$this->assertCount( 1, array_filter( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), static fn( $note ) => 'Payment complete.' === $note->content ) );

		$failing = $this->create_order( 'existing' );
		$throw   = static function () {
			throw new Exception( 'Listener failed' );
		};
		add_action( 'woocommerce_payment_complete', $throw );
		$error = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $failing->get_id(),
				'status' => OrderStatus::ON_HOLD,
			)
		);
		remove_action( 'woocommerce_payment_complete', $throw );
		$this->assertWPError( $error );
		$this->assertSame( 'woocommerce_ability_extension_failed', $error->get_error_code() );
		$this->assertSame( 'An extension failed while checking this change.', $error->get_error_message() );
		$this->assertArrayNotHasKey( 'namespace', $error->get_error_data() );
		$this->assertSame( OrderStatus::PENDING, wc_get_order( $failing->get_id() )->get_status() );
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
	 * Run a row with a spy on all hooks and a listener on every action name of the plugin, while the order is marked.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Order state.
	 * @return array{0: array<string, int>, 1: array<string, string>, 2: array<string, string>} New names with their argument count, old names inside the boundary with their call site, and actions outside the boundary with their call site.
	 */
	private function record( string $method, string $state ): array {
		$order      = $this->create_order( $state );
		$mapped     = array();
		$old        = array();
		$outside    = array();
		$plugin_dir = wp_normalize_path( dirname( __DIR__, 4 ) ) . '/';
		$busy       = false;
		$spy        = static function ( string $hook, ...$args ) use ( $order, &$mapped, &$old, &$outside, $plugin_dir, &$busy ) {
			if ( $busy ) {
				return;
			}
			$busy   = true;
			$frames = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 12 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- The caller tells how the hook was fired.
			foreach ( $frames as $index => $frame ) {
				if ( in_array( $frame['function'], array( 'apply_filters', 'apply_filters_ref_array' ), true ) ) {
					break;
				}
				if ( ! in_array( $frame['function'], array( 'do_action', 'do_action_ref_array' ), true ) ) {
					continue;
				}
				$caller = $frames[ $index + 1 ] ?? array();
				$site   = str_replace( $plugin_dir, '', wp_normalize_path( $frame['file'] ?? '' ) ) . ':' . ( $frame['line'] ?? 0 );
				$args   = 'do_action_ref_array' === $frame['function'] ? (array) ( $args[0] ?? array() ) : $args;
				if ( 'do_mapped_action' === ( $caller['function'] ?? '' ) && '_v2' === substr( $hook, -3 ) ) {
					$mapped[ $hook ] = count( $args );
					if ( true !== ( $args[1] ?? null ) || ! self::is_about( $order, null, array( $args[0] ?? null ) ) ) {
						$old[ $hook ] = 'does not pass the staged object and true';
					}
				} elseif ( 0 !== strpos( wp_normalize_path( $frame['file'] ?? '' ), $plugin_dir ) ) {
					$outside[ $hook ] = $site;
				} elseif ( self::is_about( $order, $caller['object'] ?? null, $args ) ) {
					$old[ $hook ] = $site;
				} else {
					$outside[ $hook ] = $site;
				}
				break;
			}
			$busy = false;
		};

		$remove = $this->listen_everywhere();
		$order->set_staged( true );
		add_action( 'all', $spy );
		try {
			$this->call( $order, $method );
		} finally {
			remove_action( 'all', $spy );
			$order->set_staged( false );
			$remove();
		}

		return array( $mapped, $old, $outside );
	}

	/**
	 * Add a listener to every action name that the plugin code fires or checks with has_action(), except the old
	 * names that do_mapped_action() and refuse_old_listeners() handle.
	 *
	 * @return callable Removes the listeners.
	 */
	private function listen_everywhere(): callable {
		if ( null === self::$hook_names ) {
			$listen = array();
			$mapped = array();
			$root   = dirname( __DIR__, 4 );
			foreach ( array( 'includes', 'src' ) as $folder ) {
				$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( "$root/$folder", FilesystemIterator::SKIP_DOTS ) );
				foreach ( $files as $file ) {
					if ( 'php' !== $file->getExtension() ) {
						continue;
					}
					preg_match_all( "/\\b(do_action|do_action_ref_array|do_action_deprecated|has_action|wc_do_deprecated_action|do_mapped_action|refuse_old_listeners)\\(\\s*'([A-Za-z0-9_\\-\\/]+)'\\s*[,)]/", (string) file_get_contents( $file->getPathname() ), $matches, PREG_SET_ORDER ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					foreach ( $matches as $match ) {
						if ( in_array( $match[1], array( 'do_mapped_action', 'refuse_old_listeners' ), true ) ) {
							$mapped[ $match[2] ] = true;
						} else {
							$listen[ $match[2] ] = true;
						}
					}
				}
			}
			self::$hook_names = array( array_keys( array_diff_key( $listen, $mapped ) ), array_keys( $mapped ) );
		}

		$listener = static function () {};
		foreach ( self::$hook_names[0] as $hook ) {
			add_action( $hook, $listener, 10, 0 );
		}
		return static function () use ( $listener ) {
			foreach ( self::$hook_names[0] as $hook ) {
				remove_action( $hook, $listener, 10 );
			}
		};
	}

	/**
	 * Whether an action is about the staged order: the code that fires it runs on the order or one of its items, or
	 * one of its arguments is the order, one of its items, the order ID or an item ID.
	 *
	 * @param WC_Order    $order  Staged order.
	 * @param object|null $caller Object whose method called do_action(), or null for a function or a static method.
	 * @param array       $args   Action arguments.
	 * @return bool
	 */
	private static function is_about( WC_Order $order, $caller, array $args ): bool {
		foreach ( array_merge( array( $caller ), $args ) as $value ) {
			if ( $value === $order || ( $value instanceof WC_Order_Item && self::item_order( $value ) === $order ) ) {
				return true;
			}
			if ( is_int( $value ) && $value > 0 && ( $value === $order->get_id() || self::is_item_id( $order, $value ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The order instance an item links to, read without loading anything.
	 *
	 * @param WC_Order_Item $item Item.
	 * @return WC_Order|null
	 */
	private static function item_order( WC_Order_Item $item ) {
		$property = new ReflectionProperty( WC_Order_Item::class, 'order' );
		$property->setAccessible( true );
		$reference = $property->getValue( $item );
		return $reference ? $reference->get() : null;
	}

	/**
	 * Whether an ID is the ID of an item that the order holds in memory, read without loading anything.
	 *
	 * @param WC_Order $order Order.
	 * @param int      $id    ID.
	 * @return bool
	 */
	private static function is_item_id( WC_Order $order, int $id ): bool {
		$property = new ReflectionProperty( WC_Abstract_Order::class, 'items' );
		$property->setAccessible( true );
		foreach ( $property->getValue( $order ) as $items ) {
			foreach ( (array) $items as $item ) {
				if ( $item instanceof WC_Order_Item && $item->get_id() === $id ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Run the row again with a listener on the old name, and assert the call is refused before the listener runs,
	 * with a message that names the action, the listener and the new name.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Order state.
	 * @param string $hook   Old action name.
	 */
	private function assert_old_listener_refuses( string $method, string $state, string $hook ): void {
		$order    = $this->create_order( $state );
		$ran      = false;
		$listener = static function () use ( &$ran ) {
			$ran = true;
		};
		add_action( $hook, $listener, 10, 9 );

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

		$this->assertInstanceOf( WC_Data_Exception::class, $refusal, "$hook: a listener on the old name did not refuse $method ($state)" );
		$this->assertStringContainsString( $hook . ' action', $refusal->getMessage() );
		$this->assertStringContainsString( 'a closure in', $refusal->getMessage() );
		$this->assertStringContainsString( $hook . '_v2', $refusal->getMessage() );
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
	 * Append a row's recorded actions to a JSON lines file when STAGED_ACTIONS_LOG is set, for the spike report.
	 *
	 * @param string $method    Stage method.
	 * @param string $state     Order state.
	 * @param array  $mapped    New names fired by do_mapped_action().
	 * @param array  $old       Old names inside the boundary.
	 * @param array  $outside   Actions outside the boundary.
	 */
	private static function log_row( string $method, string $state, array $mapped, array $old, array $outside ): void {
		$file = getenv( 'STAGED_ACTIONS_LOG' );
		if ( $file ) {
			file_put_contents( $file, wp_json_encode( compact( 'method', 'state', 'mapped', 'old', 'outside' ) ) . "\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions
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
			WC_Helper_Coupon::create_coupon(
				'staged10',
				array(
					'discount_type' => 'percent',
					'coupon_amount' => '10',
				)
			);
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

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test helper used only by the tests above.
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
	public function calculate_totals( $and_taxes = true ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- The test needs a subclass that overrides this method.
		return parent::calculate_totals( $and_taxes );
	}
}
