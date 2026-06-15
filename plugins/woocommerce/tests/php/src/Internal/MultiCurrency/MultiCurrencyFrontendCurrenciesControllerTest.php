<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyFrontendCurrenciesController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
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
	 * @testdox Should delegate frontend formatting callbacks to the projection service.
	 */
	public function test_delegates_frontend_formatting_callbacks_to_projection_service(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->register();

		$this->assertSame( 'GBP', $sut->get_woocommerce_currency( 'USD' ) );
		$this->assertSame( 0, $sut->get_price_decimals( 2 ) );
		$this->assertSame( ',', $sut->get_price_decimal_separator( '.' ) );
		$this->assertSame( '.', $sut->get_price_thousand_separator( ',' ) );
		$this->assertSame( '%2$s&nbsp;%1$s', $sut->get_woocommerce_price_format( '%1$s%2$s' ) );
		$this->assertSame( 'right_space', $sut->get_woocommerce_currency_pos( 'left' ) );
		$this->assertSame( 'base-hash-GBP', $sut->add_currency_to_cart_hash( 'base-hash' ) );
	}

	/**
	 * @testdox Should keep order-context hooks as pass-throughs until later B2 slices own them.
	 */
	public function test_order_context_callbacks_are_safe_pass_throughs(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->register();

		$this->assertSame( 123, $sut->init_order_currency( 123 ) );
		$this->assertSame( 42.0, $sut->maybe_init_order_currency_from_order_total_prop( 42.0, null ) );
		$this->assertSame( '$42.00', $sut->maybe_clear_order_currency_after_formatted_order_total( '$42.00', null, '', false ) );
		$this->assertSame(
			array( 'cost' => '10.00' ),
			$sut->fix_price_decimals_for_shipping_rates( array( 'cost' => '10.00' ), null )
		);
		$this->assertNull( $sut->init_order_currency_from_query_vars(), 'Pay-for-order action callback should not return a value.' );
	}

	/**
	 * Create a frontend currencies controller with a static runtime owner.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyFrontendCurrenciesController
	 */
	private function create_controller( string $owner ): MultiCurrencyFrontendCurrenciesController {
		$controller = new MultiCurrencyFrontendCurrenciesController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_frontend_projection_service( $this->create_projection_service() );

		return $controller;
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
				return 'GBP';
			}

			/**
			 * Project the number of price decimals.
			 *
			 * @param int         $decimals       Original decimal count.
			 * @param string|null $order_currency Optional order currency override.
			 * @return int
			 */
			public function get_price_decimals( int $decimals, ?string $order_currency = null ): int {
				return 0;
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
}
