<?php
/**
 * Mock DateTime Provider.
 *
 * @package WooCommerce\Admin\Tests\RemoteSpecs
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\DateTimeProvider\DateTimeProviderInterface;

/**
 * Mock DateTime Provider.
 */
class MockDateTimeProvider implements DateTimeProviderInterface {
	/**
	 * Construct the mock DateTime provider using the specified value for now.
	 *
	 * @param DateTime $now The value to use for now.
	 */
	public function __construct( $now ) {
		$this->now = $now;
	}

	/**
	 * Returns the specified DateTime.
	 *
	 * @return DateTime
	 */
	public function get_now() {
		return $this->now;
	}
}
