<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRestRequestOverrideController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyRestRequestOverrideController class.
 */
class MultiCurrencyRestRequestOverrideControllerTest extends WC_Unit_Test_Case {

	private const FILTER_OVERRIDE_SELECTED_CURRENCY   = 'wcpay_multi_currency_override_selected_currency';
	private const FILTER_SHOULD_RETURN_STORE_CURRENCY = 'wcpay_multi_currency_should_return_store_currency';
	private const FILTER_SHOULD_CONVERT_PRODUCT_PRICE = 'wcpay_multi_currency_should_convert_product_price';

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Set up test state.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_currency = get_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency', 'USD' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		remove_all_filters( self::FILTER_OVERRIDE_SELECTED_CURRENCY );
		remove_all_filters( self::FILTER_SHOULD_RETURN_STORE_CURRENCY );
		remove_all_filters( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE );
		unset( $_GET['currency'] );
		update_option( 'woocommerce_currency', $this->original_currency );

		parent::tear_down();
	}

	/**
	 * @testdox Should not register REST request overrides when plugin owns runtime.
	 */
	public function test_does_not_register_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, true );

		$sut->register();

		$this->assertFalse( has_filter( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $sut, 'get_currency_from_query_param' ) ) );
		$this->assertFalse( has_filter( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $sut, 'get_store_currency_code' ) ) );
	}

	/**
	 * @testdox Should not register REST request overrides outside non-Store REST context.
	 */
	public function test_does_not_register_outside_non_store_rest_context(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$sut->register();

		$this->assertFalse( has_filter( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $sut, 'get_currency_from_query_param' ) ) );
		$this->assertFalse( has_filter( self::FILTER_SHOULD_RETURN_STORE_CURRENCY, '__return_true' ) );
		$this->assertFalse( has_filter( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE, '__return_false' ) );
	}

	/**
	 * @testdox Should register query currency override for non-Store REST request.
	 */
	public function test_registers_query_currency_override_for_non_store_rest_request(): void {
		$sut              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true );
		$_GET['currency'] = ' gbp ';

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_filter( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $sut, 'get_currency_from_query_param' ) ) );
		/**
		 * Apply the request-local selected currency override filter registered by the controller.
		 *
		 * @since 11.0.0
		 */
		$this->assertSame( 'GBP', apply_filters( self::FILTER_OVERRIDE_SELECTED_CURRENCY, false ) );
		$this->assertFalse( has_filter( self::FILTER_SHOULD_RETURN_STORE_CURRENCY, '__return_true' ) );
		$this->assertFalse( has_filter( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE, '__return_false' ) );
	}

	/**
	 * @testdox Should force store currency without query currency for non-Store REST request.
	 */
	public function test_forces_store_currency_without_query_currency_for_non_store_rest_request(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true );

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_filter( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $sut, 'get_store_currency_code' ) ) );
		$this->assertSame( 10, has_filter( self::FILTER_SHOULD_RETURN_STORE_CURRENCY, '__return_true' ) );
		$this->assertSame( 10, has_filter( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE, '__return_false' ) );
		/**
		 * Apply the request-local selected currency override filter registered by the controller.
		 *
		 * @since 11.0.0
		 */
		$this->assertSame( 'USD', apply_filters( self::FILTER_OVERRIDE_SELECTED_CURRENCY, false ) );
		/**
		 * Apply the request-local store-currency guard filter registered by the controller.
		 *
		 * @since 11.0.0
		 */
		$this->assertTrue( apply_filters( self::FILTER_SHOULD_RETURN_STORE_CURRENCY, false ) );
		/**
		 * Apply the request-local product price conversion guard filter registered by the controller.
		 *
		 * @since 11.0.0
		 */
		$this->assertFalse( apply_filters( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE, true, new \stdClass() ) );
	}

	/**
	 * Create a REST request override controller with a static runtime owner.
	 *
	 * @param string $owner                             Runtime owner.
	 * @param bool   $should_register_request_overrides Whether REST overrides should register.
	 * @return MultiCurrencyRestRequestOverrideController
	 */
	private function create_controller( string $owner, bool $should_register_request_overrides ): MultiCurrencyRestRequestOverrideController {
		$controller = new MultiCurrencyRestRequestOverrideController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_request_context( $this->create_request_context( $should_register_request_overrides ) );

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
			 * Tell whether core multi-currency may register hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}

	/**
	 * Create a request context test double.
	 *
	 * @param bool $should_register_request_overrides Whether REST overrides should register.
	 * @return MultiCurrencyRequestContext
	 */
	private function create_request_context( bool $should_register_request_overrides ): MultiCurrencyRequestContext {
		return new class( $should_register_request_overrides ) extends MultiCurrencyRequestContext {
			/**
			 * Whether REST overrides should register.
			 *
			 * @var bool
			 */
			private bool $should_register_request_overrides;

			/**
			 * Constructor.
			 *
			 * @param bool $should_register_request_overrides Whether REST overrides should register.
			 */
			public function __construct( bool $should_register_request_overrides ) {
				$this->should_register_request_overrides = $should_register_request_overrides;
			}

			/**
			 * Tell whether REST request override filters should register.
			 *
			 * @return bool
			 */
			public function should_register_rest_request_overrides(): bool {
				return $this->should_register_request_overrides;
			}
		};
	}
}
