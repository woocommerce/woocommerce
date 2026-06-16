<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use RuntimeException;

/**
 * Fake legacy proxy that throws from every lookup.
 */
class ThrowingLegacyRuntimeProxy extends LegacyProxy {

	/**
	 * Call a user function.
	 *
	 * @param string $function_name Function name.
	 * @param mixed  ...$parameters Function parameters.
	 * @return mixed
	 * @throws RuntimeException Always thrown.
	 */
	public function call_function( $function_name, ...$parameters ) {
		throw new RuntimeException( 'Runtime lookup failed.' );
	}

	/**
	 * Call a static method.
	 *
	 * @param string $class_name  Class name.
	 * @param string $method_name Method name.
	 * @param mixed  ...$parameters Method parameters.
	 * @return mixed
	 * @throws RuntimeException Always thrown.
	 */
	public function call_static( $class_name, $method_name, ...$parameters ) {
		throw new RuntimeException( 'Runtime lookup failed.' );
	}
}
