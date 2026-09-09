<?php
/**
 * Finance service.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Balance;
use Automattic\WooCommerce\Admin\Payments\Finance\BalanceProviderInterface;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataException;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataProviderInterface;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataProviderRegistry;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataQuery;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Payout;
use Automattic\WooCommerce\Admin\Payments\Finance\PayoutsProviderInterface;
use Automattic\WooCommerce\Enums\FinanceDataSource;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves finance data providers and fetches their data for the REST API.
 *
 * @internal
 */
class FinanceService {

	/**
	 * The interface a provider must implement for each finance data type.
	 *
	 * @var array<string, class-string>
	 */
	private const DATA_TYPE_INTERFACES = array(
		FinanceDataSource::BALANCE => BalanceProviderInterface::class,
		FinanceDataSource::PAYOUTS => PayoutsProviderInterface::class,
	);

	/**
	 * Logging source.
	 *
	 * @var string
	 */
	private const LOG_SOURCE = 'payments-finance';

	/**
	 * The provider registry.
	 *
	 * @var FinanceDataProviderRegistry
	 */
	private FinanceDataProviderRegistry $registry;

	/**
	 * The serializer.
	 *
	 * @var FinanceDataSerializer
	 */
	private FinanceDataSerializer $serializer;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FinanceDataProviderRegistry $registry   The provider registry.
	 * @param FinanceDataSerializer       $serializer The serializer.
	 */
	final public function init( FinanceDataProviderRegistry $registry, FinanceDataSerializer $serializer ): void {
		$this->registry   = $registry;
		$this->serializer = $serializer;
	}

	/**
	 * Get the payment gateways that can return finance data, with the data types core can serve for each.
	 *
	 * Unregistered gateways and unsupported data types are not included.
	 *
	 * @return array The providers list response.
	 *
	 * @since 11.2.0
	 */
	public function get_providers(): array {
		$gateways  = $this->get_all_gateways();
		$providers = array();

		$home_url = get_home_url();

		foreach ( $this->registry->get_providers() as $gateway_id => $provider ) {
			$gateway = $gateways[ $gateway_id ] ?? null;
			if ( ! $gateway instanceof WC_Payment_Gateway ) {
				$this->log_debug( sprintf( 'Skipping the finance data provider for "%s": no such payment gateway is registered.', $gateway_id ) );
				continue;
			}

			$data_types = array();
			foreach ( $this->get_declared_data_types( $provider, $gateway_id ) as $data_type => $version ) {
				if ( ! FinanceDataSchemas::is_supported( $data_type, $version ) ) {
					$this->log_debug( sprintf( 'Skipping "%1$s" data for "%2$s": data-model version %3$d is not supported.', $data_type, $gateway_id, $version ) );
					continue;
				}
				if ( ! $this->implements_data_type( $provider, $data_type ) ) {
					$this->log_debug( sprintf( 'Skipping "%1$s" data for "%2$s": the provider does not implement %3$s.', $data_type, $gateway_id, self::DATA_TYPE_INTERFACES[ $data_type ] ) );
					continue;
				}

				$data_types[] = array(
					'type'           => $data_type,
					'schema_version' => $version,
				);
			}

			if ( empty( $data_types ) ) {
				continue;
			}

			$icon_url = $provider->get_icon_url();
			if ( ! str_starts_with( $icon_url, $home_url ) || str_contains( $icon_url, '..' ) ) {
				$icon_url = null;
			}

			$providers[] = array(
				'provider_id' => $gateway_id,
				'title'       => (string) $gateway->get_method_title(),
				'icon_url'    => $icon_url,
				'data_types'  => $data_types,
			);
		}

		return array( 'providers' => $providers );
	}

	/**
	 * Get the serialized balances of a payment gateway.
	 *
	 * @param string $gateway_id The payment gateway id.
	 * @return array The balances response.
	 * @throws FinanceDataException When the gateway has no provider, does not support balances, or the provider fails.
	 *
	 * @since 11.2.0
	 */
	public function get_balances( string $gateway_id ): array {
		list( $provider, $version ) = $this->resolve_provider( $gateway_id, FinanceDataSource::BALANCE );

		/**
		 * The provider, narrowed to the balance interface by resolve_provider().
		 *
		 * @var BalanceProviderInterface $provider
		 */
		$page = $this->call_provider( $gateway_id, fn() => $provider->get_balances( new FinanceDataQuery() ) );
		$this->check_items( $page, Balance::class, $gateway_id );

		return $this->serializer->serialize_page( $page, $version );
	}

	/**
	 * Get a serialized page of payouts of a payment gateway.
	 *
	 * @param string           $gateway_id The payment gateway id.
	 * @param FinanceDataQuery $query      The pagination parameters.
	 * @return array The payouts response.
	 * @throws FinanceDataException When the gateway has no provider, does not support payouts, or the provider fails.
	 *
	 * @since 11.2.0
	 */
	public function get_payouts( string $gateway_id, FinanceDataQuery $query ): array {
		list( $provider, $version ) = $this->resolve_provider( $gateway_id, FinanceDataSource::PAYOUTS );

		/**
		 * The provider, narrowed to the payouts interface by resolve_provider().
		 *
		 * @var PayoutsProviderInterface $provider
		 */
		$page = $this->call_provider( $gateway_id, fn() => $provider->get_payouts( $query ) );
		$this->check_items( $page, Payout::class, $gateway_id );

		return $this->serializer->serialize_page( $page, $version );
	}

	/**
	 * Find the provider for a gateway and the data-model version it declares for a data type.
	 *
	 * @param string $gateway_id The payment gateway id.
	 * @param string $data_type  The finance data type.
	 * @return array{0: FinanceDataProviderInterface, 1: int} The provider and the declared version.
	 * @throws FinanceDataException When there is no provider or the data type cannot be served.
	 */
	private function resolve_provider( string $gateway_id, string $data_type ): array {
		$provider = $this->registry->get_provider( $gateway_id );
		if ( null === $provider || ! isset( $this->get_all_gateways()[ $gateway_id ] ) ) {
			throw new FinanceDataException(
				esc_html__( 'No finance data is available for this payment gateway.', 'woocommerce' ),
				'provider_not_found',
				404
			);
		}

		$version = $this->get_declared_data_types( $provider, $gateway_id )[ $data_type ] ?? null;
		if ( null === $version || ! FinanceDataSchemas::is_supported( $data_type, $version ) ) {
			throw new FinanceDataException(
				esc_html__( 'This payment gateway does not provide this type of finance data.', 'woocommerce' ),
				'data_type_not_supported',
				404
			);
		}

		if ( ! $this->implements_data_type( $provider, $data_type ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: payment gateway id, 2: finance data type, 3: PHP interface name. */
					__( 'The finance data provider for "%1$s" declares support for "%2$s" but does not implement %3$s.', 'woocommerce' ),
					$gateway_id,
					$data_type,
					self::DATA_TYPE_INTERFACES[ $data_type ]
				),
				'11.2.0'
			);
			throw new FinanceDataException(
				esc_html__( 'This payment gateway does not provide this type of finance data.', 'woocommerce' ),
				'data_type_not_supported',
				404
			);
		}

		return array( $provider, $version );
	}

	/**
	 * Get the data types a provider declares, keeping only string types with integer versions.
	 *
	 * @param FinanceDataProviderInterface $provider   The provider.
	 * @param string                       $gateway_id The payment gateway id, for logging.
	 * @return array<string, int>
	 */
	private function get_declared_data_types( FinanceDataProviderInterface $provider, string $gateway_id ): array {
		try {
			$declared = $provider->get_supported_data_types();
		} catch ( \Throwable $e ) {
			$this->log_error( sprintf( 'The finance data provider for "%1$s" failed to declare its data types: %2$s: %3$s', $gateway_id, get_class( $e ), $e->getMessage() ) );
			$this->report_exception( $e, __METHOD__ );
			return array();
		}

		$data_types = array();
		foreach ( $declared as $data_type => $version ) {
			if ( is_string( $data_type ) && is_int( $version ) ) {
				$data_types[ $data_type ] = $version;
			}
		}

		return $data_types;
	}

	/**
	 * Whether a provider implements the interface for a data type.
	 *
	 * @param FinanceDataProviderInterface $provider  The provider.
	 * @param string                       $data_type The finance data type.
	 * @return bool
	 */
	private function implements_data_type( FinanceDataProviderInterface $provider, string $data_type ): bool {
		$interface = self::DATA_TYPE_INTERFACES[ $data_type ] ?? null;

		return null !== $interface && $provider instanceof $interface;
	}

	/**
	 * Call a provider method, turning any failure into a FinanceDataException.
	 *
	 * @param string   $gateway_id The payment gateway id, for logging.
	 * @param callable $callback   The call to make.
	 * @return FinanceDataPage
	 * @throws FinanceDataException When the provider throws.
	 */
	private function call_provider( string $gateway_id, callable $callback ): FinanceDataPage {
		try {
			return $callback();
		} catch ( FinanceDataException $e ) {
			$error_code  = sanitize_key( $e->get_error_code() );
			$error_code  = '' === $error_code ? 'provider_error' : $error_code;
			$http_status = max( 400, min( 599, $e->get_http_status() ) );

			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The message is returned as JSON in the REST response, not rendered as HTML.
			throw new FinanceDataException( $e->getMessage(), $error_code, $http_status, $e );
		} catch ( \Throwable $e ) {
			$this->log_error( sprintf( 'The finance data provider for "%1$s" failed: %2$s: %3$s', $gateway_id, get_class( $e ), $e->getMessage() ) );
			$this->report_exception( $e, __METHOD__ );

			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The message is returned as JSON in the REST response, not rendered as HTML.
			throw new FinanceDataException( __( 'The payment provider could not return finance data.', 'woocommerce' ), 'provider_error', 500, $e );
		}
	}

	/**
	 * Check that every item on a page is an instance of the expected class.
	 *
	 * @param FinanceDataPage $page           The page.
	 * @param string          $expected_class The expected item class.
	 * @param string          $gateway_id     The payment gateway id, for logging.
	 * @throws FinanceDataException When an item has another type.
	 */
	private function check_items( FinanceDataPage $page, string $expected_class, string $gateway_id ): void {
		foreach ( $page->get_items() as $item ) {
			if ( $item instanceof $expected_class ) {
				continue;
			}

			$this->log_error( sprintf( 'The finance data provider for "%1$s" returned a %2$s item where a %3$s was expected.', $gateway_id, get_class( $item ), $expected_class ) );

			throw new FinanceDataException(
				esc_html__( 'The payment provider returned invalid finance data.', 'woocommerce' ),
				'invalid_provider_data',
				500
			);
		}
	}

	/**
	 * Get all registered payment gateways keyed by id.
	 *
	 * @return array<string, WC_Payment_Gateway>
	 */
	private function get_all_gateways(): array {
		return WC()->payment_gateways()->payment_gateways();
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message The message.
	 */
	private function log_debug( string $message ): void {
		wc_get_logger()->debug( $message, array( 'source' => self::LOG_SOURCE ) );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The message.
	 */
	private function log_error( string $message ): void {
		wc_get_logger()->error( $message, array( 'source' => self::LOG_SOURCE ) );
	}

	/**
	 * Report a caught exception through wc_caught_exception(), which only accepts Exception instances.
	 *
	 * @param \Throwable $e      The caught throwable.
	 * @param string     $method The method that caught it.
	 */
	private function report_exception( \Throwable $e, string $method ): void {
		if ( $e instanceof \Exception ) {
			wc_caught_exception( $e, $method );
		}
	}
}
