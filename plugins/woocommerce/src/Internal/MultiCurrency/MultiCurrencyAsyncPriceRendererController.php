<?php
/**
 * MultiCurrencyAsyncPriceRendererController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAsyncPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native async price renderer hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAsyncPriceRendererController implements RegisterHooksInterface {

	private const OPTION_PREFIX     = 'wcpay_multi_currency';
	private const PRICE_TYPE_FILTER = 'wcpay_multi_currency_async_price_type';
	private const SCRIPT_HANDLE     = 'wcpay-multi-currency-async-renderer';
	private const SCRIPT_PATH       = 'assets/js/frontend/multi-currency-async-renderer';
	private const STYLE_PATH        = 'assets/css/multi-currency-async-renderer.css';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Frontend projection service.
	 *
	 * @var MultiCurrencyFrontendProjectionService|null
	 */
	private ?MultiCurrencyFrontendProjectionService $frontend_projection_service = null;

	/**
	 * Request context.
	 *
	 * @var MultiCurrencyRequestContext|null
	 */
	private ?MultiCurrencyRequestContext $request_context = null;

	/**
	 * Active session resolver.
	 *
	 * @var callable|null
	 */
	private $active_session_resolver = null;

	/**
	 * Asset URL resolver.
	 *
	 * @var callable|null
	 */
	private $asset_url_resolver = null;

	/**
	 * Asset version resolver.
	 *
	 * @var callable|null
	 */
	private $asset_version_resolver = null;

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
	 * Set the frontend projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyFrontendProjectionService $frontend_projection_service Frontend projection service.
	 */
	public function set_frontend_projection_service( MultiCurrencyFrontendProjectionService $frontend_projection_service ): void {
		$this->frontend_projection_service = $frontend_projection_service;
	}

	/**
	 * Set the request context.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyRequestContext $request_context Request context.
	 */
	public function set_request_context( MultiCurrencyRequestContext $request_context ): void {
		$this->request_context = $request_context;
	}

	/**
	 * Set the active session resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $active_session_resolver Active session resolver.
	 */
	public function set_active_session_resolver( callable $active_session_resolver ): void {
		$this->active_session_resolver = $active_session_resolver;
	}

	/**
	 * Set the asset URL resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $asset_url_resolver Asset URL resolver.
	 */
	public function set_asset_url_resolver( callable $asset_url_resolver ): void {
		$this->asset_url_resolver = $asset_url_resolver;
	}

	/**
	 * Set the asset version resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $asset_version_resolver Asset version resolver.
	 */
	public function set_asset_version_resolver( callable $asset_version_resolver ): void {
		$this->asset_version_resolver = $asset_version_resolver;
	}

	/**
	 * Register async price renderer hooks.
	 */
	public function register() {
		$request_context = $this->get_request_context();
		if (
			! $this->arbiter->should_core_register()
			|| ! $request_context->should_register_frontend_hooks()
			|| $request_context->is_store_api_request()
			|| ! $this->is_using_auto_currency_switching()
			|| $this->has_pending_currency_switch()
		) {
			return;
		}

		if (
			! MultiCurrencyAsyncPriceProjectionService::should_activate(
				$this->get_frontend_projection_service()->is_cache_optimized_mode(),
				is_admin(),
				wp_doing_cron(),
				$request_context->is_admin_api_request(),
				$this->has_active_session()
			)
		) {
			return;
		}

		$this->add_filter_once( 'wc_price', array( $this, 'handle_wc_price' ), 999, 5 );
		$this->add_filter_once( 'woocommerce_format_sale_price', array( $this, 'handle_woocommerce_format_sale_price' ), 999, 3 );
		$this->add_filter_once( 'woocommerce_format_price_range', array( $this, 'handle_woocommerce_format_price_range' ), 999, 3 );
		$this->add_action_once( 'wp_enqueue_scripts', array( $this, 'handle_wp_enqueue_scripts' ) );
	}

	/**
	 * Wrap formatted prices with async renderer skeleton markup.
	 *
	 * @internal
	 *
	 * @param string              $price_html        Formatted price HTML.
	 * @param int|float|string    $price             Raw price.
	 * @param array<string,mixed> $args           Arguments passed to wc_price().
	 * @param int|float|string    $unformatted_price Raw unformatted price.
	 * @param int|float|string    $original_price    Original price before filtering.
	 * @return string
	 */
	public function handle_wc_price( string $price_html, $price, array $args, $unformatted_price, $original_price ): string {
		unset( $original_price );

		/**
		 * Filters the native async multi-currency price type.
		 *
		 * @param string           $price_type Price type.
		 * @param int|float|string $price      Raw price.
		 * @param array<string,mixed> $args    Arguments passed to wc_price().
		 *
		 * @since 11.0.0
		 */
		$price_type = apply_filters( self::PRICE_TYPE_FILTER, 'product', $price, $args );
		$price_type = is_string( $price_type ) ? $price_type : 'product';

		return MultiCurrencyAsyncPriceProjectionService::wrap_price_with_skeleton(
			$price_html,
			$unformatted_price,
			$price_type
		);
	}

	/**
	 * Annotate sale price screen-reader text for async conversion.
	 *
	 * @internal
	 *
	 * @param string           $price_html    Sale price HTML.
	 * @param int|float|string $regular_price Regular price.
	 * @param int|float|string $sale_price    Sale price.
	 * @return string
	 */
	public function handle_woocommerce_format_sale_price( string $price_html, $regular_price, $sale_price ): string {
		return MultiCurrencyAsyncPriceProjectionService::annotate_sale_price_screen_reader_text(
			$price_html,
			$regular_price,
			$sale_price
		);
	}

	/**
	 * Annotate price range screen-reader text for async conversion.
	 *
	 * @internal
	 *
	 * @param string           $price_html Price range HTML.
	 * @param int|float|string $from       Minimum price.
	 * @param int|float|string $to         Maximum price.
	 * @return string
	 */
	public function handle_woocommerce_format_price_range( string $price_html, $from, $to ): string {
		return MultiCurrencyAsyncPriceProjectionService::annotate_price_range_screen_reader_text(
			$price_html,
			$from,
			$to
		);
	}

	/**
	 * Enqueue async renderer assets and localized config.
	 *
	 * @internal
	 */
	public function handle_wp_enqueue_scripts(): void {
		$manifest = MultiCurrencyAsyncPriceProjectionService::get_asset_manifest(
			rest_url( 'wc/v3/payments/multi-currency/public/config' ),
			MultiCurrencyAsyncPriceProjectionService::get_default_currency_config(
				get_woocommerce_currency_symbol(),
				wc_get_price_decimals(),
				wc_get_price_decimal_separator(),
				wc_get_price_thousand_separator(),
				(string) get_option( 'woocommerce_currency_pos', 'left' )
			),
			$this->get_asset_url( self::STYLE_PATH ),
			$this->get_asset_version( self::STYLE_PATH )
		);

		$script_handle = self::SCRIPT_HANDLE;

		wp_register_script(
			$script_handle,
			$this->get_asset_url( self::SCRIPT_PATH . $this->get_script_suffix() . '.js' ),
			array( 'wp-polyfill' ),
			$this->get_asset_version( self::SCRIPT_PATH . '.js' ),
			array( 'in_footer' => true )
		);
		wp_localize_script(
			$script_handle,
			$manifest['script']['localized_object'],
			$manifest['script']['config']
		);
		wp_enqueue_script( $script_handle );

		wp_enqueue_style(
			$script_handle,
			$manifest['style']['url'],
			array(),
			$manifest['style']['version']
		);
	}

	/**
	 * Get the frontend projection service.
	 *
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function get_frontend_projection_service(): MultiCurrencyFrontendProjectionService {
		if ( null === $this->frontend_projection_service ) {
			$this->frontend_projection_service = wc_get_container()
				->get( MultiCurrencyProjectionServiceFactory::class )
				->create_frontend_projection_service();
		}

		return $this->frontend_projection_service;
	}

	/**
	 * Get the request context.
	 *
	 * @return MultiCurrencyRequestContext
	 */
	private function get_request_context(): MultiCurrencyRequestContext {
		if ( null === $this->request_context ) {
			$this->request_context = new MultiCurrencyRequestContext();
		}

		return $this->request_context;
	}

	/**
	 * Tell whether a cookie-backed WooCommerce session is already active.
	 *
	 * @return bool
	 */
	private function has_active_session(): bool {
		if ( is_callable( $this->active_session_resolver ) ) {
			return (bool) call_user_func( $this->active_session_resolver );
		}

		$session = WC()->session;

		return is_object( $session )
			&& method_exists( $session, 'has_session' )
			&& $session->has_session();
	}

	/**
	 * Tell whether automatic currency switching is enabled.
	 *
	 * @return bool
	 */
	private function is_using_auto_currency_switching(): bool {
		return 'yes' === get_option( self::OPTION_PREFIX . '_enable_auto_currency', 'no' );
	}

	/**
	 * Tell whether this request is explicitly switching currencies.
	 *
	 * @return bool
	 */
	private function has_pending_currency_switch(): bool {
		return isset( $_GET['currency'] ) && is_string( $_GET['currency'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['currency'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get an asset URL.
	 *
	 * @param string $path Asset path relative to the plugin root.
	 * @return string
	 */
	private function get_asset_url( string $path ): string {
		if ( is_callable( $this->asset_url_resolver ) ) {
			return (string) call_user_func( $this->asset_url_resolver, $path );
		}

		return plugins_url( $path, WC_PLUGIN_FILE );
	}

	/**
	 * Get an asset version.
	 *
	 * @param string $path Asset path relative to the plugin root.
	 * @return string
	 */
	private function get_asset_version( string $path ): string {
		if ( is_callable( $this->asset_version_resolver ) ) {
			return (string) call_user_func( $this->asset_version_resolver, $path );
		}

		unset( $path );

		return Constants::get_constant( 'WC_VERSION' );
	}

	/**
	 * Get the script suffix for debug/non-debug builds.
	 *
	 * @return string
	 */
	private function get_script_suffix(): string {
		return Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
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
}
