<?php
/**
 * MultiCurrencySelectedCurrencyController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySelectedCurrencyPersistenceService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyUserSettingsProjectionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native selected-currency entry points when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySelectedCurrencyController implements RegisterHooksInterface {

	private const OPTION_PREFIX             = 'wcpay_multi_currency';
	private const RENDERING_MODE_CACHE      = 'cache';
	private const CACHE_FEATURE_FLAG_OPTION = '_wcpay_feature_mc_cache_optimized';
	private const DISABLE_SWITCHING_FILTER  = 'wcpay_multi_currency_should_disable_currency_switching';

	private const FILTER_OVERRIDE_NOTICE_COUNTRY       = 'wcpay_multi_currency_override_notice_country';
	private const FILTER_OVERRIDE_NOTICE_CURRENCY_NAME = 'wcpay_multi_currency_override_notice_currency_name';

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
	 * Geolocation service.
	 *
	 * @var MultiCurrencyGeolocationService|null
	 */
	private ?MultiCurrencyGeolocationService $geolocation_service = null;

	/**
	 * Runtime service factory.
	 *
	 * @var MultiCurrencyRuntimeServiceFactory
	 */
	private MultiCurrencyRuntimeServiceFactory $runtime_service_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter        $arbiter                 Runtime owner arbiter.
	 * @param MultiCurrencyRuntimeServiceFactory $runtime_service_factory Runtime service factory.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencyRuntimeServiceFactory $runtime_service_factory ): void {
		$this->arbiter                 = $arbiter;
		$this->runtime_service_factory = $runtime_service_factory;
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
	 * Set the geolocation service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyGeolocationService $geolocation_service Geolocation service.
	 */
	public function set_geolocation_service( MultiCurrencyGeolocationService $geolocation_service ): void {
		$this->geolocation_service = $geolocation_service;
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
			$this->add_action_once( 'init', array( $this, 'handle_geolocation_init' ), 12 );
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
	 * Handle automatic selected-currency changes from geolocation.
	 *
	 * @internal
	 */
	public function handle_geolocation_init(): void {
		if (
			! $this->is_using_auto_currency_switching()
			|| $this->should_disable_currency_switching()
			|| $this->should_use_async_rendering()
		) {
			return;
		}

		$this->add_action_once( 'wp_footer', array( $this, 'handle_wp_footer' ) );

		if ( $this->get_persistence_service()->has_stored_currency_code() ) {
			return;
		}

		$currency_code = $this->get_geolocation_service()->get_currency_by_customer_location();
		if ( ! is_string( $currency_code ) || '' === trim( $currency_code ) ) {
			return;
		}

		$this->get_persistence_service()->update_selected_currency( strtoupper( trim( $currency_code ) ), false );
	}

	/**
	 * Render the geolocation currency update notice.
	 *
	 * @internal
	 */
	public function handle_wp_footer(): void {
		$current_currency_code    = strtoupper( $this->get_persistence_service()->get_selected_currency_code() );
		$store_currency_code      = strtoupper( get_woocommerce_currency() );
		$geolocated_currency_code = $this->get_geolocation_service()->get_currency_by_customer_location();

		if (
			$store_currency_code === $current_currency_code
			|| ! is_string( $geolocated_currency_code )
			|| strtoupper( $geolocated_currency_code ) !== $current_currency_code
		) {
			return;
		}

		$country_code          = $this->get_geolocation_service()->get_country_by_customer_location();
		$countries             = function_exists( 'WC' ) && is_object( WC()->countries ) && method_exists( WC()->countries, 'get_countries' )
			? WC()->countries->get_countries()
			: array();
		$currencies            = get_woocommerce_currencies();
		$country_name          = $countries[ $country_code ] ?? $country_code;
		$current_currency_name = $currencies[ $current_currency_code ] ?? $current_currency_code;
		$store_currency_name   = $currencies[ $store_currency_code ] ?? $store_currency_code;

		/**
		 * Filters the country name displayed in the native multi-currency geolocation notice.
		 *
		 * @param string $country_name Country name.
		 *
		 * @since 11.0.0
		 */
		$country_name = apply_filters( self::FILTER_OVERRIDE_NOTICE_COUNTRY, $country_name );

		/**
		 * Filters the currency name displayed in the native multi-currency geolocation notice.
		 *
		 * @param string $current_currency_name Current currency name.
		 *
		 * @since 11.0.0
		 */
		$current_currency_name = apply_filters( self::FILTER_OVERRIDE_NOTICE_CURRENCY_NAME, $current_currency_name );

		$message = sprintf(
			/* translators: %1$s: User country. %2$s: Selected currency name. %3$s: Store currency name. %4$s: Link to switch currency. */
			__( 'We noticed you\'re visiting from %1$s. We\'ve updated our prices to %2$s for your shopping convenience. <a href="%4$s">Use %3$s instead.</a>', 'woocommerce' ),
			esc_html( (string) $country_name ),
			esc_html( (string) $current_currency_name ),
			esc_html( $store_currency_name ),
			esc_url( '?currency=' . $store_currency_code )
		);
		$notice_id = md5( $message );

		echo '<p class="woocommerce-store-notice demo_store" data-notice-id="' . esc_attr( $notice_id . 2 ) . '" style="display:none;">';
		echo wp_kses_post( $message );
		echo ' <a href="#" class="woocommerce-store-notice__dismiss-link">' . esc_html__( 'Dismiss', 'woocommerce' ) . '</a></p>';
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
			$this->persistence_service = $this->runtime_service_factory->create_selected_currency_persistence_service();
		}

		return $this->persistence_service;
	}

	/**
	 * Get the geolocation service.
	 *
	 * @return MultiCurrencyGeolocationService
	 */
	private function get_geolocation_service(): MultiCurrencyGeolocationService {
		if ( null === $this->geolocation_service ) {
			$this->geolocation_service = $this->runtime_service_factory->create_geolocation_service();
		}

		return $this->geolocation_service;
	}

	/**
	 * Get the request context.
	 *
	 * @return MultiCurrencyRequestContext
	 */
	private function get_request_context(): MultiCurrencyRequestContext {
		if ( null === $this->request_context ) {
			$this->request_context = $this->runtime_service_factory->create_request_context();
		}

		return $this->request_context;
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
	 * Tell whether compatibility rules disable switching.
	 *
	 * @return bool
	 */
	private function should_disable_currency_switching(): bool {
		$query_args = array();
		if ( isset( $_GET['pay_for_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['pay_for_order'] = wc_clean( wp_unslash( $_GET['pay_for_order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Filters whether native multi-currency switching should be disabled.
		 *
		 * @param bool $should_disable Whether switching should be disabled.
		 *
		 * @since 11.0.0
		 */
		$external_filter_disabled = (bool) apply_filters( self::DISABLE_SWITCHING_FILTER, false );

		return MultiCurrencyCompatibilityProjectionService::should_disable_currency_switching( $query_args, false, $external_filter_disabled );
	}

	/**
	 * Tell whether async rendering should handle geolocation switching client-side.
	 *
	 * @return bool
	 */
	private function should_use_async_rendering(): bool {
		return $this->is_cache_optimized_mode()
			&& ! $this->has_active_session()
			&& ! $this->get_request_context()->is_store_api_request();
	}

	/**
	 * Tell whether cache-optimized mode is active.
	 *
	 * @return bool
	 */
	private function is_cache_optimized_mode(): bool {
		return '1' === get_option( self::CACHE_FEATURE_FLAG_OPTION, '0' )
			&& self::RENDERING_MODE_CACHE === get_option( self::OPTION_PREFIX . '_rendering_mode', MultiCurrencyFrontendProjectionService::RENDERING_MODE_SPEED );
	}

	/**
	 * Tell whether a cookie-backed WooCommerce session exists.
	 *
	 * @return bool
	 */
	private function has_active_session(): bool {
		return function_exists( 'WC' )
			&& is_object( WC()->session )
			&& method_exists( WC()->session, 'has_session' )
			&& WC()->session->has_session();
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
