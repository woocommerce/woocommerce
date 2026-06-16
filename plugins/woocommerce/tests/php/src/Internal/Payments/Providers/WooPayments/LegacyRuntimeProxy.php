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
	 * Constructor.
	 *
	 * @param bool        $loaded     Whether the WooPayments runtime is loaded.
	 * @param object|null $gateway    Gateway service.
	 * @param object|null $account    Account service.
	 * @param object|null $api_client API client.
	 * @param object|null $logger     Logger.
	 */
	public function __construct( bool $loaded, ?object $gateway = null, ?object $account = null, ?object $api_client = null, ?object $logger = null ) {
		$this->loaded     = $loaded;
		$this->gateway    = $gateway;
		$this->account    = $account;
		$this->api_client = $api_client;
		$this->logger     = $logger;
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

		if ( 'wc_get_logger' === $function_name ) {
			return $this->logger;
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

		return parent::call_static( $class_name, $method_name, ...$parameters );
	}
}
