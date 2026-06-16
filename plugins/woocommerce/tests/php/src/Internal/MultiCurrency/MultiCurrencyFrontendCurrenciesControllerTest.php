<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyFrontendCurrenciesController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyOrderContextService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyFrontendCurrenciesController class.
 */
class MultiCurrencyFrontendCurrenciesControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the frontend currency controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'woocommerce_currency',
		'wc_get_price_decimals',
		'wc_get_price_decimal_separator',
		'wc_get_price_thousand_separator',
		'woocommerce_price_format',
		'option_woocommerce_currency_pos',
		'woocommerce_order_get_total',
		'woocommerce_get_formatted_order_total',
		'woocommerce_thankyou_order_id',
		'woocommerce_cart_hash',
		'woocommerce_shipping_method_add_rate_args',
		'before_woocommerce_pay',
		'woocommerce_account_view-order_endpoint',
		'woocommerce_order_class',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should not register frontend currency hooks while plugin multi-currency owns the runtime.
	 */
	public function test_does_not_register_when_plugin_multi_currency_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_currency', array( $sut, 'get_woocommerce_currency' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_cart_hash', array( $sut, 'add_currency_to_cart_hash' ) ) );
	}

	/**
	 * @testdox Should not register frontend currency hooks when no multi-currency runtime owns the site.
	 */
	public function test_does_not_register_when_no_multi_currency_runtime_owns_site(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_NONE );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_currency', array( $sut, 'get_woocommerce_currency' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_cart_hash', array( $sut, 'add_currency_to_cart_hash' ) ) );
	}

	/**
	 * @testdox Should register frontend currency hooks when core multi-currency owns the runtime.
	 */
	public function test_registers_frontend_currency_hooks_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 900, has_filter( 'woocommerce_currency', array( $sut, 'get_woocommerce_currency' ) ) );
		$this->assertSame( 900, has_filter( 'wc_get_price_decimals', array( $sut, 'get_price_decimals' ) ) );
		$this->assertSame( 900, has_filter( 'wc_get_price_decimal_separator', array( $sut, 'get_price_decimal_separator' ) ) );
		$this->assertSame( 900, has_filter( 'wc_get_price_thousand_separator', array( $sut, 'get_price_thousand_separator' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_price_format', array( $sut, 'get_woocommerce_price_format' ) ) );
		$this->assertSame( 900, has_filter( 'option_woocommerce_currency_pos', array( $sut, 'get_woocommerce_currency_pos' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_cart_hash', array( $sut, 'add_currency_to_cart_hash' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_order_get_total', array( $sut, 'maybe_init_order_currency_from_order_total_prop' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_get_formatted_order_total', array( $sut, 'maybe_clear_order_currency_after_formatted_order_total' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_shipping_method_add_rate_args', array( $sut, 'fix_price_decimals_for_shipping_rates' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_thankyou_order_id', array( $sut, 'init_order_currency' ) ) );
		$this->assertSame( 10, has_action( 'before_woocommerce_pay', array( $sut, 'init_order_currency_from_query_vars' ) ) );
		$this->assertSame( 9, has_action( 'woocommerce_account_view-order_endpoint', array( $sut, 'init_order_currency' ) ) );
	}

	/**
	 * @testdox Should register only always-on currency hooks in blocked request context.
	 */
	public function test_registers_only_always_on_currency_hooks_in_blocked_request_context(): void {
		$sut = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$this->create_request_context( false )
		);

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_currency', array( $sut, 'get_woocommerce_currency' ) ) );
		$this->assertFalse( has_filter( 'wc_get_price_decimals', array( $sut, 'get_price_decimals' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_order_get_total', array( $sut, 'maybe_init_order_currency_from_order_total_prop' ) ) );
		$this->assertFalse( has_action( 'before_woocommerce_pay', array( $sut, 'init_order_currency_from_query_vars' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_thankyou_order_id', array( $sut, 'init_order_currency' ) ) );
		$this->assertSame( 9, has_action( 'woocommerce_account_view-order_endpoint', array( $sut, 'init_order_currency' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_cart_hash', array( $sut, 'add_currency_to_cart_hash' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_shipping_method_add_rate_args', array( $sut, 'fix_price_decimals_for_shipping_rates' ) ) );
	}

	/**
	 * @testdox Should delegate frontend formatting callbacks to the projection service.
	 */
	public function test_delegates_frontend_formatting_callbacks_to_projection_service(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->register();

		$this->assertSame( 'GBP', $sut->get_woocommerce_currency( 'USD' ) );
		$this->assertSame( 2, $sut->get_price_decimals( 2 ) );
		$this->assertSame( ',', $sut->get_price_decimal_separator( '.' ) );
		$this->assertSame( '.', $sut->get_price_thousand_separator( ',' ) );
		$this->assertSame( '%2$s&nbsp;%1$s', $sut->get_woocommerce_price_format( '%1$s%2$s' ) );
		$this->assertSame( 'right_space', $sut->get_woocommerce_currency_pos( 'left' ) );
		$this->assertSame( 'base-hash-GBP', $sut->add_currency_to_cart_hash( 'base-hash' ) );
	}

	/**
	 * @testdox Should leave deferred order-context callbacks as safe pass-throughs.
	 */
	public function test_deferred_order_context_callbacks_are_safe_pass_throughs(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->register();

		$this->assertSame( 123, $sut->init_order_currency( 123 ) );
		$this->assertSame( 42.0, $sut->maybe_init_order_currency_from_order_total_prop( 42.0, null ) );
		$this->assertSame( '$42.00', $sut->maybe_clear_order_currency_after_formatted_order_total( '$42.00', null, '', false ) );
		$this->assertNull( $sut->init_order_currency_from_query_vars(), 'Pay-for-order action callback should not return a value.' );
	}

	/**
	 * @testdox Should initialize order currency from order ID for formatting.
	 */
	public function test_initializes_order_currency_from_order_id_for_formatting(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->assertSame( $order->get_id(), $sut->init_order_currency( $order->get_id() ) );
		$this->assertSame( 'JPY', $sut->get_order_currency() );
		$this->assertSame( 'JPY', $sut->get_woocommerce_currency( 'USD' ) );
		$this->assertSame( 0, $sut->get_price_decimals( 2 ) );
	}

	/**
	 * @testdox Should fall back to selected currency when order lookup fails.
	 */
	public function test_initializes_order_currency_to_selected_currency_when_order_lookup_fails(): void {
		$missing_order_id = PHP_INT_MAX;
		$sut              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->assertSame( $missing_order_id, $sut->init_order_currency( $missing_order_id ) );

		$this->assertSame( 'GBP', $sut->get_order_currency() );
		$this->assertSame( 'GBP', $sut->get_woocommerce_currency( 'USD' ) );
	}

	/**
	 * @testdox Should remove frontend currency filters while resolving order IDs.
	 */
	public function test_removes_frontend_currency_filters_while_resolving_order_ids(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		wc_get_container()->get( OrderCache::class )->remove( $order->get_id() );

		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->register();

		$observed_filters = null;
		add_filter(
			'woocommerce_order_class',
			function ( $class_name ) use ( $sut, &$observed_filters ) {
				$observed_filters = array(
					'wc_get_price_decimals'           => has_filter( 'wc_get_price_decimals', array( $sut, 'get_price_decimals' ) ),
					'wc_get_price_decimal_separator'  => has_filter( 'wc_get_price_decimal_separator', array( $sut, 'get_price_decimal_separator' ) ),
					'wc_get_price_thousand_separator' => has_filter( 'wc_get_price_thousand_separator', array( $sut, 'get_price_thousand_separator' ) ),
					'woocommerce_price_format'        => has_filter( 'woocommerce_price_format', array( $sut, 'get_woocommerce_price_format' ) ),
				);

				return $class_name;
			}
		);

		$this->assertSame( $order->get_id(), $sut->init_order_currency( $order->get_id() ) );

		$this->assertSame(
			array(
				'wc_get_price_decimals'           => false,
				'wc_get_price_decimal_separator'  => false,
				'wc_get_price_thousand_separator' => false,
				'woocommerce_price_format'        => false,
			),
			$observed_filters
		);
		$this->assertSame( 900, has_filter( 'wc_get_price_decimals', array( $sut, 'get_price_decimals' ) ) );
		$this->assertSame( 900, has_filter( 'wc_get_price_decimal_separator', array( $sut, 'get_price_decimal_separator' ) ) );
		$this->assertSame( 900, has_filter( 'wc_get_price_thousand_separator', array( $sut, 'get_price_thousand_separator' ) ) );
		$this->assertSame( 900, has_filter( 'woocommerce_price_format', array( $sut, 'get_woocommerce_price_format' ) ) );
	}

	/**
	 * @testdox Should clear order currency after formatted order total.
	 */
	public function test_clears_order_currency_after_formatted_order_total(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		$sut = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			null,
			$this->create_order_context_service( true )
		);

		$sut->init_order_currency( $order );
		$this->assertSame( '$42.00', $sut->maybe_clear_order_currency_after_formatted_order_total( '$42.00', $order, '', false ) );

		$this->assertNull( $sut->get_order_currency() );
		$this->assertSame( 'GBP', $sut->get_woocommerce_currency( 'USD' ) );
	}

	/**
	 * @testdox Should initialize order currency from order total in matching order context.
	 */
	public function test_initializes_order_currency_from_order_total_in_matching_order_context(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		$sut = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			null,
			$this->create_order_context_service( true )
		);

		$this->assertSame( 42.0, $sut->maybe_init_order_currency_from_order_total_prop( 42.0, $order ) );

		$this->assertSame( 'JPY', $sut->get_order_currency() );
		$this->assertSame( 'JPY', $sut->get_woocommerce_currency( 'USD' ) );
	}

	/**
	 * @testdox Should not initialize order currency from order total outside order context.
	 */
	public function test_does_not_initialize_order_currency_from_order_total_outside_order_context(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		$sut = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			null,
			$this->create_order_context_service( false )
		);

		$this->assertSame( 42.0, $sut->maybe_init_order_currency_from_order_total_prop( 42.0, $order ) );

		$this->assertNull( $sut->get_order_currency() );
		$this->assertSame( 'GBP', $sut->get_woocommerce_currency( 'USD' ) );
	}

	/**
	 * @testdox Should keep order currency after formatted order total outside order context.
	 */
	public function test_keeps_order_currency_after_formatted_order_total_outside_order_context(): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		$sut = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			null,
			$this->create_order_context_service( false )
		);

		$sut->init_order_currency( $order );
		$this->assertSame( '$42.00', $sut->maybe_clear_order_currency_after_formatted_order_total( '$42.00', $order, '', false ) );

		$this->assertSame( 'JPY', $sut->get_order_currency() );
	}

	/**
	 * @testdox Should initialize order currency from order query vars.
	 *
	 * @dataProvider order_query_var_provider
	 *
	 * @param string $query_var Query var name.
	 */
	public function test_initializes_order_currency_from_order_query_vars( string $query_var ): void {
		$order = wc_create_order();
		$order->set_currency( 'JPY' );
		$order->save();
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->with_query_vars(
			array( $query_var => $order->get_id() ),
			function () use ( $sut, $order ): void {
				$sut->init_order_currency_from_query_vars();

				$this->assertSame( 'JPY', $sut->get_order_currency() );
				$this->assertSame( 'JPY', $sut->get_woocommerce_currency( 'USD' ) );
				$this->assertSame( 0, $sut->get_price_decimals( 2 ) );
				$this->assertSame( $order->get_id(), $sut->init_order_currency( $order->get_id() ) );
			}
		);
	}

	/**
	 * Data provider for supported order query vars.
	 *
	 * @return array<string, array{string}>
	 */
	public function order_query_var_provider(): array {
		return array(
			'order-pay'      => array( 'order-pay' ),
			'order-received' => array( 'order-received' ),
			'view-order'     => array( 'view-order' ),
		);
	}

	/**
	 * @testdox Should prefer order-pay before other order query vars.
	 */
	public function test_prefers_order_pay_before_other_order_query_vars(): void {
		$order_pay = wc_create_order();
		$order_pay->set_currency( 'JPY' );
		$order_pay->save();
		$order_received = wc_create_order();
		$order_received->set_currency( 'EUR' );
		$order_received->save();
		$view_order = wc_create_order();
		$view_order->set_currency( 'AUD' );
		$view_order->save();
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->with_query_vars(
			array(
				'order-pay'      => $order_pay->get_id(),
				'order-received' => $order_received->get_id(),
				'view-order'     => $view_order->get_id(),
			),
			function () use ( $sut ): void {
				$sut->init_order_currency_from_query_vars();

				$this->assertSame( 'JPY', $sut->get_order_currency() );
			}
		);
	}

	/**
	 * @testdox Should ignore empty order query vars.
	 */
	public function test_ignores_empty_order_query_vars(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->with_query_vars(
			array(
				'order-pay'      => '',
				'order-received' => 0,
				'view-order'     => false,
			),
			function () use ( $sut ): void {
				$sut->init_order_currency_from_query_vars();

				$this->assertNull( $sut->get_order_currency() );
				$this->assertSame( 'GBP', $sut->get_woocommerce_currency( 'USD' ) );
			}
		);
	}

	/**
	 * @testdox Should set store currency decimals for shipping rate args.
	 */
	public function test_sets_store_currency_decimals_for_shipping_rate_args(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->register();

		$this->assertSame(
			array(
				'cost'           => '10.00',
				'price_decimals' => 2,
			),
			$sut->fix_price_decimals_for_shipping_rates( array( 'cost' => '10.00' ), null )
		);
	}

	/**
	 * Create a frontend currencies controller with a static runtime owner.
	 *
	 * @param string                                $owner                 Runtime owner.
	 * @param MultiCurrencyRequestContext|null      $request_context       Request context.
	 * @param MultiCurrencyOrderContextService|null $order_context_service Order context service.
	 * @return MultiCurrencyFrontendCurrenciesController
	 */
	private function create_controller(
		string $owner,
		?MultiCurrencyRequestContext $request_context = null,
		?MultiCurrencyOrderContextService $order_context_service = null
	): MultiCurrencyFrontendCurrenciesController {
		$controller = new MultiCurrencyFrontendCurrenciesController();
		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class ),
			wc_get_container()->get( MultiCurrencyRuntimeServiceFactory::class )
		);
		$controller->set_frontend_projection_service( $this->create_projection_service() );
		if ( null !== $request_context && method_exists( $controller, 'set_request_context' ) ) {
			$controller->set_request_context( $request_context );
		}
		if ( null !== $order_context_service && method_exists( $controller, 'set_order_context_service' ) ) {
			$controller->set_order_context_service( $order_context_service );
		}

		return $controller;
	}

	/**
	 * Run a callback with temporary WordPress query vars.
	 *
	 * @param array<string, mixed> $query_vars Query vars.
	 * @param callable             $callback   Callback to run.
	 */
	private function with_query_vars( array $query_vars, callable $callback ): void {
		global $wp;

		$this->assertInstanceOf( \WP::class, $wp );

		$previous_query_vars = $wp->query_vars;
		$wp->query_vars      = $query_vars;

		try {
			$callback();
		} finally {
			$wp->query_vars = $previous_query_vars;
		}
	}

	/**
	 * Create a deterministic order context service.
	 *
	 * @param bool $should_use_order_currency Whether order currency should be used.
	 * @return MultiCurrencyOrderContextService
	 */
	private function create_order_context_service( bool $should_use_order_currency ): MultiCurrencyOrderContextService {
		return new class( $should_use_order_currency ) extends MultiCurrencyOrderContextService {
			/**
			 * Whether order currency should be used.
			 *
			 * @var bool
			 */
			private bool $should_use_order_currency;

			/**
			 * Constructor.
			 *
			 * @param bool $should_use_order_currency Whether order currency should be used.
			 */
			public function __construct( bool $should_use_order_currency ) {
				$this->should_use_order_currency = $should_use_order_currency;
			}

			/**
			 * Tell whether order currency should be used.
			 *
			 * @return bool
			 */
			public function should_use_order_currency(): bool {
				return $this->should_use_order_currency;
			}
		};
	}

	/**
	 * Create a static multi-currency runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register price/currency hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}

	/**
	 * Create a deterministic frontend projection service.
	 *
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function create_projection_service(): MultiCurrencyFrontendProjectionService {
		return new class() extends MultiCurrencyFrontendProjectionService {
			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Project the WooCommerce currency code.
			 *
			 * @param string|null $order_currency Optional order currency override.
			 * @return string
			 */
			public function get_woocommerce_currency( ?string $order_currency = null ): string {
				return $order_currency ?? 'GBP';
			}

			/**
			 * Project the number of price decimals.
			 *
			 * @param int         $decimals       Original decimal count.
			 * @param string|null $order_currency Optional order currency override.
			 * @return int
			 */
			public function get_price_decimals( int $decimals, ?string $order_currency = null ): int {
				return 'JPY' === $order_currency ? 0 : 2;
			}

			/**
			 * Project the price decimal separator.
			 *
			 * @param string      $separator      Original separator.
			 * @param string|null $order_currency Optional order currency override.
			 * @return string
			 */
			public function get_price_decimal_separator( string $separator, ?string $order_currency = null ): string {
				return ',';
			}

			/**
			 * Project the price thousand separator.
			 *
			 * @param string      $separator      Original separator.
			 * @param string|null $order_currency Optional order currency override.
			 * @return string
			 */
			public function get_price_thousand_separator( string $separator, ?string $order_currency = null ): string {
				return '.';
			}

			/**
			 * Project the WooCommerce price format.
			 *
			 * @param string      $format         Original price format.
			 * @param string|null $order_currency Optional order currency override.
			 * @return string
			 */
			public function get_woocommerce_price_format( string $format, ?string $order_currency = null ): string {
				return '%2$s&nbsp;%1$s';
			}

			/**
			 * Project the WooCommerce currency position option.
			 *
			 * @param string      $position       Original currency position.
			 * @param string|null $order_currency Optional order currency override.
			 * @return string
			 */
			public function get_woocommerce_currency_pos( string $position, ?string $order_currency = null ): string {
				return 'right_space';
			}

			/**
			 * Project the store/default currency decimal count.
			 *
			 * @return int
			 */
			public function get_store_currency_decimals(): int {
				return 2;
			}

			/**
			 * Project a cart hash varied by selected currency and rate.
			 *
			 * @param string $hash Original cart hash.
			 * @return string
			 */
			public function add_currency_to_cart_hash( string $hash ): string {
				return $hash . '-GBP';
			}
		};
	}

	/**
	 * Create a request context test double.
	 *
	 * @param bool $should_register_frontend_hooks Whether frontend hooks should register.
	 * @return MultiCurrencyRequestContext
	 */
	private function create_request_context( bool $should_register_frontend_hooks ): MultiCurrencyRequestContext {
		return new class( $should_register_frontend_hooks ) extends MultiCurrencyRequestContext {
			/**
			 * Whether frontend hooks should register.
			 *
			 * @var bool
			 */
			private bool $should_register_frontend_hooks;

			/**
			 * Constructor.
			 *
			 * @param bool $should_register_frontend_hooks Whether frontend hooks should register.
			 */
			public function __construct( bool $should_register_frontend_hooks ) {
				$this->should_register_frontend_hooks = $should_register_frontend_hooks;
			}

			/**
			 * Tell whether frontend hooks should register.
			 *
			 * @return bool
			 */
			public function should_register_frontend_hooks(): bool {
				return $this->should_register_frontend_hooks;
			}
		};
	}
}
