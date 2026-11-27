<?php
/**
 * Tests that every action a stage method fires while the order is marked goes through do_staged_action().
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
 * order ID or an item ID. Every action inside the boundary must go through do_staged_action(). There is no list.
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
	 * Rows: stage method or ability, and the state of the order.
	 *
	 * @return array
	 */
	public function provide_rows(): array {
		$rows = array();
		foreach ( array( 'stage_status', 'stage_product_item', 'stage_taxes', 'stage_recalculated_coupons', 'stage_totals', 'stage_totals_cogs', 'stage_payment_complete', 'stage_payment_complete_invalid_status' ) as $method ) {
			foreach ( array( 'new', 'existing' ) as $state ) {
				$rows[ "$method $state" ] = array( $method, $state );
			}
		}
		foreach ( array( 'ability_order_update', 'ability_order_update_status' ) as $method ) {
			$rows[ "$method existing" ] = array( $method, 'existing' );
		}
		$rows['ability_order_create new'] = array( 'ability_order_create', 'new' );
		return $rows;
	}

	/**
	 * @testdox Every action and filter that the staged order or one of its items fires about itself goes through the staged wrappers, and a listener without $staged refuses the call.
	 * @dataProvider provide_rows
	 *
	 * @param string $method Stage method or ability.
	 * @param string $state  new or existing.
	 */
	public function test_every_hook_of_a_staged_call_is_wrapped( string $method, string $state ): void {
		$hooks = $this->record( $method, $state );

		self::log_row( $method, $state, $hooks );

		$unwrapped = array();
		foreach ( $hooks as $key => $hook ) {
			if ( $hook['inside'] && ! $hook['wrapped'] && ! $hook['getter_g0'] ) {
				$unwrapped[ $key ] = $hook;
			}
			if ( $hook['wrapped'] && $hook['bad_staged'] ) {
				$unwrapped[ $key ] = $hook;
			}
		}
		$this->assertSame( array(), $unwrapped, 'These hooks of the staged order fire without the staged wrappers: ' . wp_json_encode( $unwrapped ) );

		$checked = array();
		foreach ( $hooks as $hook ) {
			if ( $hook['wrapped'] && $hook['marked'] && ! isset( $checked[ $hook['hook'] ] ) ) {
				$checked[ $hook['hook'] ] = true;
				$this->assert_old_listener_refuses( $method, $state, $hook['hook'], $hook['type'], $hook['count'] );
			}
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
	 * @testdox A staged payment fires woocommerce_pre_payment_complete first, and Core's order sync adds the note and fires woocommerce_payment_complete after the save.
	 */
	public function test_staged_payment_complete(): void {
		$order = $this->create_order( 'existing' );
		$fired = array();
		$spy   = static function ( string $hook ) use ( &$fired ) {
			if ( in_array( $hook, array( 'woocommerce_pre_payment_complete', 'woocommerce_valid_order_statuses_for_payment_complete', 'woocommerce_payment_complete_order_status', 'woocommerce_payment_complete', 'woocommerce_order_status_processing' ), true ) ) {
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
		$this->assertSame( array( 'woocommerce_pre_payment_complete', 'woocommerce_valid_order_statuses_for_payment_complete', 'woocommerce_payment_complete_order_status', 'save', 'woocommerce_order_status_processing', 'woocommerce_payment_complete' ), $fired );
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
						'callback'  => static function ( array $orders ) use ( &$seen ) {
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
	 * Hook names that WooCommerce fires only when a listener exists (has_action() or has_filter() with a literal name).
	 *
	 * @return string[]
	 */
	private static function guarded_hook_names(): array {
		static $names = null;
		if ( null === $names ) {
			$names = array();
			$root  = dirname( __DIR__, 4 );
			foreach ( array( 'includes', 'src' ) as $dir ) {
				foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( "$root/$dir", FilesystemIterator::SKIP_DOTS ) ) as $file ) {
					if ( 'php' === $file->getExtension() && preg_match_all( "/has_(?:action|filter)\\(\\s*'([a-z0-9_]+)'/", (string) file_get_contents( $file->getPathname() ), $m ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
						$names = array_merge( $names, $m[1] );
					}
				}
			}
			$names = array_values( array_unique( $names ) );
		}
		return $names;
	}

	/**
	 * Add a listener that accepts every argument to each hook that fires only when a listener exists.
	 */
	private static function add_guard_listeners(): void {
		foreach ( self::guarded_hook_names() as $name ) {
			add_filter( $name, array( self::class, 'pass_through' ), 10, 99 );
		}
	}

	/**
	 * Remove the listeners of add_guard_listeners().
	 */
	private static function remove_guard_listeners(): void {
		foreach ( self::guarded_hook_names() as $name ) {
			remove_filter( $name, array( self::class, 'pass_through' ), 10 );
		}
	}

	/**
	 * A listener that changes nothing.
	 *
	 * @param mixed $value First argument.
	 * @return mixed
	 */
	public static function pass_through( $value = null ) {
		return $value;
	}

	/**
	 * Whether the object is marked, read without firing a hook.
	 *
	 * @param mixed $data_object Object.
	 * @return bool
	 */
	private static function is_marked( $data_object ): bool {
		if ( ! $data_object instanceof WC_Data ) {
			return false;
		}
		$property = new ReflectionProperty( WC_Data::class, 'staged_mark' );
		$property->setAccessible( true );
		return (bool) $property->getValue( $data_object );
	}

	/**
	 * Run a row with a spy on all hooks. For each hook fired while an order is marked, record where it fires, and
	 * whether each boundary puts it inside.
	 *
	 * Owner boundary ("inside"): the method that fires the hook runs on the marked order, or on one of its items. An
	 * item is the order's when it links to it, or when its order ID is the order's ID (items being read).
	 * Old boundary ("inside_old"): the call is in a WooCommerce file, and the caller object or an argument is the
	 * order, a linked item, the order ID or an item ID.
	 *
	 * @param string $method Stage method or ability.
	 * @param string $state  Order state.
	 * @return array<string, array> Keyed by hook and call site.
	 */
	private function record( string $method, string $state ): array {
		$order      = $this->create_order( $state );
		$hooks      = array();
		$plugin_dir = wp_normalize_path( dirname( __DIR__, 4 ) ) . '/';
		$busy       = false;
		$current    = null;
		$fire_fns   = array( 'do_action', 'do_action_ref_array', 'apply_filters', 'apply_filters_ref_array' );
		$getters    = array( 'WC_Data::get_prop', 'WC_Data::get_meta', 'WC_Order::get_address_prop' );
		$spy        = static function ( string $hook, ...$args ) use ( &$hooks, &$busy, &$current, $plugin_dir, $fire_fns, $getters ) {
			if ( $busy ) {
				return;
			}
			$busy   = true;
			$frames = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 14 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- The caller tells how the hook was fired.
			foreach ( $frames as $index => $frame ) {
				if ( ! in_array( $frame['function'], $fire_fns, true ) ) {
					continue;
				}
				$type    = 0 === strpos( $frame['function'], 'do_action' ) ? 'action' : 'filter';
				$caller  = $frames[ $index + 1 ] ?? array();
				$wrapped = in_array( $caller['function'] ?? '', array( 'do_staged_action', 'apply_staged_filters' ), true );
				$method  = $wrapped ? ( $frames[ $index + 2 ] ?? array() ) : $caller;
				$site_fr = $wrapped ? ( $frames[ $index + 1 ] ?? array() ) : $frame;
				$object  = $method['object'] ?? null;
				$args    = '_ref_array' === substr( $frame['function'], -10 ) ? (array) ( $args[0] ?? array() ) : $args;

				// The order that is marked now: the caller object, the order of a linked item, or one of the arguments.
				if ( null !== $current && ! self::is_marked( $current ) ) {
					$current = null;
				}
				foreach ( array_merge( array( $object ), $args ) as $value ) {
					if ( $value instanceof WC_Abstract_Order && self::is_marked( $value ) ) {
						$current = $value;
					} elseif ( $value instanceof WC_Order_Item && self::item_order( $value ) && self::is_marked( self::item_order( $value ) ) ) {
						$current = self::item_order( $value );
					}
				}
				if ( null === $current ) {
					break;
				}

				$file     = wp_normalize_path( $site_fr['file'] ?? '' );
				$site     = str_replace( $plugin_dir, '', $file ) . ':' . ( $site_fr['line'] ?? 0 );
				$linked   = $object instanceof WC_Order_Item && self::item_order( $object ) === $current;
				$unlinked = $object instanceof WC_Order_Item && ! $linked && $current->get_id() > 0 && (int) $object->get_order_id( 'edit' ) === $current->get_id();
				$inside   = $object === $current || $linked || $unlinked;
				$in_wc    = 0 === strpos( $file, $plugin_dir );
				$old      = $in_wc && self::is_about( $current, $object, $args );
				$fn       = ( $method['class'] ?? '' ) . '::' . ( $method['function'] ?? '' );
				$getter   = in_array( $fn, $getters, true );
				$key      = "$hook $site";
				$entry    = $hooks[ $key ] ?? array(
					'hook'       => $hook,
					'type'       => $type,
					'site'       => $site,
					'function'   => $fn,
					'object'     => is_object( $object ) ? get_class( $object ) : null,
					'inside'     => false,
					'inside_old' => false,
					'wrapped'    => false,
					'getter'     => $getter,
					'getter_g0'  => $getter && ! WC_Data::staged_getter_filters(),
					'marked'     => false,
					'unlinked'   => false,
					'bad_staged' => false,
					'count'      => 0,
					'fired'      => 0,
				);
				++$entry['fired'];
				$entry['inside']     = $entry['inside'] || $inside;
				$entry['inside_old'] = $entry['inside_old'] || $old;
				$entry['unlinked']   = $entry['unlinked'] || $unlinked;
				if ( $wrapped ) {
					$entry['wrapped'] = true;
					$entry['count']   = count( $args );
					$last             = end( $args );
					if ( $last === $current ) {
						$entry['marked'] = true;
					} elseif ( ! ( null === $last && ( $unlinked || ! $inside ) ) ) {
						$entry['bad_staged'] = true;
					}
				}
				$hooks[ $key ] = $entry;
				break;
			}
			$busy = false;
		};

		self::add_guard_listeners();
		add_action( 'all', $spy );
		try {
			$this->call( $order, $method, true );
		} finally {
			remove_action( 'all', $spy );
			self::remove_guard_listeners();
			$order->set_staged( false );
		}
		// The guard listeners make deprecated hooks fire. That is expected here.
		$this->caught_deprecated = array();

		ksort( $hooks );
		return $hooks;
	}

	/**
	 * Whether an action is about the staged order: the code that fires it runs on the order or one of its items, or
	 * one of its arguments is the order, one of its items, the order ID or an item ID.
	 *
	 * @param WC_Abstract_Order $order  Staged order.
	 * @param object|null       $caller Object whose method called do_action(), or null for a function or a static method.
	 * @param array             $args   Action arguments.
	 * @return bool
	 */
	private static function is_about( WC_Abstract_Order $order, $caller, array $args ): bool {
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
	 * @return WC_Abstract_Order|null
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
	 * @param WC_Abstract_Order $order Order.
	 * @param int               $id    ID.
	 * @return bool
	 */
	private static function is_item_id( WC_Abstract_Order $order, int $id ): bool {
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
	 * Run the row again with a listener that accepts one argument less than the hook passes, and assert the call is
	 * refused with a message that names the hook.
	 *
	 * @param string $method Stage method or ability.
	 * @param string $state  Order state.
	 * @param string $hook   Hook name.
	 * @param string $type   action or filter.
	 * @param int    $count  Number of arguments, $staged included.
	 */
	private function assert_old_listener_refuses( string $method, string $state, string $hook, string $type, int $count ): void {
		$order    = $this->create_order( $state );
		$listener = static function ( $value = null ) {
			return $value;
		};
		add_filter( $hook, $listener, 10, $count - 1 );

		$refusal = null;
		try {
			$refusal = $this->call( $order, $method, true );
		} catch ( WC_Data_Exception $e ) {
			$refusal = $e->getMessage();
		} finally {
			$order->set_staged( false );
			remove_filter( $hook, $listener, 10 );
		}
		$this->caught_deprecated = array();
		if ( $refusal instanceof WP_Error ) {
			$refusal = $refusal->get_error_message();
		}

		$this->assertIsString( $refusal, "$hook: a listener without \$staged did not refuse $method ($state)" );
		$this->assertStringContainsString( $hook . ' hook', $refusal );
	}

	/**
	 * Call the stage method of a row on the marked order, or run the ability of a row.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $method Stage method or ability.
	 * @param bool     $mark   Whether to mark the order before a stage method.
	 * @return mixed The error message of a refused ability call, or null.
	 */
	private function call( WC_Order $order, string $method, bool $mark = false ) {
		if ( 0 === strpos( $method, 'ability_' ) ) {
			return $this->run_ability( $order, $method );
		}
		if ( 'stage_totals_cogs' === $method ) {
			update_option( 'woocommerce_feature_cost_of_goods_sold_enabled', 'yes' );
		}
		if ( 'stage_payment_complete_invalid_status' === $method ) {
			$order->set_status( OrderStatus::COMPLETED );
		}
		$order->set_staged( $mark );
		switch ( $method ) {
			case 'stage_status':
				$order->stage_status( OrderStatus::PROCESSING, 'Staged', true );
				break;
			case 'stage_product_item':
				$order->stage_product_item( $this->products[1], 2, array( 'order' => $order ) );
				break;
			case 'stage_payment_complete':
			case 'stage_payment_complete_invalid_status':
				$order->stage_payment_complete( 'txn' );
				break;
			case 'stage_totals_cogs':
				$order->stage_totals();
				break;
			default:
				$order->$method();
		}
		return null;
	}

	/**
	 * Run the order ability of a row with the feature on.
	 *
	 * @param WC_Order $order  Order (saved, or new for order-create).
	 * @param string   $method Ability row.
	 * @return string|null The error message when the call is refused.
	 */
	private function run_ability( WC_Order $order, string $method ) {
		if ( ! ExtensibleAbilities::is_enabled() ) {
			$this->enable_extensible_abilities();
		}
		wp_set_current_user( 1 );
		$product = $this->products[1]->get_id();
		switch ( $method ) {
			case 'ability_order_update':
				$result = wp_get_ability( 'woocommerce/order-update' )->execute(
					array(
						'id'         => $order->get_id(),
						'line_items' => array( array( 'product_id' => $product, 'quantity' => 1 ) ),
					)
				);
				break;
			case 'ability_order_update_status':
				$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
					array(
						'id'     => $order->get_id(),
						'status' => OrderStatus::PROCESSING,
					)
				);
				break;
			default:
				$result = wp_get_ability( 'woocommerce/order-create' )->execute(
					array( 'line_items' => array( array( 'product_id' => $product, 'quantity' => 1 ) ) )
				);
		}
		return is_wp_error( $result ) ? $result->get_error_message() : null;
	}

	/**
	 * Append a row's recorded hooks to a JSON lines file when STAGED_HOOKS_LOG is set, for the spike report.
	 *
	 * @param string $method Stage method or ability.
	 * @param string $state  Order state.
	 * @param array  $hooks  Recorded hooks.
	 */
	private static function log_row( string $method, string $state, array $hooks ): void {
		$file = getenv( 'STAGED_HOOKS_LOG' );
		if ( $file ) {
			$g1    = WC_Data::staged_getter_filters();
			$hooks = array_values( $hooks );
			file_put_contents( $file, wp_json_encode( compact( 'method', 'state', 'g1', 'hooks' ) ) . "\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions
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

		// needs_processing() caches its answer for a new order under the key of ID 0.
		wp_cache_delete( 'order-needs-processing-0', 'orders' );
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
