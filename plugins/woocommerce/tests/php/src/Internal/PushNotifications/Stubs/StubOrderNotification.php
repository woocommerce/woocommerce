<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;

/**
 * Stub notification with type 'store_order' for testing.
 */
class StubOrderNotification extends NewOrderNotification {
	/** @var array<string, bool> */
	private array $meta = array();

	/**
	 * {@inheritDoc}
	 */
	public function to_payload(): ?array {
		return array( 'test' => true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function has_meta( string $key ): bool {
		return isset( $this->meta[ $key ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function write_meta( string $key ): void {
		$this->meta[ $key ] = true;
	}
}
