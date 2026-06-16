<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyAsyncPriceRendererController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAsyncPriceRendererController class.
 */
class MultiCurrencyAsyncPriceRendererControllerTest extends WC_Unit_Test_Case {

	private const SCRIPT_HANDLE = 'wcpay-multi-currency-async-renderer';

	/**
	 * Hooks touched by the controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'wc_price',
		'woocommerce_format_sale_price',
		'woocommerce_format_price_range',
		'wp_enqueue_scripts',
		'wcpay_multi_currency_async_price_type',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		unset( $_GET['currency'] );
		delete_option( 'wcpay_multi_currency_enable_auto_currency' );

		wp_dequeue_script( self::SCRIPT_HANDLE );
		wp_deregister_script( self::SCRIPT_HANDLE );
		wp_dequeue_style( self::SCRIPT_HANDLE );
		wp_deregister_style( self::SCRIPT_HANDLE );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register async renderer hooks when plugin owns runtime.
	 */
	public function test_does_not_register_hooks_when_plugin_owns_runtime(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assert_hooks_not_registered( $sut );
	}

	/**
	 * @testdox Should register async renderer hooks when core owns active cache mode.
	 */
	public function test_registers_hooks_when_core_owns_active_cache_mode(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 999, has_filter( 'wc_price', array( $sut, 'handle_wc_price' ) ) );
		$this->assertSame( 999, has_filter( 'woocommerce_format_sale_price', array( $sut, 'handle_woocommerce_format_sale_price' ) ) );
		$this->assertSame( 999, has_filter( 'woocommerce_format_price_range', array( $sut, 'handle_woocommerce_format_price_range' ) ) );
		$this->assertSame( 10, has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * @testdox Should not register async renderer hooks when activation is blocked.
	 *
	 * @dataProvider registration_blocker_data
	 *
	 * @param bool $cache_optimized_mode Whether cache-optimized mode is active.
	 * @param bool $frontend_request     Whether this is a frontend request.
	 * @param bool $store_api_request    Whether this is a Store API request.
	 * @param bool $admin_api_request    Whether this is an admin API request.
	 * @param bool $active_session       Whether a WooCommerce session is active.
	 * @param bool $auto_enabled         Whether automatic currency switching is enabled.
	 * @param bool $pending_currency     Whether the request has a pending explicit currency switch.
	 */
	public function test_does_not_register_hooks_when_activation_is_blocked(
		bool $cache_optimized_mode,
		bool $frontend_request,
		bool $store_api_request,
		bool $admin_api_request,
		bool $active_session,
		bool $auto_enabled,
		bool $pending_currency
	): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', $auto_enabled ? 'yes' : 'no' );

		if ( $pending_currency ) {
			$_GET['currency'] = 'EUR';
		}

		$sut = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$cache_optimized_mode,
			$frontend_request,
			$store_api_request,
			$admin_api_request,
			$active_session
		);

		$sut->register();

		$this->assert_hooks_not_registered( $sut );
	}

	/**
	 * @testdox Should wrap prices with async skeleton markup.
	 */
	public function test_wraps_prices_with_async_skeleton_markup(): void {
		$sut = $this->create_controller();

		add_filter(
			'wcpay_multi_currency_async_price_type',
			static function ( string $price_type, $price, array $args ): string {
				return 'yes' === ( $args['shipping'] ?? 'no' ) && '25' === (string) $price ? 'shipping' : $price_type;
			},
			10,
			3
		);

		$result = $sut->handle_wc_price(
			'<span class="woocommerce-Price-amount amount"><bdi>$25.00</bdi></span>',
			'25',
			array( 'shipping' => 'yes' ),
			'25.00',
			'25'
		);

		$this->assertStringContainsString( 'class="woocommerce-Price-amount amount wcpay-async-price"', $result );
		$this->assertStringContainsString( 'data-wcpay-price="25.00"', $result );
		$this->assertStringContainsString( 'data-wcpay-price-type="shipping"', $result );
		$this->assertStringContainsString( '<bdi class="wcpay-price-skeleton"></bdi>', $result );
		$this->assertStringContainsString( '<span class="screen-reader-text wcpay-price-placeholder">', $result );
		$this->assertStringContainsString( '$25.00', $result );
	}

	/**
	 * @testdox Should annotate sale and range screen reader text.
	 */
	public function test_annotates_sale_and_range_screen_reader_text(): void {
		$sut        = $this->create_controller();
		$sale_html  = '<span class="screen-reader-text">Original price was: $50.00.</span>'
			. '<span class="screen-reader-text">Current price is: $35.00.</span>';
		$range_html = '<span class="screen-reader-text">Price range: $10.00 through $30.00</span>';

		$sale_result  = $sut->handle_woocommerce_format_sale_price( $sale_html, '50', '35' );
		$range_result = $sut->handle_woocommerce_format_price_range( $range_html, '10', '30' );

		$this->assertStringContainsString( 'data-wcpay-sr-type="sale_original"', $sale_result );
		$this->assertStringContainsString( 'data-wcpay-sr-price="50"', $sale_result );
		$this->assertStringContainsString( 'data-wcpay-sr-type="sale_current"', $sale_result );
		$this->assertStringContainsString( 'data-wcpay-sr-price="35"', $sale_result );
		$this->assertStringContainsString( 'data-wcpay-sr-type="range"', $range_result );
		$this->assertStringContainsString( 'data-wcpay-sr-price-from="10"', $range_result );
		$this->assertStringContainsString( 'data-wcpay-sr-price-to="30"', $range_result );
	}

	/**
	 * @testdox Should enqueue async renderer assets and localized config.
	 */
	public function test_enqueues_async_renderer_assets_and_localized_config(): void {
		$sut = $this->create_controller();
		$sut->set_asset_url_resolver(
			static function ( string $path ): string {
				return 'https://example.test/wp-content/plugins/woocommerce/' . $path;
			}
		);
		$sut->set_asset_version_resolver(
			static function (): string {
				return '1.2.3';
			}
		);

		$sut->handle_wp_enqueue_scripts();

		$script = wp_scripts()->registered[ self::SCRIPT_HANDLE ] ?? null;
		$style  = wp_styles()->registered[ self::SCRIPT_HANDLE ] ?? null;
		$data   = wp_scripts()->get_data( self::SCRIPT_HANDLE, 'data' );

		$this->assertNotNull( $script );
		$this->assertSame( 'https://example.test/wp-content/plugins/woocommerce/assets/js/frontend/multi-currency-async-renderer.min.js', $script->src );
		$this->assertTrue( wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) );
		$this->assertIsString( $data );
		$this->assertStringContainsString( 'wcpayAsyncPriceConfig', $data );
		$this->assertStringContainsString( 'wc/v3/payments/multi-currency/public/config', $data );
		$this->assertNotNull( $style );
		$this->assertSame( 'https://example.test/wp-content/plugins/woocommerce/assets/css/multi-currency-async-renderer.css', $style->src );
		$this->assertSame( '1.2.3', $style->ver );
		$this->assertTrue( wp_style_is( self::SCRIPT_HANDLE, 'enqueued' ) );
	}

	/**
	 * Data provider for registration blockers.
	 *
	 * @return array<string,array{bool,bool,bool,bool,bool,bool,bool}>
	 */
	public function registration_blocker_data(): array {
		return array(
			'inactive cache mode'     => array( false, true, false, false, false, true, false ),
			'non-frontend request'    => array( true, false, false, false, false, true, false ),
			'Store API request'       => array( true, true, true, false, false, true, false ),
			'admin API request'       => array( true, true, false, true, false, true, false ),
			'active session'          => array( true, true, false, false, true, true, false ),
			'auto switching disabled' => array( true, true, false, false, false, false, false ),
			'pending currency switch' => array( true, true, false, false, false, true, true ),
		);
	}

	/**
	 * Assert controller hooks are not registered.
	 *
	 * @param MultiCurrencyAsyncPriceRendererController $sut System under test.
	 */
	private function assert_hooks_not_registered( MultiCurrencyAsyncPriceRendererController $sut ): void {
		$this->assertFalse( has_filter( 'wc_price', array( $sut, 'handle_wc_price' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_format_sale_price', array( $sut, 'handle_woocommerce_format_sale_price' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_format_price_range', array( $sut, 'handle_woocommerce_format_price_range' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * Create an async price renderer controller.
	 *
	 * @param string $owner                Runtime owner.
	 * @param bool   $cache_optimized_mode Whether cache-optimized mode is active.
	 * @param bool   $frontend_request     Whether this is a frontend request.
	 * @param bool   $store_api_request    Whether this is a Store API request.
	 * @param bool   $admin_api_request    Whether this is an admin API request.
	 * @param bool   $active_session       Whether a WooCommerce session is active.
	 * @return MultiCurrencyAsyncPriceRendererController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $cache_optimized_mode = true,
		bool $frontend_request = true,
		bool $store_api_request = false,
		bool $admin_api_request = false,
		bool $active_session = false
	): MultiCurrencyAsyncPriceRendererController {
		$controller = new MultiCurrencyAsyncPriceRendererController();
		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class ),
			wc_get_container()->get( MultiCurrencyRuntimeServiceFactory::class )
		);
		$controller->set_frontend_projection_service( $this->create_frontend_projection_service( $cache_optimized_mode ) );
		$controller->set_request_context( $this->create_request_context( $frontend_request, $store_api_request, $admin_api_request ) );
		$controller->set_active_session_resolver(
			static function () use ( $active_session ): bool {
				return $active_session;
			}
		);

		return $controller;
	}

	/**
	 * Create a static runtime arbiter.
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
			 * Tell whether core should register.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}

	/**
	 * Create a frontend projection service test double.
	 *
	 * @param bool $cache_optimized_mode Whether cache-optimized mode is active.
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function create_frontend_projection_service( bool $cache_optimized_mode ): MultiCurrencyFrontendProjectionService {
		return new class( $cache_optimized_mode ) extends MultiCurrencyFrontendProjectionService {
			/**
			 * Whether cache-optimized mode is active.
			 *
			 * @var bool
			 */
			private bool $cache_optimized_mode;

			/**
			 * Constructor.
			 *
			 * @param bool $cache_optimized_mode Whether cache-optimized mode is active.
			 */
			public function __construct( bool $cache_optimized_mode ) {
				$this->cache_optimized_mode = $cache_optimized_mode;
			}

			/**
			 * Tell whether cache-optimized rendering is active.
			 *
			 * @return bool
			 */
			public function is_cache_optimized_mode(): bool {
				return $this->cache_optimized_mode;
			}
		};
	}

	/**
	 * Create a request context test double.
	 *
	 * @param bool $frontend_request  Whether this is a frontend request.
	 * @param bool $store_api_request Whether this is a Store API request.
	 * @param bool $admin_api_request Whether this is an admin API request.
	 * @return MultiCurrencyRequestContext
	 */
	private function create_request_context( bool $frontend_request, bool $store_api_request, bool $admin_api_request ): MultiCurrencyRequestContext {
		return new class( $frontend_request, $store_api_request, $admin_api_request ) extends MultiCurrencyRequestContext {
			/**
			 * Whether this is a frontend request.
			 *
			 * @var bool
			 */
			private bool $frontend_request;

			/**
			 * Whether this is a Store API request.
			 *
			 * @var bool
			 */
			private bool $store_api_request;

			/**
			 * Whether this is an admin API request.
			 *
			 * @var bool
			 */
			private bool $admin_api_request;

			/**
			 * Constructor.
			 *
			 * @param bool $frontend_request  Whether this is a frontend request.
			 * @param bool $store_api_request Whether this is a Store API request.
			 * @param bool $admin_api_request Whether this is an admin API request.
			 */
			public function __construct( bool $frontend_request, bool $store_api_request, bool $admin_api_request ) {
				$this->frontend_request  = $frontend_request;
				$this->store_api_request = $store_api_request;
				$this->admin_api_request = $admin_api_request;
			}

			/**
			 * Tell whether frontend hooks should register.
			 *
			 * @return bool
			 */
			public function should_register_frontend_hooks(): bool {
				return $this->frontend_request;
			}

			/**
			 * Tell whether this is a Store API request.
			 *
			 * @return bool
			 */
			public function is_store_api_request(): bool {
				return $this->store_api_request;
			}

			/**
			 * Tell whether this is an admin API request.
			 *
			 * @return bool
			 */
			public function is_admin_api_request(): bool {
				return $this->admin_api_request;
			}
		};
	}
}
