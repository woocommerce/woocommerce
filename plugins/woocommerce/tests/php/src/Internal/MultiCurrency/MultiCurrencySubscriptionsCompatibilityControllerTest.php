<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySubscriptionsCompatibilityController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySubscriptionsCompatibilityController class.
 */
class MultiCurrencySubscriptionsCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the subscriptions compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'wcpay_multi_currency_override_selected_currency',
		'wcpay_multi_currency_should_disable_currency_switching',
		'wcpay_multi_currency_should_convert_product_price',
		'wcpay_multi_currency_should_convert_coupon_amount',
		'option_woocommerce_subscriptions_multiple_purchase',
	);

	/**
	 * Controllers that scheduled deferred plugin-loaded registration.
	 *
	 * @var MultiCurrencySubscriptionsCompatibilityController[]
	 */
	private array $deferred_controllers = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		foreach ( $this->deferred_controllers as $controller ) {
			remove_action( 'plugins_loaded', array( $controller, 'register_subscription_filters' ), 20 );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should register subscription filters for core frontend runtime.
	 */
	public function test_registers_subscription_filters_for_core_frontend_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_override_selected_currency', array( $sut, 'override_selected_currency' ) ) );
		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_should_disable_currency_switching', array( $sut, 'should_disable_currency_switching' ) ) );
		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_should_convert_coupon_amount', array( $sut, 'should_convert_coupon_amount' ) ) );
		$this->assertSame( 50, has_filter( 'option_woocommerce_subscriptions_multiple_purchase', array( $sut, 'maybe_disable_mixed_cart' ) ) );
	}

	/**
	 * @testdox Should not register subscription filters when plugin owns runtime.
	 */
	public function test_does_not_register_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assert_subscription_hooks_not_registered( $sut );
	}

	/**
	 * @testdox Should not register subscription filters when subscriptions runtime is absent.
	 */
	public function test_does_not_register_when_subscriptions_runtime_is_absent(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$sut->register();

		$this->assert_subscription_hooks_not_registered( $sut );
	}

	/**
	 * @testdox Should not register subscription filters for admin or cron requests.
	 */
	public function test_does_not_register_for_admin_or_cron_requests(): void {
		$admin = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, true, false );
		$cron  = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, false, true );

		$admin->register();
		$cron->register();

		$this->assert_subscription_hooks_not_registered( $admin );
		$this->assert_subscription_hooks_not_registered( $cron );
	}

	/**
	 * @testdox Should defer registration until subscriptions runtime loads.
	 */
	public function test_defers_registration_until_subscriptions_runtime_loads(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );
		$sut->set_plugins_loaded( false );
		$this->deferred_controllers[] = $sut;

		$sut->register();

		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_subscription_filters' ) ) );

		$sut->set_subscriptions_available( true );
		$sut->register_subscription_filters();

		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
	}

	/**
	 * @testdox Should override selected currency from subscription cart items and switch requests.
	 */
	public function test_overrides_selected_currency_from_subscription_contexts(): void {
		foreach (
			array(
				'renewal'     => 'EUR',
				'resubscribe' => 'GBP',
				'switch'      => 'CAD',
			) as $type => $currency_code
		) {
			$sut = $this->create_controller();
			$sut->set_subscription_cart_item( $type, $this->create_subscription( $currency_code ) );

			$this->assertSame( $currency_code, $sut->override_selected_currency( false ) );
		}

		$switch_request = $this->create_controller();
		$switch_request->set_switch_request_subscription( $this->create_subscription( 'AUD' ) );

		$this->assertSame( 'AUD', $switch_request->override_selected_currency( false ) );
	}

	/**
	 * @testdox Should preserve an existing selected currency override.
	 */
	public function test_preserves_existing_selected_currency_override(): void {
		$sut = $this->create_controller();
		$sut->set_subscription_cart_item( 'renewal', $this->create_subscription( 'EUR' ) );

		$this->assertSame( 'GBP', $sut->override_selected_currency( 'GBP' ) );
	}

	/**
	 * @testdox Should disable switching for subscription cart or switch request contexts.
	 */
	public function test_disables_switching_for_subscription_contexts(): void {
		$sut = $this->create_controller();

		$this->assertFalse( $sut->should_disable_currency_switching( false ) );
		$this->assertTrue( $sut->should_disable_currency_switching( true ) );

		$sut->set_subscription_cart_item( 'resubscribe', $this->create_subscription( 'EUR' ) );

		$this->assertTrue( $sut->should_disable_currency_switching( false ) );

		$switch_request = $this->create_controller();
		$switch_request->set_switch_request_subscription( $this->create_subscription( 'AUD' ) );

		$this->assertTrue( $switch_request->should_disable_currency_switching( false ) );
	}

	/**
	 * @testdox Should apply subscription product conversion decisions.
	 */
	public function test_applies_subscription_product_conversion_decisions(): void {
		$product = (object) array( 'id' => 1 );
		$sut     = $this->create_controller();

		$this->assertTrue( $sut->should_convert_product_price( true, $product ) );
		$this->assertFalse( $sut->should_convert_product_price( false, $product ) );

		$renewal = $this->create_controller();
		$renewal->set_subscription_cart_item( 'renewal', $this->create_subscription( 'EUR' ) );
		$renewal->set_backtrace_calls( array( 'WC_Cart_Totals->calculate_item_totals' ) );

		$this->assertFalse( $renewal->should_convert_product_price( true, $product ) );

		$renewal_setup = $this->create_controller();
		$renewal_setup->set_subscription_cart_item( 'renewal', $this->create_subscription( 'EUR' ) );
		$renewal_setup->set_backtrace_calls( array( 'WCS_Cart_Renewal->setup_cart', 'WC_Cart_Totals->calculate_item_totals' ) );

		$this->assertTrue( $renewal_setup->should_convert_product_price( true, $product ) );

		$resubscribe = $this->create_controller();
		$resubscribe->set_subscription_cart_item( 'resubscribe', $this->create_subscription( 'GBP' ) );
		$resubscribe->set_backtrace_calls( array( 'WC_Cart->get_product_subtotal' ) );

		$this->assertFalse( $resubscribe->should_convert_product_price( true, $product ) );

		$recurring_item = $this->create_controller();
		$recurring_item->set_backtrace_calls( array( 'WC_Payments_Subscription_Service->get_recurring_item_data_for_subscription', 'WC_Product->get_price' ) );

		$this->assertFalse( $recurring_item->should_convert_product_price( true, $product ) );
	}

	/**
	 * @testdox Should apply subscription coupon conversion decisions.
	 */
	public function test_applies_subscription_coupon_conversion_decisions(): void {
		$sut = $this->create_controller();

		$this->assertTrue( $sut->should_convert_coupon_amount( true, $this->create_coupon( 'fixed_cart' ) ) );
		$this->assertFalse( $sut->should_convert_coupon_amount( false, $this->create_coupon( 'fixed_cart' ) ) );
		$this->assertFalse( $sut->should_convert_coupon_amount( true, $this->create_coupon( 'recurring_percent' ) ) );

		$renewal = $this->create_controller();
		$renewal->set_subscription_cart_item( 'renewal', $this->create_subscription( 'EUR' ) );
		$renewal->set_backtrace_calls( array( 'WC_Discounts->apply_coupon' ) );

		$this->assertFalse( $renewal->should_convert_coupon_amount( true, $this->create_coupon( 'renewal_fee' ) ) );
		$this->assertTrue( $renewal->should_convert_coupon_amount( true, $this->create_coupon( 'fixed_cart' ) ) );

		$early_renewal = $this->create_controller();
		$early_renewal->set_subscription_cart_item( 'renewal', $this->create_subscription( 'EUR' ) );
		$early_renewal->set_backtrace_calls( array( 'WCS_Cart_Early_Renewal->setup_cart', 'WC_Discounts->apply_coupon' ) );

		$this->assertTrue( $early_renewal->should_convert_coupon_amount( true, $this->create_coupon( 'renewal_fee' ) ) );
	}

	/**
	 * @testdox Should match instance method calls in the real backtrace.
	 */
	public function test_matches_instance_method_calls_in_real_backtrace(): void {
		$sut = new class() extends MultiCurrencySubscriptionsCompatibilityController {
			/**
			 * Tell whether an expected call is present in the real backtrace.
			 *
			 * @param string $expected_call Expected call string.
			 * @return bool
			 */
			public function matches_current_backtrace( string $expected_call ): bool {
				return $this->is_call_in_backtrace( array( $expected_call ) );
			}
		};

		$fixture = new class() {
			/**
			 * Call the controller from an instance method.
			 *
			 * @param object $controller Controller exposing matches_current_backtrace().
			 * @return bool
			 */
			public function matches_instance_method_call( object $controller ): bool {
				return $controller->matches_current_backtrace( self::class . '->matches_instance_method_call' );
			}
		};

		$this->assertTrue( $fixture->matches_instance_method_call( $sut ) );
	}

	/**
	 * @testdox Should disable mixed purchases for switch cart items.
	 */
	public function test_disables_mixed_purchase_for_switch_cart_items(): void {
		$sut = $this->create_controller();

		$this->assertSame( 'yes', $sut->maybe_disable_mixed_cart( 'yes' ) );

		$sut->set_subscription_cart_item( 'switch', $this->create_subscription( 'CAD' ) );

		$this->assertSame( 'no', $sut->maybe_disable_mixed_cart( 'yes' ) );
	}

	/**
	 * @testdox Should bootstrap subscriptions compatibility controller.
	 */
	public function test_bootstrap_registers_subscriptions_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencySubscriptionsCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencySubscriptionsCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencySubscriptionsCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert subscription compatibility hooks are not registered for a controller.
	 *
	 * @param MultiCurrencySubscriptionsCompatibilityController $sut The controller.
	 */
	private function assert_subscription_hooks_not_registered( MultiCurrencySubscriptionsCompatibilityController $sut ): void {
		$this->assertFalse( has_filter( 'wcpay_multi_currency_override_selected_currency', array( $sut, 'override_selected_currency' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_disable_currency_switching', array( $sut, 'should_disable_currency_switching' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_coupon_amount', array( $sut, 'should_convert_coupon_amount' ) ) );
		$this->assertFalse( has_filter( 'option_woocommerce_subscriptions_multiple_purchase', array( $sut, 'maybe_disable_mixed_cart' ) ) );
	}

	/**
	 * Create a subscriptions compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner                   Runtime owner.
	 * @param bool   $subscriptions_available Whether Subscriptions runtime is available.
	 * @param bool   $is_admin                Whether this is an admin request.
	 * @param bool   $is_cron                 Whether this is a cron request.
	 * @return MultiCurrencySubscriptionsCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $subscriptions_available = true,
		bool $is_admin = false,
		bool $is_cron = false
	): MultiCurrencySubscriptionsCompatibilityController {
		$controller = new class( $subscriptions_available, $is_admin, $is_cron ) extends MultiCurrencySubscriptionsCompatibilityController {
			/**
			 * Whether Subscriptions runtime is available.
			 *
			 * @var bool
			 */
			private bool $subscriptions_available;

			/**
			 * Whether this is an admin request.
			 *
			 * @var bool
			 */
			private bool $is_admin;

			/**
			 * Whether this is a cron request.
			 *
			 * @var bool
			 */
			private bool $is_cron;

			/**
			 * Whether WordPress has finished loading plugins.
			 *
			 * @var bool
			 */
			private bool $plugins_loaded = true;

			/**
			 * Subscription cart items keyed by type.
			 *
			 * @var array<string,array<string,mixed>>
			 */
			private array $cart_items = array();

			/**
			 * Switch request subscription.
			 *
			 * @var object|null
			 */
			private ?object $switch_request_subscription = null;

			/**
			 * Active backtrace calls keyed by call string.
			 *
			 * @var array<string,bool>
			 */
			private array $backtrace_calls = array();

			/**
			 * Constructor.
			 *
			 * @param bool $subscriptions_available Whether Subscriptions runtime is available.
			 * @param bool $is_admin                Whether this is an admin request.
			 * @param bool $is_cron                 Whether this is a cron request.
			 */
			public function __construct( bool $subscriptions_available, bool $is_admin, bool $is_cron ) {
				$this->subscriptions_available = $subscriptions_available;
				$this->is_admin                = $is_admin;
				$this->is_cron                 = $is_cron;
			}

			/**
			 * Set whether Subscriptions runtime is available.
			 *
			 * @param bool $subscriptions_available Whether Subscriptions runtime is available.
			 */
			public function set_subscriptions_available( bool $subscriptions_available ): void {
				$this->subscriptions_available = $subscriptions_available;
			}

			/**
			 * Set whether plugins_loaded has fired.
			 *
			 * @param bool $plugins_loaded Whether plugins_loaded has fired.
			 */
			public function set_plugins_loaded( bool $plugins_loaded ): void {
				$this->plugins_loaded = $plugins_loaded;
			}

			/**
			 * Set a deterministic subscription cart item.
			 *
			 * @param string $type         Subscription cart type.
			 * @param object $subscription Subscription object.
			 */
			public function set_subscription_cart_item( string $type, object $subscription ): void {
				$this->cart_items[ $type ] = array(
					'subscription_' . $type => array( 'subscription_id' => $type . '-subscription' ),
					'subscription_object'   => $subscription,
				);
			}

			/**
			 * Set a deterministic switch request subscription.
			 *
			 * @param object $subscription Subscription object.
			 */
			public function set_switch_request_subscription( object $subscription ): void {
				$this->switch_request_subscription = $subscription;
			}

			/**
			 * Set deterministic backtrace calls.
			 *
			 * @param string[] $backtrace_calls Backtrace call strings.
			 */
			public function set_backtrace_calls( array $backtrace_calls ): void {
				$this->backtrace_calls = array_fill_keys( $backtrace_calls, true );
			}

			/**
			 * Tell whether the current request is an admin request.
			 *
			 * @return bool
			 */
			protected function is_admin_request(): bool {
				return $this->is_admin;
			}

			/**
			 * Tell whether the current request is a cron request.
			 *
			 * @return bool
			 */
			protected function is_cron_request(): bool {
				return $this->is_cron;
			}

			/**
			 * Tell whether Subscriptions runtime is available.
			 *
			 * @return bool
			 */
			protected function is_subscriptions_runtime_available(): bool {
				return $this->subscriptions_available;
			}

			/**
			 * Tell whether WordPress has finished loading plugins.
			 *
			 * @return bool
			 */
			protected function have_plugins_loaded(): bool {
				return $this->plugins_loaded;
			}

			/**
			 * Get a deterministic subscription cart item.
			 *
			 * @param string $type Subscription cart type.
			 * @return array<string,mixed>|null
			 */
			protected function get_subscription_type_from_cart( string $type ): ?array {
				return $this->cart_items[ $type ] ?? null;
			}

			/**
			 * Get a subscription object from a deterministic cart item.
			 *
			 * @param array<string,mixed> $cart_item Subscription cart item.
			 * @param string              $type      Subscription cart type.
			 * @return object|null
			 */
			protected function get_subscription_from_cart_item( array $cart_item, string $type ): ?object {
				unset( $type );

				return $cart_item['subscription_object'] ?? null;
			}

			/**
			 * Get a deterministic switch request subscription.
			 *
			 * @return object|null
			 */
			protected function get_subscription_from_switch_request(): ?object {
				return $this->switch_request_subscription;
			}

			/**
			 * Tell whether expected calls are present in the backtrace.
			 *
			 * @param string[] $expected_calls Expected call strings.
			 * @return bool
			 */
			protected function is_call_in_backtrace( array $expected_calls ): bool {
				foreach ( $expected_calls as $expected_call ) {
					if ( isset( $this->backtrace_calls[ $expected_call ] ) ) {
						return true;
					}
				}

				return false;
			}
		};

		$controller->init( $this->create_arbiter( $owner ), new LegacyProxy() );

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
	 * Create a fake subscription object.
	 *
	 * @param string $currency_code Currency code.
	 * @return object
	 */
	private function create_subscription( string $currency_code ): object {
		return new class( $currency_code ) {
			/**
			 * Currency code.
			 *
			 * @var string
			 */
			private string $currency_code;

			/**
			 * Constructor.
			 *
			 * @param string $currency_code Currency code.
			 */
			public function __construct( string $currency_code ) {
				$this->currency_code = $currency_code;
			}

			/**
			 * Get subscription currency.
			 *
			 * @return string
			 */
			public function get_currency(): string {
				return $this->currency_code;
			}
		};
	}

	/**
	 * Create a fake coupon object.
	 *
	 * @param string $discount_type Discount type.
	 * @return object
	 */
	private function create_coupon( string $discount_type ): object {
		return new class( $discount_type ) {
			/**
			 * Discount type.
			 *
			 * @var string
			 */
			private string $discount_type;

			/**
			 * Constructor.
			 *
			 * @param string $discount_type Discount type.
			 */
			public function __construct( string $discount_type ) {
				$this->discount_type = $discount_type;
			}

			/**
			 * Get coupon discount type.
			 *
			 * @return string
			 */
			public function get_discount_type(): string {
				return $this->discount_type;
			}
		};
	}
}
