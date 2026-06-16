<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySelectedCurrencyController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySelectedCurrencyPersistenceService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySelectedCurrencyController class.
 */
class MultiCurrencySelectedCurrencyControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the selected currency controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'init',
		'wp_footer',
		'woocommerce_created_customer',
		'woocommerce_edit_account_form',
		'woocommerce_save_account_details',
	);

	/**
	 * Original WooCommerce session.
	 *
	 * @var mixed
	 */
	private $original_session;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_session = WC()->session;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		unset(
			$_GET['currency'],
			$_GET['min_price'],
			$_GET['max_price'],
			$_GET['rest_route'],
			$_GET['pay_for_order'],
			$_POST['wcpay_selected_currency']
		);
		delete_option( 'wcpay_multi_currency_enable_auto_currency' );
		delete_option( 'wcpay_multi_currency_rendering_mode' );
		delete_option( '_wcpay_feature_mc_cache_optimized' );
		WC()->session = $this->original_session;

		parent::tearDown();
	}

	/**
	 * @testdox Should not register selected currency hooks when plugin owns runtime.
	 */
	public function test_does_not_register_selected_currency_hooks_when_plugin_owns_runtime(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, $service );

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertFalse( has_action( 'woocommerce_save_account_details', array( $sut, 'handle_woocommerce_save_account_details' ) ) );
	}

	/**
	 * @testdox Should register selected currency hooks when core owns runtime.
	 */
	public function test_registers_selected_currency_hooks_when_core_owns_runtime(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );

		$sut->register();
		$sut->register();

		$this->assertSame( 11, has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertSame( 12, has_action( 'init', array( $sut, 'handle_geolocation_init' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_created_customer', array( $sut, 'handle_woocommerce_created_customer' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_edit_account_form', array( $sut, 'handle_woocommerce_edit_account_form' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_save_account_details', array( $sut, 'handle_woocommerce_save_account_details' ) ) );
	}

	/**
	 * @testdox Should register only account hooks in blocked request context.
	 */
	public function test_registers_only_account_hooks_in_blocked_request_context(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$service,
			$this->create_request_context( false )
		);

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertFalse( has_action( 'init', array( $sut, 'handle_geolocation_init' ) ) );
		$this->assertFalse( has_action( 'woocommerce_created_customer', array( $sut, 'handle_woocommerce_created_customer' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_edit_account_form', array( $sut, 'handle_woocommerce_edit_account_form' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_save_account_details', array( $sut, 'handle_woocommerce_save_account_details' ) ) );
	}

	/**
	 * @testdox Should register writer hooks in Store API request context.
	 */
	public function test_registers_writer_hooks_for_store_api_context(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$service,
			$this->create_request_context( true )
		);

		$sut->register();

		$this->assertSame( 11, has_action( 'init', array( $sut, 'handle_init' ) ) );
		$this->assertSame( 12, has_action( 'init', array( $sut, 'handle_geolocation_init' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_created_customer', array( $sut, 'handle_woocommerce_created_customer' ) ) );
	}

	/**
	 * @testdox Should update currency from URL parameter.
	 */
	public function test_updates_currency_from_url_parameter(): void {
		$service          = $this->create_persistence_service();
		$sut              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['currency'] = ' gbp ';

		$sut->handle_init();

		$this->assertSame( array( 'GBP' ), $service->updated_currencies );
	}

	/**
	 * @testdox Should update currency from geolocation when automatic switching is enabled.
	 */
	public function test_updates_currency_from_geolocation_when_auto_currency_is_enabled(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD' ) );

		$sut->handle_geolocation_init();

		$this->assertSame( array( 'CAD' ), $service->updated_currencies );
		$this->assertSame( array( false ), $service->persist_flags );
	}

	/**
	 * @testdox Should not update geolocation currency when automatic switching is disabled.
	 */
	public function test_does_not_update_geolocation_currency_when_auto_currency_is_disabled(): void {
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD' ) );

		$sut->handle_geolocation_init();

		$this->assertSame( array(), $service->updated_currencies );
	}

	/**
	 * @testdox Should not update geolocation currency when switching is disabled.
	 */
	public function test_does_not_update_geolocation_currency_when_switching_is_disabled(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$service               = $this->create_persistence_service();
		$sut                   = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['pay_for_order'] = '1';
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD' ) );

		$sut->handle_geolocation_init();

		$this->assertSame( array(), $service->updated_currencies );
	}

	/**
	 * @testdox Should not overwrite stored currency with geolocation currency.
	 */
	public function test_does_not_overwrite_stored_currency_with_geolocation_currency(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$service = $this->create_persistence_service( true, 'GBP', true );
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD' ) );

		$sut->handle_geolocation_init();

		$this->assertSame( array(), $service->updated_currencies );
	}

	/**
	 * @testdox Should skip geolocation persistence in cache mode without an active session.
	 */
	public function test_skips_geolocation_persistence_in_cache_mode_without_active_session(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		update_option( 'wcpay_multi_currency_rendering_mode', 'cache' );
		update_option( '_wcpay_feature_mc_cache_optimized', '1' );
		WC()->session = $this->create_session( false );
		$service      = $this->create_persistence_service();
		$sut          = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$service,
			$this->create_request_context( true, false )
		);
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD' ) );

		$sut->handle_geolocation_init();

		$this->assertSame( array(), $service->updated_currencies );
	}

	/**
	 * @testdox Should persist geolocation currency in cache mode for Store API requests.
	 */
	public function test_persists_geolocation_currency_in_cache_mode_for_store_api_requests(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		update_option( 'wcpay_multi_currency_rendering_mode', 'cache' );
		update_option( '_wcpay_feature_mc_cache_optimized', '1' );
		WC()->session = $this->create_session( false );
		$service      = $this->create_persistence_service();
		$sut          = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$service,
			$this->create_request_context( true, true )
		);
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD' ) );

		$sut->handle_geolocation_init();

		$this->assertSame( array( 'CAD' ), $service->updated_currencies );
		$this->assertSame( array( false ), $service->persist_flags );
	}

	/**
	 * @testdox Should register geolocation notice when automatic switching passes guards.
	 */
	public function test_registers_geolocation_notice_when_auto_currency_passes_guards(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$service = $this->create_persistence_service();
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD', 'CA' ) );

		$sut->handle_geolocation_init();
		$sut->handle_geolocation_init();

		$this->assertSame( 10, has_action( 'wp_footer', array( $sut, 'handle_wp_footer' ) ) );
	}

	/**
	 * @testdox Should not register geolocation notice when switching is disabled.
	 */
	public function test_does_not_register_geolocation_notice_when_switching_is_disabled(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$service               = $this->create_persistence_service();
		$sut                   = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['pay_for_order'] = '1';
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD', 'CA' ) );

		$sut->handle_geolocation_init();

		$this->assertFalse( has_action( 'wp_footer', array( $sut, 'handle_wp_footer' ) ) );
	}

	/**
	 * @testdox Should render geolocation currency update notice.
	 */
	public function test_renders_geolocation_currency_update_notice(): void {
		$service = $this->create_persistence_service( true, 'CAD' );
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( 'CAD', 'CA' ) );

		ob_start();
		$sut->handle_wp_footer();
		$markup = (string) ob_get_clean();

		$store_currency = get_woocommerce_currency();
		$currencies     = get_woocommerce_currencies();

		$this->assertStringContainsString( 'woocommerce-store-notice demo_store', $markup );
		$this->assertStringContainsString( 'visiting from Canada', $markup );
		$this->assertStringContainsString( '?currency=' . $store_currency, $markup );
		$this->assertStringContainsString( 'Use ' . $currencies[ $store_currency ] . ' instead.', $markup );
		$this->assertStringContainsString( 'woocommerce-store-notice__dismiss-link', $markup );
	}

	/**
	 * @testdox Should not render geolocation notice for the store default currency.
	 */
	public function test_does_not_render_geolocation_notice_for_store_default_currency(): void {
		$store_currency = get_woocommerce_currency();
		$service        = $this->create_persistence_service( true, $store_currency );
		$sut            = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( $store_currency, 'US' ) );

		ob_start();
		$sut->handle_wp_footer();
		$markup = (string) ob_get_clean();

		$this->assertSame( '', $markup );
	}

	/**
	 * @testdox Should not render geolocation notice for another selected currency.
	 */
	public function test_does_not_render_geolocation_notice_for_other_selected_currency(): void {
		$service = $this->create_persistence_service( true, 'CAD' );
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$sut->set_geolocation_service( $this->create_geolocation_service( 'EUR', 'FR' ) );

		ob_start();
		$sut->handle_wp_footer();
		$markup = (string) ob_get_clean();

		$this->assertSame( '', $markup );
	}

	/**
	 * @testdox Should redirect browser currency switches to strip stale price filters.
	 */
	public function test_redirects_browser_currency_switch_to_strip_stale_price_filters(): void {
		$service                = $this->create_persistence_service();
		$sut                    = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['currency']       = 'eur';
		$_GET['min_price']      = '10';
		$_GET['max_price']      = '50';
		$original_request_uri   = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = '/shop/?currency=EUR&min_price=10&max_price=50';
		$captured_url           = null;

		add_filter(
			'wp_redirect',
			static function ( $location ) use ( &$captured_url ) {
				$captured_url = $location;
				throw new \Exception( 'redirect captured' );
			}
		);

		try {
			$sut->handle_init();
			$this->fail( 'Expected handle_init() to redirect on a browser currency switch with stale price filters.' );
		} catch ( \Exception $e ) {
			$this->assertSame( 'redirect captured', $e->getMessage(), 'Unexpected exception thrown.' );
			$this->assertSame( array( 'EUR' ), $service->updated_currencies );
			$this->assertIsString( $captured_url, 'Expected a redirect URL to be captured.' );
			$this->assertStringNotContainsString( 'min_price', $captured_url, 'min_price should have been stripped.' );
			$this->assertStringNotContainsString( 'max_price', $captured_url, 'max_price should have been stripped.' );
		} finally {
			remove_all_filters( 'wp_redirect' );
			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			}
		}
	}

	/**
	 * @testdox Should not redirect Store API currency switches with price filters.
	 */
	public function test_does_not_redirect_store_api_currency_switch_with_price_filters(): void {
		$service                = $this->create_persistence_service();
		$sut                    = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['currency']       = 'eur';
		$_GET['min_price']      = '10';
		$_GET['max_price']      = '50';
		$original_request_uri   = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/products?currency=EUR&min_price=10&max_price=50';

		add_filter(
			'wp_redirect',
			static function () {
				throw new \Exception( 'Unexpected redirect during REST request.' );
			}
		);

		try {
			$sut->handle_init();

			$this->assertSame( array( 'EUR' ), $service->updated_currencies );
		} finally {
			remove_all_filters( 'wp_redirect' );
			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			}
		}
	}

	/**
	 * @testdox Should not redirect rest_route currency switches with price filters.
	 */
	public function test_does_not_redirect_rest_route_currency_switch_with_price_filters(): void {
		$service                = $this->create_persistence_service();
		$sut                    = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_GET['currency']       = 'eur';
		$_GET['min_price']      = '10';
		$_GET['max_price']      = '50';
		$_GET['rest_route']     = '/wc/store/v1/products';
		$original_request_uri   = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$_SERVER['REQUEST_URI'] = '/?rest_route=/wc/store/v1/products&currency=EUR&min_price=10&max_price=50';

		add_filter(
			'wp_redirect',
			static function () {
				throw new \Exception( 'Unexpected redirect during rest_route request.' );
			}
		);

		try {
			$sut->handle_init();

			$this->assertSame( array( 'EUR' ), $service->updated_currencies );
		} finally {
			remove_all_filters( 'wp_redirect' );
			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			}
		}
	}

	/**
	 * @testdox Should render account currency field when multiple currencies are enabled.
	 */
	public function test_renders_account_currency_field_when_multiple_currencies_are_enabled(): void {
		$service = $this->create_persistence_service( true, 'GBP' );
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );

		ob_start();
		$sut->handle_woocommerce_edit_account_form();
		$markup = (string) ob_get_clean();

		$this->assertStringContainsString( '<label for="wcpay_selected_currency">Default currency</label>', $markup );
		$this->assertStringContainsString( '<option value="GBP" selected>', $markup );
	}

	/**
	 * @testdox Should save account currency field from posted data.
	 */
	public function test_saves_account_currency_field_from_posted_data(): void {
		$service                          = $this->create_persistence_service();
		$sut                              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, $service );
		$_POST['wcpay_selected_currency'] = ' jpy ';

		$sut->handle_woocommerce_save_account_details();

		$this->assertSame( array( 'JPY' ), $service->updated_currencies );
	}

	/**
	 * Create a selected currency controller with a static runtime owner.
	 *
	 * @param string                           $owner           Runtime owner.
	 * @param object                           $service         Persistence service test double.
	 * @param MultiCurrencyRequestContext|null $request_context Request context.
	 * @return MultiCurrencySelectedCurrencyController
	 */
	private function create_controller(
		string $owner,
		object $service,
		?MultiCurrencyRequestContext $request_context = null
	): MultiCurrencySelectedCurrencyController {
		$controller = new MultiCurrencySelectedCurrencyController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_persistence_service( $service );
		if ( null !== $request_context && method_exists( $controller, 'set_request_context' ) ) {
			$controller->set_request_context( $request_context );
		}

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
	 * Create a persistence service test double.
	 *
	 * @param bool     $has_additional_currencies Whether multiple currencies are enabled.
	 * @param string   $selected_code             Selected currency code.
	 * @param bool     $has_stored_currency       Whether a stored currency exists.
	 * @param string[] $enabled_currency_codes    Enabled currency codes.
	 * @return MultiCurrencySelectedCurrencyPersistenceService&object{updated_currencies: string[], persist_flags: bool[], new_customer_ids: int[]}
	 */
	private function create_persistence_service(
		bool $has_additional_currencies = true,
		string $selected_code = 'USD',
		bool $has_stored_currency = false,
		array $enabled_currency_codes = array( 'USD', 'GBP', 'JPY', 'EUR', 'CAD' )
	): MultiCurrencySelectedCurrencyPersistenceService {
		return new class( $has_additional_currencies, $selected_code, $has_stored_currency, $enabled_currency_codes ) extends MultiCurrencySelectedCurrencyPersistenceService {
			/**
			 * Updated currencies.
			 *
			 * @var string[]
			 */
			public array $updated_currencies = array();

			/**
			 * Persist-change flags.
			 *
			 * @var bool[]
			 */
			public array $persist_flags = array();

			/**
			 * New customer IDs.
			 *
			 * @var int[]
			 */
			public array $new_customer_ids = array();

			/**
			 * Whether multiple currencies are enabled.
			 *
			 * @var bool
			 */
			private bool $has_additional_currencies;

			/**
			 * Selected currency code.
			 *
			 * @var string
			 */
			private string $selected_code;

			/**
			 * Whether a stored currency exists.
			 *
			 * @var bool
			 */
			private bool $has_stored_currency;

			/**
			 * Enabled currency lookup.
			 *
			 * @var array<string,bool>
			 */
			private array $enabled_currency_lookup;

			/**
			 * Constructor.
			 *
			 * @param bool     $has_additional_currencies Whether multiple currencies are enabled.
			 * @param string   $selected_code             Selected currency code.
			 * @param bool     $has_stored_currency       Whether a stored currency exists.
			 * @param string[] $enabled_currency_codes   Enabled currency codes.
			 */
			public function __construct(
				bool $has_additional_currencies,
				string $selected_code,
				bool $has_stored_currency,
				array $enabled_currency_codes
			) {
				$this->has_additional_currencies = $has_additional_currencies;
				$this->selected_code             = $selected_code;
				$this->has_stored_currency       = $has_stored_currency;
				$this->enabled_currency_lookup   = array_fill_keys( array_map( 'strtoupper', $enabled_currency_codes ), true );
			}

			/**
			 * Persist the selected currency.
			 *
			 * @param string $currency_code  Currency code.
			 * @param bool   $persist_change Whether the change persists.
			 * @return bool
			 */
			public function update_selected_currency( string $currency_code, bool $persist_change = true ): bool {
				$currency_code = strtoupper( trim( $currency_code ) );

				if ( ! isset( $this->enabled_currency_lookup[ $currency_code ] ) ) {
					return false;
				}

				$this->updated_currencies[] = $currency_code;
				$this->persist_flags[]      = $persist_change;

				return true;
			}

			/**
			 * Persist a new customer's selected currency.
			 *
			 * @param int $customer_id Customer ID.
			 * @return bool
			 */
			public function set_new_customer_currency_meta( int $customer_id ): bool {
				$this->new_customer_ids[] = $customer_id;

				return true;
			}

			/**
			 * Tell whether more than one currency is enabled.
			 *
			 * @return bool
			 */
			public function has_additional_currencies_enabled(): bool {
				return $this->has_additional_currencies;
			}

			/**
			 * Get enabled currency options.
			 *
			 * @return array<int,array{code:string,symbol:string}>
			 */
			public function get_enabled_currency_options(): array {
				return array(
					array(
						'code'   => 'USD',
						'symbol' => get_woocommerce_currency_symbol( 'USD' ),
					),
					array(
						'code'   => 'GBP',
						'symbol' => get_woocommerce_currency_symbol( 'GBP' ),
					),
					array(
						'code'   => 'JPY',
						'symbol' => get_woocommerce_currency_symbol( 'JPY' ),
					),
				);
			}

			/**
			 * Get selected currency code.
			 *
			 * @return string
			 */
			public function get_selected_currency_code(): string {
				return $this->selected_code;
			}

			/**
			 * Tell whether a stored selected currency exists.
			 *
			 * @return bool
			 */
			public function has_stored_currency_code(): bool {
				return $this->has_stored_currency;
			}
		};
	}

	/**
	 * Create a deterministic geolocation service.
	 *
	 * @param string|null $currency_code Currency code to return.
	 * @param string      $country_code  Country code to return.
	 * @return MultiCurrencyGeolocationService
	 */
	private function create_geolocation_service( ?string $currency_code, string $country_code = 'CA' ): MultiCurrencyGeolocationService {
		return new class( $currency_code, $country_code ) extends MultiCurrencyGeolocationService {
			/**
			 * Currency code to return.
			 *
			 * @var string|null
			 */
			private ?string $currency_code;

			/**
			 * Country code to return.
			 *
			 * @var string
			 */
			private string $country_code;

			/**
			 * Constructor.
			 *
			 * @param string|null $currency_code Currency code to return.
			 * @param string      $country_code  Country code to return.
			 */
			public function __construct( ?string $currency_code, string $country_code ) {
				$this->currency_code = $currency_code;
				$this->country_code  = $country_code;
			}

			/**
			 * Get the customer's currency based on location.
			 *
			 * @return string|null
			 */
			public function get_currency_by_customer_location(): ?string {
				return $this->currency_code;
			}

			/**
			 * Get the customer's country based on location.
			 *
			 * @return string
			 */
			public function get_country_by_customer_location(): string {
				return $this->country_code;
			}
		};
	}

	/**
	 * Create a session test double.
	 *
	 * @param bool $has_session Whether an active session exists.
	 * @return object
	 */
	private function create_session( bool $has_session ): object {
		return new class( $has_session ) {
			/**
			 * Whether an active session exists.
			 *
			 * @var bool
			 */
			private bool $has_session;

			/**
			 * Constructor.
			 *
			 * @param bool $has_session Whether an active session exists.
			 */
			public function __construct( bool $has_session ) {
				$this->has_session = $has_session;
			}

			/**
			 * Tell whether a cookie-backed session exists.
			 *
			 * @return bool
			 */
			public function has_session(): bool {
				return $this->has_session;
			}
		};
	}

	/**
	 * Create a request context test double.
	 *
	 * @param bool $should_register_entry_hooks Whether selected-currency entry hooks should register.
	 * @param bool $is_store_api_request        Whether this is a Store API request.
	 * @return MultiCurrencyRequestContext
	 */
	private function create_request_context( bool $should_register_entry_hooks, bool $is_store_api_request = false ): MultiCurrencyRequestContext {
		return new class( $should_register_entry_hooks, $is_store_api_request ) extends MultiCurrencyRequestContext {
			/**
			 * Whether selected-currency entry hooks should register.
			 *
			 * @var bool
			 */
			private bool $should_register_entry_hooks;

			/**
			 * Whether this is a Store API request.
			 *
			 * @var bool
			 */
			private bool $is_store_api_request;

			/**
			 * Constructor.
			 *
			 * @param bool $should_register_entry_hooks Whether selected-currency entry hooks should register.
			 * @param bool $is_store_api_request        Whether this is a Store API request.
			 */
			public function __construct( bool $should_register_entry_hooks, bool $is_store_api_request ) {
				$this->should_register_entry_hooks = $should_register_entry_hooks;
				$this->is_store_api_request        = $is_store_api_request;
			}

			/**
			 * Tell whether selected-currency entry hooks should register.
			 *
			 * @return bool
			 */
			public function should_register_selected_currency_entry_hooks(): bool {
				return $this->should_register_entry_hooks;
			}

			/**
			 * Tell whether this is a Store API request.
			 *
			 * @return bool
			 */
			public function is_store_api_request(): bool {
				return $this->is_store_api_request;
			}
		};
	}
}
