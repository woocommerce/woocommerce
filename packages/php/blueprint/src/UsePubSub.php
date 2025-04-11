<?php

namespace Automattic\WooCommerce\Blueprint;

trait UsePubSub {

	private array $subscribers = array();

	/**
	 * Subscribe to an event with a callback.
	 *
	 * @param string   $event The event name.
	 * @param callable $callback The callback to execute when the event is published.
	 * @return void
	 */
	public function subscribe( string $event, callable $callback ): void {
		if ( ! isset( $this->subscribers[ $event ] ) ) {
			$this->subscribers[ $event ] = array();
		}

		$this->subscribers[ $event ][] = $callback;
	}

	/**
	 * Publish an event to all subscribers.
	 *
	 * @param string $event The event name.
	 * @param mixed  ...$args Arguments to pass to the callbacks.
	 * @return void
	 */
	public function publish( string $event, ...$args ): void {
		if ( ! isset( $this->subscribers[ $event ] ) ) {
			return;
		}

		foreach ( $this->subscribers[ $event ] as $callback ) {
			call_user_func( $callback, ...$args );
		}
	}

	/**
	 * Unsubscribe a specific callback from an event.
	 *
	 * @param string   $event The event name.
	 * @param callable $callback The callback to remove.
	 * @return void
	 */
	public function unsubscribe( string $event, callable $callback ): void {
		if ( ! isset( $this->subscribers[ $event ] ) ) {
			return;
		}

		$this->subscribers[ $event ] = array_filter(
			$this->subscribers[ $event ],
			fn( $subscriber ) => $subscriber !== $callback
		);

		if ( empty( $this->subscribers[ $event ] ) ) {
			unset( $this->subscribers[ $event ] );
		}
	}
}
