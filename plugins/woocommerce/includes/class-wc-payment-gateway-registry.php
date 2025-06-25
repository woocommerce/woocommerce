<?php

/**
 * Gateway registry class to manage payment gateway instances.
 */
class WC_Payment_Gateway_Registry {
	/**
	 * Registered gateway instances.
	 *
	 * @var WC_Payment_Gateway[]
	 */
	private array $gateways = [];

	/**
	 * Register a gateway instance.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 *
	 * @return bool True if registered successfully, false if already exists.
	 */
	public function register( WC_Payment_Gateway $gateway ): bool {
		if ( ! isset( $this->gateways[ $gateway->id ] ) ) {
			$this->gateways[ $gateway->id ] = $gateway;

			return true;
		}

		return false;
	}

	/**
	 * Get all registered gateways.
	 *
	 * @return WC_Payment_Gateway[] Array of gateway instances.
	 */
	public function get_all(): array {
		return $this->gateways;
	}

	/**
	 * Get a specific gateway by ID.
	 *
	 * @param string $id Gateway ID.
	 *
	 * @return WC_Payment_Gateway|null Gateway instance or null if not found.
	 */
	public function get( string $id ): ?WC_Payment_Gateway {
		return $this->gateways[ $id ] ?? null;
	}

	/**
	 * Check if a gateway is registered.
	 *
	 * @param string $id Gateway ID.
	 *
	 * @return bool True if gateway is registered.
	 */
	public function has( string $id ): bool {
		return isset( $this->gateways[ $id ] );
	}

	/**
	 * Get all gateway IDs.
	 *
	 * @return string[] Array of gateway IDs.
	 */
	public function get_ids(): array {
		return array_keys( $this->gateways );
	}
}
