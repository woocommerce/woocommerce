<?php
/**
 * MultiCurrencyRestRequestOverrideController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native non-Store REST request-local currency override filters.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRestRequestOverrideController implements RegisterHooksInterface {

	private const FILTER_OVERRIDE_SELECTED_CURRENCY   = 'wcpay_multi_currency_override_selected_currency';
	private const FILTER_SHOULD_RETURN_STORE_CURRENCY = 'wcpay_multi_currency_should_return_store_currency';
	private const FILTER_SHOULD_CONVERT_PRODUCT_PRICE = 'wcpay_multi_currency_should_convert_product_price';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Request context.
	 *
	 * @var MultiCurrencyRequestContext|null
	 */
	private ?MultiCurrencyRequestContext $request_context = null;

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
	 * Register non-Store REST request-local override filters.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() || ! $this->get_request_context()->should_register_rest_request_overrides() ) {
			return;
		}

		if ( isset( $_GET['currency'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->add_filter_once( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $this, 'get_currency_from_query_param' ) );
			return;
		}

		$this->add_filter_once( self::FILTER_SHOULD_RETURN_STORE_CURRENCY, '__return_true' );
		$this->add_filter_once( self::FILTER_SHOULD_CONVERT_PRODUCT_PRICE, '__return_false' );
		$this->add_filter_once( self::FILTER_OVERRIDE_SELECTED_CURRENCY, array( $this, 'get_store_currency_code' ) );
	}

	/**
	 * Get a request-local selected currency from the REST query parameter.
	 *
	 * @internal
	 *
	 * @param mixed $currency_code Existing override value.
	 * @return string|false Uppercase requested currency, or false when unavailable.
	 */
	public function get_currency_from_query_param( $currency_code = false ) {
		unset( $currency_code );

		if ( ! isset( $_GET['currency'] ) || ! is_scalar( $_GET['currency'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		$currency_code = sanitize_text_field( (string) wp_unslash( $_GET['currency'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return strtoupper( trim( $currency_code ) );
	}

	/**
	 * Get the store default currency code for non-Store REST requests.
	 *
	 * @internal
	 *
	 * @param mixed $currency_code Existing override value.
	 * @return string
	 */
	public function get_store_currency_code( $currency_code = false ): string {
		unset( $currency_code );

		return strtoupper( get_woocommerce_currency() );
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
}
