<?php
/**
 * MultiCurrencyShadowMode class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Shadow;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Abstract_Order;
use WC_Order;

/**
 * Records native multi-currency shadow output while the WooPayments plugin owns multi-currency.
 *
 * B1e shadow mode is intentionally read-only: it observes after plugin-owned
 * multi-currency order/refund meta hooks have run, reads the persisted meta
 * surface, computes native B1d candidates, and logs a machine-readable
 * comparison outside the order. It must not save orders/refunds, register price
 * filters, initialize sessions, or refresh rate caches.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyShadowMode implements RegisterHooksInterface {

	/**
	 * Filter that enables read-only native multi-currency shadow mode.
	 *
	 * @var string
	 */
	const FILTER_SHADOW_ENABLED = 'woocommerce_native_multi_currency_shadow_mode_enabled';

	/**
	 * Filter that enables full actual/native-computed surfaces in shadow logs.
	 *
	 * @var string
	 */
	const FILTER_LOG_FULL_SURFACES = 'woocommerce_native_multi_currency_shadow_mode_log_full_surfaces';

	/**
	 * WC logger source for machine-readable shadow comparison records.
	 *
	 * @var string
	 */
	const LOG_SOURCE = 'native-multi-currency-shadow';

	/**
	 * Observed WooPayments multi-currency order/refund meta keys.
	 *
	 * @var string[]
	 */
	private const MULTI_CURRENCY_META_KEYS = array(
		MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE,
		MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY,
		MultiCurrencyPriceProjectionService::META_KEY_STRIPE_EXCHANGE_RATE,
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Native price/meta projection service.
	 *
	 * @var MultiCurrencyPriceProjectionService|null
	 */
	private ?MultiCurrencyPriceProjectionService $projection_service = null;

	/**
	 * Multi-currency surface differ.
	 *
	 * @var MultiCurrencySurfaceDiffer
	 */
	private MultiCurrencySurfaceDiffer $differ;

	/**
	 * Legacy proxy for mockable global calls.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter $arbiter      Runtime owner arbiter.
	 * @param MultiCurrencySurfaceDiffer  $differ       Multi-currency surface differ.
	 * @param LegacyProxy                 $legacy_proxy Legacy proxy.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, MultiCurrencySurfaceDiffer $differ, LegacyProxy $legacy_proxy ): void {
		$this->arbiter      = $arbiter;
		$this->differ       = $differ;
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Set the native projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions. Production
	 * code lazily builds the default read-only projection graph.
	 *
	 * @param MultiCurrencyPriceProjectionService $projection_service Native price/meta projection service.
	 */
	public function set_projection_service( MultiCurrencyPriceProjectionService $projection_service ): void {
		$this->projection_service = $projection_service;
	}

	/**
	 * Register read-only multi-currency shadow hooks.
	 */
	public function register() {
		if ( ! $this->should_register_shadow_hooks() ) {
			return;
		}

		$this->add_shadow_action_once( 'woocommerce_new_order', array( $this, 'handle_woocommerce_new_order' ), 100, 2 );
		$this->add_shadow_action_once( 'woocommerce_order_refunded', array( $this, 'handle_woocommerce_order_refunded' ), 100, 2 );
	}

	/**
	 * Tell whether shadow mode should register hooks.
	 *
	 * @return bool
	 */
	public function should_register_shadow_hooks(): bool {
		return $this->is_shadow_mode_enabled() && $this->arbiter->should_plugin_register();
	}

	/**
	 * Tell whether shadow mode is enabled.
	 *
	 * @return bool
	 */
	public function is_shadow_mode_enabled(): bool {
		/**
		 * Filters whether read-only native multi-currency shadow mode is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether shadow mode is enabled. Default false.
		 */
		return (bool) apply_filters( self::FILTER_SHADOW_ENABLED, false );
	}

	/**
	 * Observe the WooCommerce new-order hook after plugin-owned effects.
	 *
	 * @param int   $order_id Order ID.
	 * @param mixed $order    Order object.
	 */
	public function handle_woocommerce_new_order( int $order_id, $order = null ): void {
		if ( ! $order instanceof WC_Order ) {
			$order = $this->legacy_proxy->call_function( 'wc_get_order', $order_id );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->record_order_shadow( $order, 'woocommerce_new_order' );
	}

	/**
	 * Observe the WooCommerce order-refunded hook after plugin-owned effects.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function handle_woocommerce_order_refunded( int $order_id, int $refund_id ): void {
		$order = $this->legacy_proxy->call_function( 'wc_get_order', $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$refund = $this->legacy_proxy->call_function( 'wc_get_order', $refund_id );
		if ( ! $refund instanceof WC_Abstract_Order ) {
			return;
		}

		$this->record_refund_shadow( $order, $refund, 'woocommerce_order_refunded' );
	}

	/**
	 * Record a shadow comparison for order multi-currency meta.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $trigger Trigger name.
	 * @return MultiCurrencyShadowComparison Shadow comparison.
	 */
	public function record_order_shadow( WC_Order $order, string $trigger ): MultiCurrencyShadowComparison {
		$start           = microtime( true );
		$actual          = $this->read_meta_surface( $order );
		$native_computed = $this->build_meta_surface( $this->get_projection_service()->get_order_meta_candidates( (string) $order->get_currency() ) );
		$diff            = $this->differ->diff( $native_computed, $actual );
		$elapsed_ms      = ( microtime( true ) - $start ) * 1000;

		$comparison = new MultiCurrencyShadowComparison( $trigger, (int) $order->get_id(), $actual, $native_computed, $diff, $elapsed_ms );
		$this->log_comparison( $comparison );

		return $comparison;
	}

	/**
	 * Record a shadow comparison for refund multi-currency meta.
	 *
	 * @param WC_Order          $order   Source order object.
	 * @param WC_Abstract_Order $refund  Refund object.
	 * @param string            $trigger Trigger name.
	 * @return MultiCurrencyShadowComparison Shadow comparison.
	 */
	public function record_refund_shadow( WC_Order $order, WC_Abstract_Order $refund, string $trigger ): MultiCurrencyShadowComparison {
		$start           = microtime( true );
		$actual          = $this->read_meta_surface( $refund );
		$native_computed = $this->build_meta_surface( $this->get_projection_service()->get_refund_meta_candidates( $order ) );
		$diff            = $this->differ->diff( $native_computed, $actual );
		$elapsed_ms      = ( microtime( true ) - $start ) * 1000;

		$comparison = new MultiCurrencyShadowComparison(
			$trigger,
			(int) $order->get_id(),
			$actual,
			$native_computed,
			$diff,
			$elapsed_ms,
			'refund',
			(int) $refund->get_id()
		);
		$this->log_comparison( $comparison );

		return $comparison;
	}

	/**
	 * Read a normalized multi-currency meta surface from an order or refund.
	 *
	 * @param WC_Abstract_Order $order Order or refund object.
	 * @return array<string,mixed>
	 */
	private function read_meta_surface( WC_Abstract_Order $order ): array {
		$meta = array();

		foreach ( self::MULTI_CURRENCY_META_KEYS as $key ) {
			if ( ! $order->meta_exists( $key ) ) {
				continue;
			}

			$meta[ $key ] = $this->normalize_meta_value( $order->get_meta( $key, true ) );
		}

		ksort( $meta );

		return array(
			'meta' => $meta,
		);
	}

	/**
	 * Build a normalized multi-currency meta surface from native candidates.
	 *
	 * @param array<string,mixed> $meta_candidates Native meta candidates.
	 * @return array<string,mixed>
	 */
	private function build_meta_surface( array $meta_candidates ): array {
		$meta = array();

		foreach ( $meta_candidates as $key => $value ) {
			$meta[ $key ] = $this->normalize_meta_value( $value );
		}

		ksort( $meta );

		return array(
			'meta' => $meta,
		);
	}

	/**
	 * Get the native projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->projection_service ) {
			$localization_service   = $this->create_read_only_localization_service();
			$rate_provider_registry = new CurrencyRateProviderRegistry();
			$state_builder          = new MultiCurrencyStateBuilder(
				$localization_service,
				new MultiCurrencyRateService( $rate_provider_registry ),
				new MultiCurrencyDatabaseCache()
			);

			$this->projection_service = new MultiCurrencyPriceProjectionService(
				$state_builder,
				new MultiCurrencyPriceCalculator( $localization_service )
			);
		}

		return $this->projection_service;
	}

	/**
	 * Create a localization adapter that reads locale data without writing transients.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_read_only_localization_service(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Currency formatting map.
			 *
			 * @var array<string,array<string,array<string,mixed>>>|null
			 */
			private ?array $currency_format = null;

			/**
			 * Country locale information.
			 *
			 * @var array<string,array<string,mixed>>|null
			 */
			private ?array $locale_info = null;

			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				$this->load_locale_data();

				$currency_code   = strtoupper( (string) $currency_code );
				$currency_format = array(
					'currency_pos' => 'left',
					'thousand_sep' => ',',
					'decimal_sep'  => '.',
					'num_decimals' => 2,
				);
				$locale          = get_user_locale();

				$currency_options = $this->currency_format[ $currency_code ] ?? null;
				if ( is_array( $currency_options ) ) {
					$currency_format = $currency_options[ $locale ] ?? $currency_options['default'] ?? $currency_format;
				}

				/**
				 * Filter to edit formatting for a specific currency.
				 *
				 * @since 11.0.0
				 *
				 * @param array<string,mixed> $currency_format The currency format settings.
				 * @param string              $locale          The user's locale.
				 */
				return apply_filters( 'wcpay_' . strtolower( $currency_code ) . '_format', $currency_format, $locale );
			}

			/**
			 * Get locale data for a country.
			 *
			 * @param string $country Country code.
			 * @return array<string,mixed>
			 */
			public function get_country_locale_data( $country ): array {
				$this->load_locale_data();

				return $this->locale_info[ strtoupper( (string) $country ) ] ?? array();
			}

			/**
			 * Load locale and currency formatting data without writing transients.
			 */
			private function load_locale_data(): void {
				if ( null !== $this->locale_info && null !== $this->currency_format ) {
					return;
				}

				$locale_info_path      = WC()->plugin_path() . '/i18n/locale-info.php';
				$locale_info           = file_exists( $locale_info_path ) ? include $locale_info_path : array();
				$this->locale_info     = is_array( $locale_info ) ? $locale_info : array();
				$this->currency_format = array();

				foreach ( $this->locale_info as $country_data ) {
					if ( empty( $country_data['currency_code'] ) || empty( $country_data['locales'] ) || ! is_array( $country_data['locales'] ) ) {
						continue;
					}

					$currency_code = strtoupper( (string) $country_data['currency_code'] );
					foreach ( $country_data['locales'] as $locale => $locale_data ) {
						if ( empty( $locale_data ) || ! is_array( $locale_data ) ) {
							continue;
						}

						$this->currency_format[ $currency_code ][ $locale ] = array(
							'currency_pos' => $locale_data['currency_pos'] ?? 'left',
							'thousand_sep' => $locale_data['thousand_sep'] ?? ',',
							'decimal_sep'  => $locale_data['decimal_sep'] ?? '.',
							'num_decimals' => $country_data['num_decimals'] ?? 2,
						);
					}
				}
			}
		};
	}

	/**
	 * Normalize meta values for stable machine-readable comparisons.
	 *
	 * @param mixed $value Meta value.
	 * @return string Normalized scalar value.
	 */
	private function normalize_meta_value( $value ): string {
		if ( is_scalar( $value ) || null === $value ) {
			return (string) $value;
		}

		$encoded = wp_json_encode( $value );
		return false === $encoded ? '' : $encoded;
	}

	/**
	 * Log a machine-readable shadow comparison out of band.
	 *
	 * @param MultiCurrencyShadowComparison $comparison Shadow comparison.
	 */
	private function log_comparison( MultiCurrencyShadowComparison $comparison ): void {
		$logger = $this->legacy_proxy->call_function( 'wc_get_logger' );
		if ( ! is_object( $logger ) || ! is_callable( array( $logger, 'debug' ) ) ) {
			return;
		}

		/**
		 * Filters whether multi-currency shadow logs include full actual/native-computed surfaces.
		 *
		 * Defaults to false so production canaries record compact diffs and
		 * surface hashes rather than duplicating full order/refund meta surfaces.
		 *
		 * @since 11.0.0
		 *
		 * @param bool                          $include_surfaces Whether to include full surfaces. Default false.
		 * @param MultiCurrencyShadowComparison $comparison       Shadow comparison.
		 */
		$include_surfaces = (bool) apply_filters( self::FILTER_LOG_FULL_SURFACES, false, $comparison );

		$message = wp_json_encode( $comparison->to_log_array( $include_surfaces ) );
		if ( false === $message ) {
			return;
		}

		$logger->debug(
			$message,
			array(
				'source' => self::LOG_SOURCE,
			)
		);
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_shadow_action_once( string $hook, callable $callback, int $priority, int $accepted_args ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
