<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyPointsRewardsCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyPointsRewardsCompatibilityController class.
 */
class MultiCurrencyPointsRewardsCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the Points and Rewards compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'plugins_loaded',
		'option_wc_points_rewards_earn_points_ratio',
		'option_wc_points_rewards_redeem_points_ratio',
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
	 * @testdox Should register Points and Rewards compatibility hooks for core frontend runtime.
	 */
	public function test_registers_points_rewards_compatibility_hooks_for_core_frontend_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 50, has_filter( 'option_wc_points_rewards_earn_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
		$this->assertSame( 50, has_filter( 'option_wc_points_rewards_redeem_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
	}

	/**
	 * @testdox Should not register Points and Rewards hooks when runtime guards block.
	 */
	public function test_does_not_register_points_rewards_hooks_when_guards_block(): void {
		$plugin_owned           = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$missing_points_rewards = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );
		$admin                  = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, true );

		$plugin_owned->register();
		$missing_points_rewards->register();
		$admin->register();

		$this->assert_points_rewards_hooks_not_registered( $plugin_owned );
		$this->assert_points_rewards_hooks_not_registered( $missing_points_rewards );
		$this->assert_points_rewards_hooks_not_registered( $admin );
	}

	/**
	 * @testdox Should defer Points and Rewards hook registration until plugins load.
	 */
	public function test_defers_points_rewards_registration_until_plugins_load(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, false, false );

		$sut->register();

		$this->assertFalse( has_filter( 'option_wc_points_rewards_earn_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_points_rewards_filters' ) ) );

		$sut->set_points_rewards_available( true );
		$sut->register_points_rewards_filters();

		$this->assertSame( 50, has_filter( 'option_wc_points_rewards_earn_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
		$this->assertSame( 50, has_filter( 'option_wc_points_rewards_redeem_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
	}

	/**
	 * @testdox Should convert Points and Rewards ratios by selected currency rate.
	 */
	public function test_converts_points_rewards_ratios_by_selected_currency_rate(): void {
		$sut = $this->create_controller();

		$this->assertSame( '10:1.6', $sut->convert_points_ratio( '10:2' ) );
	}

	/**
	 * @testdox Should preserve ratios when selected currency is default currency.
	 */
	public function test_preserves_ratios_when_selected_currency_is_default_currency(): void {
		$sut = $this->create_controller();
		$sut->set_state_builder( $this->create_state_builder( $this->create_state( true ) ) );

		$this->assertSame( '10:2', $sut->convert_points_ratio( '10:2' ) );
	}

	/**
	 * @testdox Should preserve ratios while Points and Rewards discount data is calculated.
	 */
	public function test_preserves_ratios_while_discount_data_is_calculated(): void {
		$sut = $this->create_controller();
		$sut->set_backtrace_calls( array( 'WC_Points_Rewards_Discount->get_discount_data' ) );

		$this->assertSame( '10:2', $sut->convert_points_ratio( '10:2' ) );
	}

	/**
	 * @testdox Should bootstrap Points and Rewards compatibility controller.
	 */
	public function test_bootstrap_registers_points_rewards_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyPointsRewardsCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyPointsRewardsCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyPointsRewardsCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert Points and Rewards hooks are not registered for a controller.
	 *
	 * @param MultiCurrencyPointsRewardsCompatibilityController $sut The controller.
	 */
	private function assert_points_rewards_hooks_not_registered( MultiCurrencyPointsRewardsCompatibilityController $sut ): void {
		$this->assertFalse( has_filter( 'option_wc_points_rewards_earn_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
		$this->assertFalse( has_filter( 'option_wc_points_rewards_redeem_points_ratio', array( $sut, 'convert_points_ratio' ) ) );
	}

	/**
	 * Create a Points and Rewards compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner                    Runtime owner.
	 * @param bool   $points_rewards_available Whether Points and Rewards runtime is available.
	 * @param bool   $is_admin                 Whether this is an admin request.
	 * @param bool   $plugins_loaded           Whether plugins have loaded.
	 * @return MultiCurrencyPointsRewardsCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $points_rewards_available = true,
		bool $is_admin = false,
		bool $plugins_loaded = true
	): MultiCurrencyPointsRewardsCompatibilityController {
		$controller = new class( $points_rewards_available, $is_admin, $plugins_loaded ) extends MultiCurrencyPointsRewardsCompatibilityController {
			/**
			 * Whether Points and Rewards runtime is available.
			 *
			 * @var bool
			 */
			private bool $points_rewards_available;

			/**
			 * Whether this is an admin request.
			 *
			 * @var bool
			 */
			private bool $is_admin;

			/**
			 * Whether plugins have loaded.
			 *
			 * @var bool
			 */
			private bool $plugins_loaded;

			/**
			 * Backtrace summary calls.
			 *
			 * @var string[]
			 */
			private array $backtrace_calls = array();

			/**
			 * Constructor.
			 *
			 * @param bool $points_rewards_available Whether Points and Rewards runtime is available.
			 * @param bool $is_admin                 Whether this is an admin request.
			 * @param bool $plugins_loaded           Whether plugins have loaded.
			 */
			public function __construct( bool $points_rewards_available, bool $is_admin, bool $plugins_loaded ) {
				$this->points_rewards_available = $points_rewards_available;
				$this->is_admin                 = $is_admin;
				$this->plugins_loaded           = $plugins_loaded;
			}

			/**
			 * Set whether Points and Rewards runtime is available.
			 *
			 * @param bool $points_rewards_available Whether Points and Rewards runtime is available.
			 */
			public function set_points_rewards_available( bool $points_rewards_available ): void {
				$this->points_rewards_available = $points_rewards_available;
			}

			/**
			 * Set backtrace summary calls.
			 *
			 * @param string[] $backtrace_calls Backtrace summary calls.
			 */
			public function set_backtrace_calls( array $backtrace_calls ): void {
				$this->backtrace_calls = $backtrace_calls;
			}

			/**
			 * Check if Points and Rewards runtime is available.
			 *
			 * @return bool
			 */
			protected function is_points_rewards_runtime_available(): bool {
				return $this->points_rewards_available;
			}

			/**
			 * Check if this is an admin request.
			 *
			 * @return bool
			 */
			protected function is_admin_request(): bool {
				return $this->is_admin;
			}

			/**
			 * Check if plugins have loaded.
			 *
			 * @return bool
			 */
			protected function have_plugins_loaded(): bool {
				return $this->plugins_loaded;
			}

			/**
			 * Check if any expected call appears in the deterministic backtrace.
			 *
			 * @param string[] $calls Expected calls.
			 * @return bool
			 */
			protected function is_call_in_backtrace( array $calls ): bool {
				return array() !== array_intersect( $calls, $this->backtrace_calls );
			}
		};

		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )
		);
		$controller->set_state_builder( $this->create_state_builder( $this->create_state() ) );

		return $controller;
	}

	/**
	 * Create a runtime arbiter test double.
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
			 * Get the multi-currency runtime owner.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}
		};
	}

	/**
	 * Create a state builder test double.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder( MultiCurrencyState $state ): MultiCurrencyStateBuilder {
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
			 * Build a multi-currency state snapshot.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				return $this->state;
			}
		};
	}

	/**
	 * Create multi-currency state.
	 *
	 * @param bool $selected_is_default Whether selected currency should be default currency.
	 * @return MultiCurrencyState
	 */
	private function create_state( bool $selected_is_default = false ): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', 1.0, true );
		$gbp = $this->create_currency( 'GBP', 0.8, false );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $selected_is_default ? $usd : $gbp );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param float  $rate       Currency rate.
	 * @param bool   $is_default Whether this is the default currency.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default ): MultiCurrencyCurrency {
		return new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
	}

	/**
	 * Create a localization test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_localization(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				unset( $currency_code );

				return array(
					'currency_pos' => 'left',
					'thousand_sep' => ',',
					'decimal_sep'  => '.',
					'num_decimals' => 2,
				);
			}

			/**
			 * Get locale data for a country.
			 *
			 * @param string $country Country code.
			 * @return array<string,mixed>
			 */
			public function get_country_locale_data( $country ): array {
				unset( $country );

				return array();
			}
		};
	}
}
