<?php
/**
 * MultiCurrencySelectedCurrencyController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySelectedCurrencyPersistenceService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyUserSettingsProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native selected-currency entry points when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySelectedCurrencyController implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Selected currency persistence service.
	 *
	 * @var MultiCurrencySelectedCurrencyPersistenceService|null
	 */
	private ?MultiCurrencySelectedCurrencyPersistenceService $persistence_service = null;

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
	 * Set the selected-currency persistence service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencySelectedCurrencyPersistenceService $persistence_service Persistence service.
	 */
	public function set_persistence_service( MultiCurrencySelectedCurrencyPersistenceService $persistence_service ): void {
		$this->persistence_service = $persistence_service;
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
	 * Register selected-currency hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->get_request_context()->should_register_selected_currency_entry_hooks() ) {
			$this->add_action_once( 'init', array( $this, 'handle_init' ), 11 );
			$this->add_action_once( 'woocommerce_created_customer', array( $this, 'handle_woocommerce_created_customer' ) );
		}

		$this->add_action_once( 'woocommerce_edit_account_form', array( $this, 'handle_woocommerce_edit_account_form' ) );
		$this->add_action_once( 'woocommerce_save_account_details', array( $this, 'handle_woocommerce_save_account_details' ) );
	}

	/**
	 * Handle explicit selected-currency URL changes.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! isset( $_GET['currency'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$currency_code = wc_clean( wp_unslash( $_GET['currency'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_scalar( $currency_code ) ) {
			return;
		}

		$this->get_persistence_service()->update_selected_currency( strtoupper( trim( (string) $currency_code ) ) );
		$this->clear_url_price_params();
	}

	/**
	 * Handle customer creation during checkout.
	 *
	 * @internal
	 *
	 * @param mixed $customer_id Customer ID.
	 */
	public function handle_woocommerce_created_customer( $customer_id ): void {
		$this->get_persistence_service()->set_new_customer_currency_meta( absint( $customer_id ) );
	}

	/**
	 * Clear stale URL price filters after a browser currency switch.
	 */
	private function clear_url_price_params(): void {
		if (
			( function_exists( 'WC' ) && WC()->is_rest_api_request() )
			|| ! empty( $_GET['rest_route'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			return;
		}

		if ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url = remove_query_arg( array( 'min_price', 'max_price' ) );

			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Render the My Account presentment currency field.
	 *
	 * @internal
	 */
	public function handle_woocommerce_edit_account_form(): void {
		$service = $this->get_persistence_service();
		if ( ! $service->has_additional_currencies_enabled() ) {
			return;
		}

		$markup = MultiCurrencyUserSettingsProjectionService::get_presentment_currency_field_markup(
			$service->get_enabled_currency_options(),
			$service->get_selected_currency_code()
		);

		// Projection markup escapes the individual attributes and text while preserving WooPayments-compatible form HTML.
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save the My Account presentment currency field.
	 *
	 * @internal
	 */
	public function handle_woocommerce_save_account_details(): void {
		$posted_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$intent      = MultiCurrencyUserSettingsProjectionService::get_save_presentment_currency_intent( $posted_data );

		if ( $intent['should_update'] && is_string( $intent['currency_code'] ) ) {
			$this->get_persistence_service()->update_selected_currency( $intent['currency_code'] );
		}
	}

	/**
	 * Get the persistence service.
	 *
	 * @return MultiCurrencySelectedCurrencyPersistenceService
	 */
	private function get_persistence_service(): MultiCurrencySelectedCurrencyPersistenceService {
		if ( null === $this->persistence_service ) {
			$localization_service      = new MultiCurrencyLocalizationService();
			$this->persistence_service = new MultiCurrencySelectedCurrencyPersistenceService(
				new MultiCurrencyStateBuilder(
					$localization_service,
					new MultiCurrencyRateService( new CurrencyRateProviderRegistry() ),
					new MultiCurrencyDatabaseCache()
				)
			);
		}

		return $this->persistence_service;
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
