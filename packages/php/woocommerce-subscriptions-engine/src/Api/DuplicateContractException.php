<?php
/**
 * DuplicateContractException - a contract create refused because the extension already
 * created a contract with the same creation key.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown by {@see Contracts::create()} and {@see Contracts::create_locked()} when the
 * `(extension_slug, creation_key)` pair is taken. Carries the existing contract id.
 */
final class DuplicateContractException extends RuntimeException {

	/**
	 * The contract that already holds the creation key.
	 *
	 * @var int
	 */
	private $existing_contract_id;

	/**
	 * The creation key that collided.
	 *
	 * @var string
	 */
	private $creation_key;

	/**
	 * Describe the collision.
	 *
	 * @param int    $existing_contract_id The contract that already holds the key.
	 * @param string $creation_key         The creation key that collided.
	 */
	public function __construct( int $existing_contract_id, string $creation_key ) {
		parent::__construct( sprintf( 'A contract with this creation key already exists (contract %d).', $existing_contract_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the message is not output.

		$this->existing_contract_id = $existing_contract_id;
		$this->creation_key         = $creation_key;
	}

	/**
	 * The contract that already holds the creation key.
	 */
	public function get_existing_contract_id(): int {
		return $this->existing_contract_id;
	}

	/**
	 * The creation key that collided.
	 */
	public function get_creation_key(): string {
		return $this->creation_key;
	}
}
