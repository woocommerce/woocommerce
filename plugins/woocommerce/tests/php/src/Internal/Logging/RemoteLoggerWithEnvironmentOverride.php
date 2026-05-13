<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Logging;

use Automattic\WooCommerce\Internal\Logging\RemoteLogger;

/**
 * Mock class that extends RemoteLogger to allow overriding is_dev_or_local_environment.
 */
class RemoteLoggerWithEnvironmentOverride extends RemoteLogger {
	/**
	 * The is_dev_or_local value.
	 *
	 * @var bool
	 */
	private $is_dev_or_local = false;

	/**
	 * Set the is_dev_or_local value.
	 *
	 * @param bool $value The value to set.
	 */
	public function set_is_dev_or_local( $value ) {
		$this->is_dev_or_local = $value;
	}

	/**
	 * @inheritDoc
	 */
	protected function is_dev_or_local_environment() {
		return $this->is_dev_or_local;
	}
}
