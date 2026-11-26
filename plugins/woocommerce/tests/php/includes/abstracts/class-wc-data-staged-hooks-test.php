<?php
/**
 * Tests that every WooCommerce hook a stage call on a marked object fires before the write is checked or decided.
 *
 * @package WooCommerce\Tests\Abstracts
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Matches the WC_Data class it tests.
/**
 * Stages through every public stage_*() method on an object marked as staged and accounts for every WooCommerce hook
 * that fires before the write: fired with do_staged_action() or apply_staged_filters(), deferred with a listener check, or decided.
 */
class WC_Data_Staged_Hooks_Test extends WC_Unit_Test_Case {

	/**
	 * Read methods of an object. A filter that one of them fires is a getter filter and gets no listener check: a
	 * getter filter must not write.
	 */
	private const READ_METHODS = array( 'get_prop', 'get_meta', 'get_address_prop', 'get_items', 'get_subtotal', 'get_product', 'has_status', 'filter_raw_meta_data' );

	/**
	 * Hooks that a stage method defers to the save, with their listeners checked at stage time. They fire through a
	 * literal do_action() inside a closure, not through the wrapper.
	 */
	private const DEFERRED_CHECKED = array( 'woocommerce_order_after_calculate_totals' );

	/**
	 * WooCommerce hooks that can fire before the write on a staged call and get no listener check, by group.
	 * Each group has the reason. The names come from the review of every hook that fires before the write on a
	 * staged call. A `*` matches any part of a dynamic hook name. Hooks fired by a read method of the object, and
	 * hooks that WordPress fires, are left out by a rule in record_fires(), not by name.
	 */
	private const DECIDED = array(
		'The review found no difference for a listener between a staged call and the saving path.' => array(
			'action_scheduler_stored_action',
			'deleted_*_meta',
			'updated_*_meta',
			'wc_get_price_decimal_separator',
			'wc_get_price_decimals',
			'wc_order_statuses',
			'wc_order_types',
			'wc_round_tax_total',
			'wc_shipping_enabled',
			'wc_tax_enabled',
			'woocommerce_*_data_store',
			'woocommerce_adjust_non_base_location_prices',
			'woocommerce_analytics_customer_roles',
			'woocommerce_apply_base_tax_for_local_pickup',
			'woocommerce_base_tax_rates',
			'woocommerce_before_*_object_save',
			'woocommerce_before_delete_order_item',
			'woocommerce_calc_tax',
			'woocommerce_cart_coupon_types',
			'woocommerce_change_term_counts',
			'woocommerce_countries_base_city',
			'woocommerce_countries_base_country',
			'woocommerce_countries_base_postcode',
			'woocommerce_countries_base_state',
			'woocommerce_coupon_code',
			'woocommerce_coupon_discount_types',
			'woocommerce_coupon_error',
			'woocommerce_coupon_is_valid',
			'woocommerce_coupon_is_valid_for_cart',
			'woocommerce_coupon_is_valid_for_excluded_items',
			'woocommerce_coupon_is_valid_for_excluded_product_categories',
			'woocommerce_coupon_is_valid_for_excluded_product_ids',
			'woocommerce_coupon_is_valid_for_product_categories',
			'woocommerce_coupon_is_valid_for_product_ids',
			'woocommerce_coupon_is_valid_for_sale_items',
			'woocommerce_coupon_loaded',
			'woocommerce_coupon_validate_expiry_date',
			'woocommerce_coupon_validate_maximum_amount',
			'woocommerce_coupon_validate_minimum_amount',
			'woocommerce_coupon_validate_user_usage_limit',
			'woocommerce_currency',
			'woocommerce_customer_loaded',
			'woocommerce_data_store_wp_*_read_meta',
			'woocommerce_data_stores',
			'woocommerce_decrease_coupon_usage_count',
			'woocommerce_default_order_status',
			'woocommerce_delete_order_item',
			'woocommerce_find_rates',
			'woocommerce_format_postcode',
			'woocommerce_generate_order_key',
			'woocommerce_get_base_location',
			'woocommerce_get_coupon_id_from_code',
			'woocommerce_get_order_item_classname',
			'woocommerce_get_price_excluding_tax',
			'woocommerce_get_product_cogs_total_value',
			'woocommerce_get_shop_coupon_data',
			'woocommerce_internal_rounding_precision',
			'woocommerce_is_downloadable',
			'woocommerce_is_virtual',
			'woocommerce_load_order_cogs_value',
			'woocommerce_load_product_cogs_value',
			'woocommerce_local_pickup_methods',
			'woocommerce_matched_rates',
			'woocommerce_matched_tax_rates',
			'woocommerce_max_webhook_delivery_failures',
			'woocommerce_new_order',
			'woocommerce_new_order_data',
			'woocommerce_new_stock_notification',
			'woocommerce_new_webhook',
			'woocommerce_order_class',
			'woocommerce_order_data_store_cpt_query_unsupported_args',
			'woocommerce_order_number',
			'woocommerce_order_object_updated_props',
			'woocommerce_order_refund_object_updated_props',
			'woocommerce_order_type_to_group',
			'woocommerce_orders_table_datastore_db_rows_for_order',
			'woocommerce_orders_table_datastore_extra_db_rows_for_order',
			'woocommerce_payment_complete_order_status_*',
			'woocommerce_price_ex_tax_amount',
			'woocommerce_price_inc_tax_amount',
			'woocommerce_prices_include_tax',
			'woocommerce_product_backorders_require_notification',
			'woocommerce_product_class',
			'woocommerce_product_coupon_types',
			'woocommerce_product_is_on_sale',
			'woocommerce_product_is_taxable',
			'woocommerce_product_read',
			'woocommerce_product_stock_status_options',
			'woocommerce_product_type_query',
			'woocommerce_product_visibility_options',
			'woocommerce_rate_code',
			'woocommerce_rate_compound',
			'woocommerce_rate_label',
			'woocommerce_removed_order_items',
			'woocommerce_save_order_cogs_value',
			'woocommerce_shipping_init',
			'woocommerce_shipping_methods',
			'woocommerce_sortable_taxonomies',
			'woocommerce_stock_amount',
			'woocommerce_tax_round',
			'woocommerce_update_order',
			'woocommerce_update_order_refund',
			'woocommerce_update_stock_notification',
			'woocommerce_webhook_delivery_url',
			'woocommerce_webhook_http_args',
			'woocommerce_webhook_name',
			'woocommerce_webhook_secret',
			'woocommerce_webhook_status',
			'woocommerce_webhook_statuses',
			'woocommerce_webhook_topic',
			'woocommerce_webhook_updated',
		),
		'It fires inside or after the write, or it cannot fire before the write on a staged call.' => array(
			'action_scheduler_allowed_nested_schedule_classes',
			'action_scheduler_stored_action_class',
			'action_scheduler_stored_action_instance',
			'added_*_meta',
			'pre_as_schedule_single_action',
			'wc_object_cache_get_engine',
			'woocommerce_analytics_disable_action_scheduling',
			'woocommerce_cached_widget_id',
			'woocommerce_delete_shop_order_transients',
			'woocommerce_get_*_page_id',
			'woocommerce_get_items_key',
			'woocommerce_get_order_address',
			'woocommerce_order_refund_object_updated_props',
			'woocommerce_payment_complete',
			'woocommerce_update_order_refund',
			'woocommerce_update_user_last_active_threshold',
			'woocommerce_user_last_update_fields',
			'woocommerce_valid_webhook_events',
			'woocommerce_valid_webhook_resources',
			'woocommerce_webhook_process_delivery',
			'woocommerce_webhook_resource',
			'woocommerce_webhook_should_deliver',
			'woocommerce_webhook_topic_hooks',
		),
		'Order query hook: its only difference, the refund query of a new order, is removed in Core by PR 71475.' => array(
			'woocommerce_get_wp_query_args',
			'woocommerce_hpos_pre_query',
			'woocommerce_order_data_store_cpt_get_orders_query',
			'woocommerce_order_query',
			'woocommerce_order_query_args',
			'woocommerce_orders_table_datastore_get_orders_query',
			'woocommerce_orders_table_query_clauses',
			'woocommerce_orders_table_query_count_sql',
			'woocommerce_orders_table_query_sql',
		),
		'A real difference, but no listener acts on it, and the hook passes no staged object or child: left out by decision.' => array(
			'woocommerce_coupon_custom_discounts_array',
			'woocommerce_orders_table_datastore_should_save_after_meta_change',
		),
		'Its order on the staged path is fixed in Core by PR 71474.' => array(
			'woocommerce_order_edit_status',
		),
	);

	/**
	 * Hook call sites that an earlier row proved, so each one runs its second pass once.
	 *
	 * @var array<string, true>
	 */
	private static $proven_sites = array();

	/**
	 * Products of the orders, created once per test: the product of the order, and another one.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Every row: the stage method, as declaring class and method, and the state of the object it stages on.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function rows(): array {
		$rows   = array();
		$orders = array(
			'WC_Abstract_Order::stage_order_items_removal',
			'WC_Abstract_Order::stage_coupon',
			'WC_Abstract_Order::stage_coupon_removal',
			'WC_Abstract_Order::stage_recalculated_coupons',
			'WC_Abstract_Order::stage_product_item',
			'WC_Abstract_Order::stage_shipping_total',
			'WC_Abstract_Order::stage_taxes',
			'WC_Abstract_Order::stage_tax_items',
			'WC_Abstract_Order::stage_totals',
			'WC_Order::stage_payment_complete',
			'WC_Order::stage_status',
			'WC_Order::stage_order_note',
			'WC_Order_Refund::stage_coupon',
			'WC_Webhook::stage_delivery_result',
			'WC_Webhook::stage_delivery_ping',
			Notification::class . '::stage_verification_key',
			Notification::class . '::stage_unsubscribe_key',
		);
		foreach ( $orders as $method ) {
			foreach ( array( 'new', 'existing' ) as $state ) {
				$rows[ "$method $state" ] = array( $method, $state );
			}
		}
		$rows['WC_Abstract_Order::stage_order_items_removal synchronous store'] = array( 'WC_Abstract_Order::stage_order_items_removal', 'synchronous' );
		$rows['WC_Abstract_Order::stage_coupon guest over the usage limit']     = array( 'WC_Abstract_Order::stage_coupon', 'guest' );

		return $rows;
	}

	/**
	 * Data provider: every row.
	 *
	 * @return array
	 */
	public function provide_rows(): array {
		return self::rows();
	}

	/**
	 * @testdox Every WooCommerce hook that a stage call on a marked object fires before the write is checked or decided, and a listener without $staged on a checked hook refuses the call before it runs.
	 * @dataProvider provide_rows
	 *
	 * @param string $method Stage method.
	 * @param string $state  State of the object: new, existing, synchronous or guest.
	 */
	public function test_every_hook_of_a_staged_call_is_checked_or_decided( string $method, string $state ): void {
		list( $checked, $unaccounted ) = $this->record_fires( $method, $state );

		$this->assertSame( array(), $unaccounted, 'These hooks fire before the write on a staged call without a listener check' );

		foreach ( $checked as $site => $fire ) {
			if ( isset( self::$proven_sites[ $site ] ) ) {
				continue;
			}
			$this->assert_old_listener_refuses( $method, $state, $fire );
			self::$proven_sites[ $site ] = true;
		}
	}

	/**
	 * @testdox The rows stage through every public stage_*() method that a WC_Data class of WooCommerce declares.
	 */
	public function test_the_rows_cover_every_stage_method(): void {
		$declared = array();
		$root     = dirname( __DIR__, 4 );
		foreach ( array( 'includes', 'src' ) as $folder ) {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( "$root/$folder", FilesystemIterator::SKIP_DOTS ) );
			foreach ( $files as $file ) {
				$code = 'php' === $file->getExtension() ? file_get_contents( $file->getPathname() ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( false === strpos( (string) $code, 'public function stage_' ) || ! preg_match( '/^(?:abstract |final )*class (\w+)/m', $code, $class ) ) {
					continue;
				}
				$namespace  = preg_match( '/^namespace ([^;]+);/m', $code, $match ) ? $match[1] . '\\' : '';
				$reflection = new ReflectionClass( $namespace . $class[1] );
				foreach ( $reflection->getMethods( ReflectionMethod::IS_PUBLIC ) as $stage_method ) {
					if ( 0 === strpos( $stage_method->getName(), 'stage_' ) && $stage_method->class === $reflection->getName() && $reflection->isSubclassOf( WC_Data::class ) ) {
						$declared[] = $stage_method->class . '::' . $stage_method->getName();
					}
				}
			}
		}
		$staged = array_values( array_unique( array_column( self::rows(), 0 ) ) );
		sort( $declared );
		sort( $staged );

		$this->assertSame( $declared, $staged, 'Add a row to rows() and a case to create_row() for each new stage method' );
	}

	/**
	 * Run a row with a spy on all hooks until the write starts. Return the call sites of hooks fired with a listener
	 * check, and a message for each WooCommerce hook that is neither checked nor decided.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Object state.
	 * @return array{0: array<string, array{hook: string, count: int, index: int}>, 1: string[]}
	 */
	private function record_fires( string $method, string $state ): array {
		list( $subject, $stage ) = $this->create_row( $method, $state );
		$checked                 = array();
		$unaccounted             = array();
		$counts                  = array();
		$writing                 = false;
		$plugin_dir              = wp_normalize_path( dirname( __DIR__, 4 ) ) . '/';
		$spy                     = static function ( string $hook, ...$args ) use ( &$checked, &$unaccounted, &$counts, &$writing, $subject, $method, $state, $plugin_dir ) {
			if ( $writing || ( did_action( 'wc_staged_hooks_test_save' ) && ! $subject instanceof WC_Abstract_Order ) ) {
				return;
			}
			if ( preg_match( '/^woocommerce_before_\w+_object_save$/', $hook ) && ( $args[0] ?? null ) === $subject ) {
				$writing = true;
				return;
			}
			$counts[ $hook ] = ( $counts[ $hook ] ?? 0 ) + 1;

			$frames = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- The caller tells how the hook was fired.
			foreach ( $frames as $index => $frame ) {
				if ( ! in_array( $frame['function'], array( 'apply_filters', 'do_action', 'apply_filters_ref_array', 'do_action_ref_array' ), true ) ) {
					continue;
				}
				$caller = $frames[ $index + 1 ] ?? array();
				$file   = wp_normalize_path( $frame['file'] ?? '' );
				if ( in_array( $caller['function'] ?? '', array( 'do_staged_action', 'apply_staged_filters' ), true ) ) {
					$site             = $hook . ' ' . basename( $caller['file'] ?? '' ) . ':' . ( $caller['line'] ?? 0 );
					$checked[ $site ] = $checked[ $site ] ?? array(
						'hook'  => $hook,
						'count' => count( $args ),
						'index' => $counts[ $hook ],
					);
					return;
				}
				$location = str_replace( $plugin_dir, '', $file ) . ':' . ( $frame['line'] ?? 0 );
				$site     = $hook . ' ' . $location;
				if ( 0 !== strpos( $file, $plugin_dir ) || in_array( $caller['function'] ?? '', self::READ_METHODS, true ) ) {
					return;
				}
				if ( in_array( $hook, self::DEFERRED_CHECKED, true ) ) {
					$checked[ $site ] = $checked[ $site ] ?? array(
						'hook'  => $hook,
						'count' => count( $args ),
						'index' => $counts[ $hook ],
					);
					return;
				}
				if ( ! self::is_decided( $hook ) ) {
					$unaccounted[ $site ] = sprintf( '%s fired at %s before the write on a staged %s call (%s). Fire it with do_staged_action() or apply_staged_filters() when it passes the staged object or one of its children, or add it to DECIDED in this test with a reason.', $hook, $location, $method, $state );
				}
				return;
			}
		};

		add_action( 'all', $spy );
		try {
			$stage();
		} finally {
			remove_action( 'all', $spy );
		}

		return array( $checked, array_values( $unaccounted ) );
	}

	/**
	 * Whether a hook is in DECIDED.
	 *
	 * @param string $hook Hook name.
	 * @return bool
	 */
	private static function is_decided( string $hook ): bool {
		foreach ( self::DECIDED as $hooks ) {
			foreach ( $hooks as $pattern ) {
				if ( fnmatch( $pattern, $hook ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Run the row again on a new object, with a listener that does not accept $staged on the hook from the fire at
	 * the call site on. Assert the call is refused before that fire, with a message that names the hook.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Object state.
	 * @param array  $fire   Fire from record_fires().
	 */
	private function assert_old_listener_refuses( string $method, string $state, array $fire ): void {
		list( , $stage ) = $this->create_row( $method, $state );
		$hook            = $fire['hook'];
		$fired           = 0;
		$ran             = false;
		$listener        = static function ( ...$args ) use ( &$fired, &$ran, $fire ) {
			$ran = $ran || $fired >= $fire['index'];
			return $args[0] ?? null;
		};
		$spy             = static function ( string $current ) use ( &$fired, $hook, $listener, $fire ) {
			if ( $current === $hook && ++$fired === $fire['index'] - 1 ) {
				add_filter( $hook, $listener, PHP_INT_MAX, $fire['count'] - 1 );
			}
		};
		if ( 1 === $fire['index'] ) {
			add_filter( $hook, $listener, PHP_INT_MAX, $fire['count'] - 1 );
		}

		$refusal = null;
		add_action( 'all', $spy );
		try {
			$stage();
		} catch ( WC_Data_Exception $e ) {
			$refusal = $e;
		} finally {
			remove_action( 'all', $spy );
			remove_filter( $hook, $listener, PHP_INT_MAX );
		}

		$this->assertInstanceOf( WC_Data_Exception::class, $refusal, "$hook: a listener without \$staged did not refuse $method ($state)" );
		$this->assertStringContainsString( $hook . ' hook', $refusal->getMessage() );
		$this->assertFalse( $ran, "$hook: the listener ran" );
	}

	/**
	 * Create the object of a row and the callable that marks it as staged, stages on it and then saves.
	 *
	 * @param string $method Stage method.
	 * @param string $state  Object state.
	 * @return array{0: object, 1: callable}
	 */
	private function create_row( string $method, string $state ): array {
		list( $class, $name ) = explode( '::', $method );
		if ( 'WC_Webhook' === $class ) {
			return $this->create_webhook_row( $name, $state );
		}

		if ( Notification::class === $class ) {
			update_option( StockNotifications::ENABLE_OPTION_NAME, 'yes' );
			add_filter( 'woocommerce_data_stores', array( wc_get_container()->get( StockNotifications::class ), 'register_data_stores' ) );
			$notification = new Notification();
			$notification->set_product_id( WC_Helper_Product::create_simple_product()->get_id() );
			$notification->set_user_email( 'staged@example.com' );
			if ( 'existing' === $state ) {
				$notification->save();
			}
			return array(
				$notification,
				static function () use ( $notification, $name ) {
					$notification->mark_staged();
					$notification->$name();
					do_action( 'wc_staged_hooks_test_save' );
					$notification->save();
				},
			);
		}

		$order = $this->create_order( $state );
		if ( 'WC_Order_Refund' === $class ) {
			$refund = 'new' === $state ? new WC_Order_Refund() : wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => 1,
				)
			);
			return array(
				$refund,
				static function () use ( $refund ) {
					$refund->mark_staged();
					$refund->stage_coupon( 'staged_restricted' );
					$refund->save();
				},
			);
		}

		if ( 'synchronous' === $state ) {
			$cache = new ReflectionProperty( WC_Abstract_Order::class, 'data_store_supports_deferred_item_deletion' );
			$cache->setAccessible( true );
			$cache->setValue( $order, false );
		}

		$new_product = 'stage_product_item' === $name ? WC_Helper_Product::create_simple_product() : null;
		$calls       = array(
			'stage_order_items_removal'  => static fn() => $order->stage_order_items_removal( 'fee' ),
			'stage_coupon'               => static fn() => $order->stage_coupon(
				array(
					'new'   => 'staged_cart_rules',
					'guest' => 'staged_once',
				)[ $state ] ?? 'staged_product_rules'
			),
			'stage_coupon_removal'       => static fn() => $order->stage_coupon_removal( 'staged_custom' ),
			'stage_recalculated_coupons' => static fn() => $order->stage_recalculated_coupons(),
			'stage_product_item'         => static fn() => $order->stage_product_item( $new_product, 1, array( 'order' => $order ) ),
			'stage_shipping_total'       => static fn() => $order->stage_shipping_total(),
			'stage_taxes'                => static fn() => $order->stage_taxes(),
			'stage_tax_items'            => static fn() => $order->stage_tax_items(),
			'stage_totals'               => static fn() => $order->stage_totals(),
			'stage_payment_complete'     => static fn() => $order->stage_payment_complete( 'transaction' ),
			'stage_status'               => static fn() => $order->stage_status( OrderStatus::PROCESSING ),
			'stage_order_note'           => static fn() => $order->stage_order_note( 'Staged note' ),
		);

		return array(
			$order,
			static function () use ( $order, $calls, $name ) {
				$order->mark_staged();
				$calls[ $name ]();
				$order->save();
			},
		);
	}

	/**
	 * Create a webhook row. The webhook has six failed deliveries, so a failed result disables it.
	 *
	 * @param string $name  Stage method name.
	 * @param string $state Object state.
	 * @return array{0: object, 1: callable}
	 */
	private function create_webhook_row( string $name, string $state ): array {
		$webhook = new WC_Webhook();
		$webhook->set_delivery_url( 'https://example.com/hook' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_status( 'active' );
		$webhook->set_failure_count( 6 );
		if ( 'existing' === $state ) {
			$webhook->save();
		}
		$http = static fn() => array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);
		return array(
			$webhook,
			static function () use ( $webhook, $name, $http ) {
				add_filter( 'pre_http_request', $http );
				$webhook->mark_staged();
				try {
					'stage_delivery_ping' === $name ? $webhook->stage_delivery_ping() : $webhook->stage_delivery_result( 500 );
				} finally {
					remove_filter( 'pre_http_request', $http );
				}
				do_action( 'wc_staged_hooks_test_save' );
				$webhook->save();
			},
		);
	}

	/**
	 * Create an order with taxes and cost of goods on.
	 *
	 * - new: a new order with an unsaved product line, without coupons, fees or shipping.
	 * - existing and synchronous: a saved order with a customer, a product line, a shipping line, a negative fee and
	 *   two coupons, one of a custom product type, which then gets an unsaved product line and an unsaved fee.
	 * - guest: a saved guest order whose billing email used the coupon staged_once up to its limit.
	 *
	 * @param string $state Object state.
	 * @return WC_Order
	 */
	private function create_order( string $state ): WC_Order {
		if ( empty( $this->products ) ) {
			$this->create_fixtures();
		}
		list( $product, $other ) = $this->products;

		if ( 'new' === $state ) {
			$order = new WC_Order();
			$order->add_item( self::create_line( $product ) );
			return $order;
		}

		if ( 'guest' === $state ) {
			$order = WC_Helper_Order::create_order( 0, $product );
			$order->set_billing_email( 'guest@example.com' );
			$order->save();
			return wc_get_order( $order->get_id() );
		}

		$order = WC_Helper_Order::create_order( 1, $product );
		$fee   = new WC_Order_Item_Fee();
		$fee->set_total( '-5' );
		$order->add_item( $fee );
		$order->save();
		foreach ( array( 'staged_custom', 'staged_restricted' ) as $code ) {
			$order->apply_coupon( $code );
		}

		$order = wc_get_order( $order->get_id() );
		$order->add_item( self::create_line( $other ) );
		$unsaved_fee = new WC_Order_Item_Fee();
		$unsaved_fee->set_total( '-1' );
		$order->add_item( $unsaved_fee );

		return $order;
	}

	/**
	 * Create an unsaved product line.
	 *
	 * @param WC_Product $product Product.
	 * @return WC_Order_Item_Product
	 */
	private static function create_line( WC_Product $product ): WC_Order_Item_Product {
		$line = new WC_Order_Item_Product();
		$line->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 10,
				'total'    => 10,
			)
		);

		return $line;
	}

	/**
	 * Turn taxes and cost of goods on, register a custom product coupon type and a listener that makes
	 * woocommerce_get_product_from_item fire, and create the products and coupons of the orders.
	 */
	private function create_fixtures(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_feature_cost_of_goods_sold_enabled', 'yes' );
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Tax',
				'tax_rate_shipping' => '1',
			)
		);
		add_filter( 'woocommerce_coupon_discount_types', static fn( $types ) => $types + array( 'staged_custom' => 'Staged custom' ) );
		add_filter( 'woocommerce_product_coupon_types', static fn( $types ) => array_merge( $types, array( 'staged_custom' ) ) );
		add_filter( 'woocommerce_get_product_from_item', static fn( $product ) => $product );

		$product  = WC_Helper_Product::create_simple_product();
		$category = wp_insert_term( 'Staged ' . wp_generate_password( 6, false ), 'product_cat' );
		wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );
		$other = WC_Helper_Product::create_simple_product();
		WC_Helper_Coupon::create_coupon( 'staged_custom', array( 'discount_type' => 'staged_custom' ) );
		WC_Helper_Coupon::create_coupon(
			'staged_restricted',
			array(
				'discount_type'          => 'fixed_product',
				'limit_usage_to_x_items' => '1',
			)
		);
		WC_Helper_Coupon::create_coupon(
			'staged_product_rules',
			array(
				'discount_type'        => 'percent',
				'product_ids'          => (string) $product->get_id(),
				'product_categories'   => array( $category['term_id'] ),
				'date_expires'         => time() + DAY_IN_SECONDS,
				'usage_limit_per_user' => '5',
			)
		);
		WC_Helper_Coupon::create_coupon(
			'staged_cart_rules',
			array(
				'discount_type'              => 'fixed_cart',
				'exclude_sale_items'         => 'yes',
				'exclude_product_ids'        => (string) $other->get_id(),
				'exclude_product_categories' => array( (int) get_option( 'default_product_cat' ) ),
			)
		);
		$once = WC_Helper_Coupon::create_coupon( 'staged_once', array( 'usage_limit_per_user' => '1' ) );
		$once->increase_usage_count( 'guest@example.com' );

		$this->products = array( $product, $other );
	}
}
