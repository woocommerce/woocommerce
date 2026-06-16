<?php
/**
 * MultiCurrencyStorefrontIntegrationController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStorefrontProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native Storefront multi-currency switcher hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyStorefrontIntegrationController implements RegisterHooksInterface {

	private const OPTION_PREFIX            = 'wcpay_multi_currency';
	private const DISABLE_SWITCHING_FILTER = 'wcpay_multi_currency_should_disable_currency_switching';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder|null
	 */
	private ?MultiCurrencyStateBuilder $state_builder = null;

	/**
	 * Switcher projection service.
	 *
	 * @var MultiCurrencySwitcherProjectionService|null
	 */
	private ?MultiCurrencySwitcherProjectionService $switcher_projection_service = null;

	/**
	 * Theme resolver.
	 *
	 * @var callable|null
	 */
	private $theme_resolver = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter $arbiter Runtime owner arbiter.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter ): void {
		$this->arbiter = $arbiter;
	}

	/**
	 * Set the state builder.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 */
	public function set_state_builder( MultiCurrencyStateBuilder $state_builder ): void {
		$this->state_builder = $state_builder;
	}

	/**
	 * Set the switcher projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencySwitcherProjectionService $switcher_projection_service Switcher projection service.
	 */
	public function set_switcher_projection_service( MultiCurrencySwitcherProjectionService $switcher_projection_service ): void {
		$this->switcher_projection_service = $switcher_projection_service;
	}

	/**
	 * Set the theme resolver.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param callable $theme_resolver Theme resolver.
	 */
	public function set_theme_resolver( callable $theme_resolver ): void {
		$this->theme_resolver = $theme_resolver;
	}

	/**
	 * Register Storefront switcher hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$theme = $this->get_theme_data();
		if ( ! MultiCurrencyStorefrontProjectionService::is_storefront_theme( $theme['stylesheet'], $theme['template'] ) ) {
			return;
		}

		$enabled_currency_count = count( $this->get_state_builder()->build()->get_enabled_currencies() );
		if (
			! MultiCurrencyStorefrontProjectionService::should_activate(
				$enabled_currency_count,
				$this->is_using_storefront_switcher(),
				$this->get_simulation_variables()
			)
		) {
			return;
		}

		$this->add_filter_once( 'woocommerce_breadcrumb_defaults', array( $this, 'handle_woocommerce_breadcrumb_defaults' ), 9999 );
		$this->add_action_once( 'wp_enqueue_scripts', array( $this, 'handle_wp_enqueue_scripts' ), 50 );
	}

	/**
	 * Inject the native switcher into Storefront breadcrumb defaults.
	 *
	 * @internal
	 *
	 * @param array<string,mixed> $defaults Breadcrumb defaults.
	 * @return array<string,mixed>
	 */
	public function handle_woocommerce_breadcrumb_defaults( array $defaults ): array {
		$instance = array();
		/**
		 * Filters the native multi-currency Storefront widget instance.
		 *
		 * @param array<string,mixed> $instance Widget instance.
		 *
		 * @since 11.0.0
		 */
		$instance = apply_filters( self::filter_name( 'storefront_widget_instance' ), $instance );
		$instance = is_array( $instance ) ? $instance : array();

		$args = MultiCurrencyStorefrontProjectionService::get_default_widget_args();
		/**
		 * Filters the native multi-currency Storefront widget args.
		 *
		 * @param array<string,string> $args Widget args.
		 *
		 * @since 11.0.0
		 */
		$args = apply_filters( self::filter_name( 'storefront_widget_args' ), $args );
		$args = is_array( $args ) ? $args : MultiCurrencyStorefrontProjectionService::get_default_widget_args();

		$widget_markup = $this->get_switcher_projection_service()->get_widget_markup(
			$instance,
			$args,
			$this->get_current_query_args(),
			$this->should_disable_currency_switching()
		);

		return MultiCurrencyStorefrontProjectionService::inject_switcher_into_breadcrumb( $defaults, $widget_markup );
	}

	/**
	 * Add Storefront inline CSS for the native switcher.
	 *
	 * @internal
	 */
	public function handle_wp_enqueue_scripts(): void {
		$style_manifest = MultiCurrencyStorefrontProjectionService::get_inline_style_manifest();

		/**
		 * Filters the native multi-currency Storefront widget CSS.
		 *
		 * @param string $css Inline CSS.
		 *
		 * @since 11.0.0
		 */
		$css = apply_filters( $style_manifest['filter'], $style_manifest['css'] );
		if ( ! is_string( $css ) ) {
			return;
		}

		wp_add_inline_style( $style_manifest['handle'], $css );
	}

	/**
	 * Get the state builder.
	 *
	 * @return MultiCurrencyStateBuilder
	 */
	private function get_state_builder(): MultiCurrencyStateBuilder {
		if ( null === $this->state_builder ) {
			$this->state_builder = new MultiCurrencyStateBuilder(
				new MultiCurrencyLocalizationService(),
				new MultiCurrencyRateService( new CurrencyRateProviderRegistry() ),
				new MultiCurrencyDatabaseCache()
			);
		}

		return $this->state_builder;
	}

	/**
	 * Get the switcher projection service.
	 *
	 * @return MultiCurrencySwitcherProjectionService
	 */
	private function get_switcher_projection_service(): MultiCurrencySwitcherProjectionService {
		if ( null === $this->switcher_projection_service ) {
			$this->switcher_projection_service = new MultiCurrencySwitcherProjectionService( $this->get_state_builder() );
		}

		return $this->switcher_projection_service;
	}

	/**
	 * Get active theme data.
	 *
	 * @return array{stylesheet:string,template:string}
	 */
	private function get_theme_data(): array {
		$theme = is_callable( $this->theme_resolver ) ? call_user_func( $this->theme_resolver ) : wp_get_theme();

		if ( $theme instanceof \WP_Theme ) {
			return array(
				'stylesheet' => $theme->get_stylesheet(),
				'template'   => $theme->get_template(),
			);
		}

		if ( is_array( $theme ) ) {
			return array(
				'stylesheet' => (string) ( $theme['stylesheet'] ?? '' ),
				'template'   => (string) ( $theme['template'] ?? '' ),
			);
		}

		return array(
			'stylesheet' => '',
			'template'   => '',
		);
	}

	/**
	 * Tell whether the Storefront switcher setting is enabled.
	 *
	 * @return bool
	 */
	private function is_using_storefront_switcher(): bool {
		return 'yes' === get_option( self::OPTION_PREFIX . '_enable_storefront_switcher', 'no' );
	}

	/**
	 * Get onboarding simulation variables from the query string.
	 *
	 * @return array<string,mixed>
	 */
	private function get_simulation_variables(): array {
		$query_args    = $this->get_current_query_args();
		$is_simulation = filter_var( $query_args['is_mc_onboarding_simulation'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( ! $is_simulation || ! array_key_exists( 'enable_storefront_switcher', $query_args ) ) {
			return array();
		}

		return array(
			'enable_storefront_switcher' => filter_var( $query_args['enable_storefront_switcher'], FILTER_VALIDATE_BOOLEAN ),
		);
	}

	/**
	 * Tell whether compatibility rules disable switching.
	 *
	 * @return bool
	 */
	private function should_disable_currency_switching(): bool {
		/**
		 * Filters whether native multi-currency switching should be disabled.
		 *
		 * @param bool $should_disable Whether switching should be disabled.
		 *
		 * @since 11.0.0
		 */
		$external_filter_disabled = (bool) apply_filters( self::DISABLE_SWITCHING_FILTER, false );

		return MultiCurrencyCompatibilityProjectionService::should_disable_currency_switching(
			$this->get_current_query_args(),
			false,
			$external_filter_disabled
		);
	}

	/**
	 * Get sanitized current query args.
	 *
	 * @return array<string,mixed>
	 */
	private function get_current_query_args(): array {
		if ( empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return array();
		}

		$query_args = wc_clean( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return is_array( $query_args ) ? $query_args : array();
	}

	/**
	 * Register a filter only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_filter_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_filter( $hook, $callback ) ) {
			add_filter( $hook, $callback, $priority, $accepted_args );
		}
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_action_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}

	/**
	 * Build a multi-currency filter name.
	 *
	 * @param string $suffix Filter suffix.
	 * @return string
	 */
	private static function filter_name( string $suffix ): string {
		return self::OPTION_PREFIX . '_' . $suffix;
	}
}
