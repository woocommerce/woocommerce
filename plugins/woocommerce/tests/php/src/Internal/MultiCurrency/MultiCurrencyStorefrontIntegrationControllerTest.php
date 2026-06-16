<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyStorefrontIntegrationController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyStorefrontIntegrationController class.
 */
class MultiCurrencyStorefrontIntegrationControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'woocommerce_breadcrumb_defaults',
		'wp_enqueue_scripts',
		'wcpay_multi_currency_storefront_widget_instance',
		'wcpay_multi_currency_storefront_widget_args',
		'wcpay_multi_currency_storefront_widget_css',
		'wcpay_multi_currency_should_disable_currency_switching',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		unset(
			$_GET['currency'],
			$_GET['s'],
			$_GET['is_mc_onboarding_simulation'],
			$_GET['enable_storefront_switcher']
		);
		delete_option( 'wcpay_multi_currency_enable_storefront_switcher' );
		wp_dequeue_style( 'storefront-style' );
		wp_deregister_style( 'storefront-style' );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register Storefront switcher hooks when plugin owns runtime.
	 */
	public function test_does_not_register_hooks_when_plugin_owns_runtime(): void {
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'yes' );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_breadcrumb_defaults', array( $sut, 'handle_woocommerce_breadcrumb_defaults' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * @testdox Should register Storefront switcher hooks when enabled for a Storefront theme.
	 */
	public function test_registers_hooks_when_storefront_switcher_is_enabled(): void {
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'yes' );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 9999, has_filter( 'woocommerce_breadcrumb_defaults', array( $sut, 'handle_woocommerce_breadcrumb_defaults' ) ) );
		$this->assertSame( 50, has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * @testdox Should not register Storefront switcher hooks for non-Storefront themes.
	 */
	public function test_does_not_register_hooks_for_non_storefront_theme(): void {
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'yes' );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, 2, 'twentytwentyfive', 'twentytwentyfive' );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_breadcrumb_defaults', array( $sut, 'handle_woocommerce_breadcrumb_defaults' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * @testdox Should not register Storefront switcher hooks with one enabled currency.
	 */
	public function test_does_not_register_hooks_with_one_enabled_currency(): void {
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'yes' );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, 1 );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_breadcrumb_defaults', array( $sut, 'handle_woocommerce_breadcrumb_defaults' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * @testdox Should allow simulation to enable Storefront switcher hooks.
	 */
	public function test_allows_simulation_to_enable_storefront_switcher_hooks(): void {
		$_GET['is_mc_onboarding_simulation'] = 'true';
		$_GET['enable_storefront_switcher']  = 'true';
		$sut                                 = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();

		$this->assertSame( 9999, has_filter( 'woocommerce_breadcrumb_defaults', array( $sut, 'handle_woocommerce_breadcrumb_defaults' ) ) );
		$this->assertSame( 50, has_action( 'wp_enqueue_scripts', array( $sut, 'handle_wp_enqueue_scripts' ) ) );
	}

	/**
	 * @testdox Should inject switcher markup into Storefront breadcrumb defaults.
	 */
	public function test_injects_switcher_markup_into_breadcrumb_defaults(): void {
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'yes' );
		$_GET['currency'] = 'EUR';
		$_GET['s']        = 'shirts';
		$switcher         = $this->create_switcher_service( '<div id="woocommerce-payments-multi-currency-storefront-widget">Switcher</div>' );
		$sut              = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, 2, 'storefront', 'storefront', $switcher );

		add_filter(
			'wcpay_multi_currency_storefront_widget_instance',
			static function () {
				return array( 'title' => 'Header currency' );
			}
		);
		add_filter(
			'wcpay_multi_currency_storefront_widget_args',
			static function () {
				return array(
					'before_widget' => '<aside>',
					'after_widget'  => '</aside>',
				);
			}
		);

		$result = $sut->handle_woocommerce_breadcrumb_defaults(
			array(
				'wrap_before' => '<div class="storefront-breadcrumb"><nav class="woocommerce-breadcrumb">',
				'wrap_after'  => '</nav></div>',
			)
		);

		$this->assertSame(
			'<div class="storefront-breadcrumb"><div id="woocommerce-payments-multi-currency-storefront-widget">Switcher</div><nav class="woocommerce-breadcrumb">',
			$result['wrap_before']
		);
		$this->assertSame( array( 'title' => 'Header currency' ), $switcher->last_instance );
		$this->assertSame(
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
			),
			$switcher->last_args
		);
		$this->assertSame(
			array(
				'currency' => 'EUR',
				's'        => 'shirts',
			),
			$switcher->last_query_args
		);
		$this->assertFalse( $switcher->last_switching_disabled );
	}

	/**
	 * @testdox Should keep breadcrumb defaults unchanged when switching is disabled.
	 */
	public function test_keeps_breadcrumb_defaults_when_switching_is_disabled(): void {
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'yes' );
		$switcher = $this->create_switcher_service( '<div>Switcher</div>' );
		$sut      = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, 2, 'storefront', 'storefront', $switcher );

		add_filter( 'wcpay_multi_currency_should_disable_currency_switching', '__return_true' );

		$defaults = array(
			'wrap_before' => '<div class="storefront-breadcrumb"><nav class="woocommerce-breadcrumb">',
		);
		$result   = $sut->handle_woocommerce_breadcrumb_defaults( $defaults );

		$this->assertSame( $defaults, $result );
		$this->assertTrue( $switcher->last_switching_disabled );
	}

	/**
	 * @testdox Should enqueue filtered Storefront inline CSS.
	 */
	public function test_enqueues_filtered_storefront_inline_css(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		wp_register_style( 'storefront-style', false, array(), '1.0.0' );
		add_filter(
			'wcpay_multi_currency_storefront_widget_css',
			static function () {
				return '#woocommerce-payments-multi-currency-storefront-widget { float: left; }';
			}
		);

		$sut->handle_wp_enqueue_scripts();

		$style_data = wp_styles()->get_data( 'storefront-style', 'after' );

		$this->assertIsArray( $style_data );
		$this->assertSame( '#woocommerce-payments-multi-currency-storefront-widget { float: left; }', $style_data[0] );
	}

	/**
	 * Create a Storefront integration controller.
	 *
	 * @param string                                      $owner                  Runtime owner.
	 * @param int                                         $enabled_currency_count Enabled currency count.
	 * @param string                                      $stylesheet             Theme stylesheet.
	 * @param string                                      $template               Theme template.
	 * @param MultiCurrencySwitcherProjectionService|null $switcher_service       Switcher service.
	 * @return MultiCurrencyStorefrontIntegrationController
	 */
	private function create_controller(
		string $owner,
		int $enabled_currency_count = 2,
		string $stylesheet = 'storefront',
		string $template = 'storefront',
		?MultiCurrencySwitcherProjectionService $switcher_service = null
	): MultiCurrencyStorefrontIntegrationController {
		$controller = new MultiCurrencyStorefrontIntegrationController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_state_builder( $this->create_state_builder( $enabled_currency_count ) );
		$controller->set_theme_resolver(
			static function () use ( $stylesheet, $template ) {
				return array(
					'stylesheet' => $stylesheet,
					'template'   => $template,
				);
			}
		);

		if ( null !== $switcher_service ) {
			$controller->set_switcher_projection_service( $switcher_service );
		}

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
			 * Get the runtime owner.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
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
	 * Create a state builder test double.
	 *
	 * @param int $enabled_currency_count Enabled currency count.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder( int $enabled_currency_count ): MultiCurrencyStateBuilder {
		$state = $this->create_state( $enabled_currency_count );

		return new class( $state ) extends MultiCurrencyStateBuilder {
			/**
			 * Multi-currency state.
			 *
			 * @var MultiCurrencyState
			 */
			private MultiCurrencyState $state;

			/**
			 * Constructor.
			 *
			 * @param MultiCurrencyState $state Multi-currency state.
			 */
			public function __construct( MultiCurrencyState $state ) {
				$this->state = $state;
			}

			/**
			 * Build the state.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				return $this->state;
			}
		};
	}

	/**
	 * Create a multi-currency state snapshot.
	 *
	 * @param int $enabled_currency_count Enabled currency count.
	 * @return MultiCurrencyState
	 */
	private function create_state( int $enabled_currency_count ): MultiCurrencyState {
		$localization = $this->create_localization_service();
		$usd          = new MultiCurrencyCurrency( $localization, 'USD', 1.0, true );
		$enabled      = array( 'USD' => $usd );

		if ( 1 < $enabled_currency_count ) {
			$enabled['CAD'] = new MultiCurrencyCurrency( $localization, 'CAD', 1.25, false );
		}

		return new MultiCurrencyState( $enabled, $enabled, $usd, $usd );
	}

	/**
	 * Create a localization service test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_localization_service(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				return array(
					'num_decimals' => 2,
				);
			}

			/**
			 * Get country locale data.
			 *
			 * @param string $country Country code.
			 * @return array<string,mixed>
			 */
			public function get_country_locale_data( $country ): array {
				return array();
			}
		};
	}

	/**
	 * Create a switcher projection service test double.
	 *
	 * @param string $markup Markup to return.
	 * @return MultiCurrencySwitcherProjectionService&object{last_instance: array<string,mixed>, last_args: array<string,mixed>, last_query_args: array<string,mixed>, last_switching_disabled: bool}
	 */
	private function create_switcher_service( string $markup ): MultiCurrencySwitcherProjectionService {
		return new class( $markup ) extends MultiCurrencySwitcherProjectionService {
			/**
			 * Last widget instance.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_instance = array();

			/**
			 * Last widget args.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_args = array();

			/**
			 * Last query args.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_query_args = array();

			/**
			 * Last switching-disabled flag.
			 *
			 * @var bool
			 */
			public bool $last_switching_disabled = false;

			/**
			 * Markup to return.
			 *
			 * @var string
			 */
			private string $markup;

			/**
			 * Constructor.
			 *
			 * @param string $markup Markup to return.
			 */
			public function __construct( string $markup ) {
				$this->markup = $markup;
			}

			/**
			 * Project widget markup.
			 *
			 * @param array<string,mixed> $instance           Widget instance.
			 * @param array<string,mixed> $args               Widget args.
			 * @param array<string,mixed> $query_args         Query args.
			 * @param bool                $switching_disabled Whether switching is disabled.
			 * @return string
			 */
			public function get_widget_markup(
				array $instance = array(),
				array $args = array(),
				array $query_args = array(),
				bool $switching_disabled = false
			): string {
				$this->last_instance           = $instance;
				$this->last_args               = $args;
				$this->last_query_args         = $query_args;
				$this->last_switching_disabled = $switching_disabled;

				return $switching_disabled ? '' : $this->markup;
			}
		};
	}
}
