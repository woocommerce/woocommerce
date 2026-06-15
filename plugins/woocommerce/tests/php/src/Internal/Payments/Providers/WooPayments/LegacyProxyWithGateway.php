<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Fake legacy proxy that returns a controlled gateway.
 */
class LegacyProxyWithGateway extends LegacyProxy {

	/**
	 * Legacy gateway.
	 *
	 * @var RecordingLegacyGateway|null
	 */
	private ?RecordingLegacyGateway $gateway;

	/**
	 * Constructor.
	 *
	 * @param RecordingLegacyGateway|null $gateway Legacy gateway.
	 */
	public function __construct( ?RecordingLegacyGateway $gateway ) {
		$this->gateway = $gateway;
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
			return null !== $this->gateway;
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
		if ( 'WC_Payments' === $class_name && 'get_gateway' === $method_name ) {
			return $this->gateway;
		}

		return parent::call_static( $class_name, $method_name, ...$parameters );
	}
}
