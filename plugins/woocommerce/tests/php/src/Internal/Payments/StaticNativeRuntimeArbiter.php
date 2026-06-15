<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;

/**
 * Static arbiter for registry tests.
 */
class StaticNativeRuntimeArbiter extends NativePaymentsRuntimeArbiter {

	/**
	 * Whether native should register.
	 *
	 * @var bool
	 */
	private bool $should_native_register;

	/**
	 * Constructor.
	 *
	 * @param bool $should_native_register Whether native should register.
	 */
	public function __construct( bool $should_native_register ) {
		$this->should_native_register = $should_native_register;
	}

	/**
	 * Tell whether core-native code may perform mutating registration for this site.
	 *
	 * @return bool
	 */
	public function should_native_register(): bool {
		return $this->should_native_register;
	}
}
