<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Fake legacy proxy for WooPayments runtime access.
 */
class LegacyRuntimeProxy extends LegacyProxy {

	/**
	 * Whether the WooPayments runtime is loaded.
	 *
	 * @var bool
	 */
	private bool $loaded;

	/**
	 * Gateway service.
	 *
	 * @var object|null
	 */
	private ?object $gateway;

	/**
	 * Account service.
	 *
	 * @var object|null
	 */
	private ?object $account;

	/**
	 * API client.
	 *
	 * @var object|null
	 */
	private ?object $api_client;

	/**
	 * Logger.
	 *
	 * @var object|null
	 */
	private ?object $logger;

	/**
	 * Account connect URL.
	 *
	 * @var string|null
	 */
	private ?string $account_connect_url;

	/**
	 * Account overview URL.
	 *
	 * @var string|null
	 */
	private ?string $account_overview_url;

	/**
	 * WooPayments mode service.
	 *
	 * @var object|null
	 */
	private ?object $mode;

	/**
	 * Supported countries.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $supported_countries;

	/**
	 * Updated options.
	 *
	 * @var array<string,mixed>
	 */
	private array $updated_options = array();

	/**
	 * WooPayments account data option payload.
	 *
	 * @var mixed
	 */
	private $account_data = null;

	/**
	 * Constructor.
	 *
	 * @param bool                     $loaded     Whether the WooPayments runtime is loaded.
	 * @param object|null              $gateway    Gateway service.
	 * @param object|null              $account    Account service.
	 * @param object|null              $api_client API client.
	 * @param object|null              $logger               Logger.
	 * @param string|null              $account_connect_url  Account connect URL.
	 * @param string|null              $account_overview_url Account overview URL.
	 * @param object|null              $mode                 WooPayments mode service.
	 * @param array<string,mixed>|null $supported_countries  Supported countries.
	 */
	public function __construct( bool $loaded, ?object $gateway = null, ?object $account = null, ?object $api_client = null, ?object $logger = null, ?string $account_connect_url = null, ?string $account_overview_url = null, ?object $mode = null, ?array $supported_countries = null ) {
		$this->loaded               = $loaded;
		$this->gateway              = $gateway;
		$this->account              = $account;
		$this->api_client           = $api_client;
		$this->logger               = $logger;
		$this->account_connect_url  = $account_connect_url;
		$this->account_overview_url = $account_overview_url;
		$this->mode                 = $mode;
		$this->supported_countries  = $supported_countries;
	}

	/**
	 * Call a user function.
	 *
	 * @param string $function_name Function name.
	 * @param mixed  ...$parameters Function parameters.
	 * @return mixed
	 */
	public function call_function( $function_name, ...$parameters ) {
		if ( 'class_exists' === $function_name && 'WC_Payments' === ( $parameters[0] ?? null ) ) {
			return $this->loaded;
		}

		if ( 'class_exists' === $function_name && 'WC_Payments_Onboarding_Service' === ( $parameters[0] ?? null ) ) {
			return true;
		}

		if ( 'class_exists' === $function_name && 'WC_Payments_Utils' === ltrim( (string) ( $parameters[0] ?? '' ), '\\' ) ) {
			return null !== $this->supported_countries;
		}

		if ( 'wc_get_logger' === $function_name ) {
			return $this->logger;
		}

		if ( 'is_callable' === $function_name && '\WC_Payments_Account::get_connect_url' === ( $parameters[0] ?? null ) ) {
			return null !== $this->account_connect_url;
		}

		if ( 'is_callable' === $function_name && '\WC_Payments_Account::get_overview_page_url' === ( $parameters[0] ?? null ) ) {
			return null !== $this->account_overview_url;
		}

		if ( 'is_callable' === $function_name && in_array( $parameters[0] ?? null, array( 'WC_Payments_Utils::supported_countries', '\WC_Payments_Utils::supported_countries' ), true ) ) {
			return null !== $this->supported_countries;
		}

		if ( 'update_option' === $function_name ) {
			$this->updated_options[ (string) ( $parameters[0] ?? '' ) ] = $parameters[1] ?? null;

			return true;
		}

		if ( 'get_option' === $function_name && 'wcpay_account_data' === ( $parameters[0] ?? null ) ) {
			return null === $this->account_data ? ( $parameters[1] ?? null ) : $this->account_data;
		}

		return parent::call_function( $function_name, ...$parameters );
	}

	/**
	 * Call a static method.
	 *
	 * @param string $class_name  Class name.
	 * @param string $method_name Method name.
	 * @param mixed  ...$parameters Method parameters.
	 * @return mixed
	 */
	public function call_static( $class_name, $method_name, ...$parameters ) {
		if ( 'WC_Payments_Account' === ltrim( $class_name, '\\' ) && 'get_connect_url' === $method_name ) {
			return $this->account_connect_url;
		}

		if ( 'WC_Payments_Account' === ltrim( $class_name, '\\' ) && 'get_overview_page_url' === $method_name ) {
			return $this->account_overview_url;
		}

		if ( 'WC_Payments_Utils' === ltrim( $class_name, '\\' ) && 'supported_countries' === $method_name ) {
			return $this->supported_countries;
		}

		if ( 'WC_Payments' !== $class_name ) {
			return parent::call_static( $class_name, $method_name, ...$parameters );
		}

		if ( 'get_gateway' === $method_name ) {
			return $this->gateway;
		}

		if ( 'get_account_service' === $method_name ) {
			return $this->account;
		}

		if ( 'get_payments_api_client' === $method_name ) {
			return $this->api_client;
		}

		if ( 'mode' === $method_name ) {
			return $this->mode;
		}

		return parent::call_static( $class_name, $method_name, ...$parameters );
	}

	/**
	 * Get updated options.
	 *
	 * @return array<string,mixed>
	 */
	public function get_updated_options(): array {
		return $this->updated_options;
	}

	/**
	 * Set the WooPayments account data option payload.
	 *
	 * @param mixed $account_data Account data payload.
	 */
	public function set_account_data( $account_data ): void {
		$this->account_data = $account_data;
	}
}
