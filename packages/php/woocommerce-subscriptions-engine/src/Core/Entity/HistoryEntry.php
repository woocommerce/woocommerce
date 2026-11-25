<?php
/**
 * HistoryEntry - one record in a contract's history, written on the owning extension's command.
 *
 * The engine gives an entry no meaning: `type` is a slug the extension chooses and `payload` is
 * the extension's opaque data, stored as JSON. `cycle_id` optionally names a cycle of the same
 * contract, and `creation_key` optionally makes the add idempotent per contract. Entries are
 * immutable: there are no setters, and a stored entry is built with {@see self::from_storage()}.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

defined( 'ABSPATH' ) || exit;

/**
 * History entry entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) entry or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class HistoryEntry {

	/**
	 * Entry id, or null before it is persisted.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Contract id.
	 *
	 * @var int
	 */
	private $contract_id;

	/**
	 * Cycle id (a cycle of the same contract), or null.
	 *
	 * @var int|null
	 */
	private $cycle_id;

	/**
	 * Extension-chosen type slug.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Idempotency key, unique per contract, or null.
	 *
	 * @var string|null
	 */
	private $creation_key;

	/**
	 * The extension's opaque payload, or null.
	 *
	 * @var array<int|string, mixed>|null
	 */
	private $payload;

	/**
	 * When the engine stored the entry (GMT `Y-m-d H:i:s`), or null before it is persisted.
	 *
	 * @var string|null
	 */
	private $date_created_gmt;

	/**
	 * Use {@see self::create()} or {@see self::from_storage()}. Coerces each attribute to its
	 * property type; unknown keys are ignored, missing keys take the default.
	 *
	 * @param array<string, mixed> $data Raw attributes keyed by property name.
	 */
	private function __construct( array $data ) {
		$this->id               = Coercion::coerce_nullable_int( $data['id'] ?? null );
		$this->contract_id      = Coercion::coerce_int( $data['contract_id'] ?? null );
		$this->cycle_id         = Coercion::coerce_nullable_int( $data['cycle_id'] ?? null );
		$this->type             = Coercion::coerce_string( $data['type'] ?? null );
		$this->creation_key     = Coercion::coerce_nullable_string( $data['creation_key'] ?? null );
		$this->payload          = is_array( $data['payload'] ?? null ) ? $data['payload'] : null;
		$this->date_created_gmt = Coercion::coerce_nullable_string( $data['date_created_gmt'] ?? null );
	}

	/**
	 * Build a new, unsaved entry. `type` is required (1-64 of `[a-z0-9_-]`); `payload` must be
	 * JSON-encodable; `creation_key` is 1-100 characters, trimmed. Every invalid attribute is
	 * reported in one exception.
	 *
	 * @param array<string, mixed> $args `contract_id`, `cycle_id`, `type`, `payload`, `creation_key`.
	 * @throws InvalidInputException If the entry attributes are not valid.
	 */
	public static function create( array $args ): self {
		// A new entry is always unsaved; the engine sets the id and the creation time on insert.
		unset( $args['id'], $args['date_created_gmt'] );

		// The key column ignores trailing spaces when comparing, so they never tell keys apart.
		if ( isset( $args['creation_key'] ) && is_string( $args['creation_key'] ) ) {
			$args['creation_key'] = trim( $args['creation_key'] );
		}

		$errors = array_filter(
			array(
				self::get_type_error( $args['type'] ?? null ),
				self::get_creation_key_error( $args['creation_key'] ?? null ),
				self::get_payload_error( $args['payload'] ?? null ),
			)
		);
		if ( array() !== $errors ) {
			throw InvalidInputException::from_errors( array_values( $errors ) );
		}

		return new self( $args );
	}

	/**
	 * Hydrate from a stored row, decoding the JSON payload. Stored values are not validated.
	 *
	 * @param array<string, mixed> $row History row.
	 */
	public static function from_storage( array $row ): self {
		$payload = $row['payload'] ?? null;
		if ( is_string( $payload ) ) {
			$row['payload'] = json_decode( $payload, true );
		}

		return new self( $row );
	}

	/**
	 * Entry id, or null before save.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Contract id.
	 */
	public function get_contract_id(): int {
		return $this->contract_id;
	}

	/**
	 * Cycle id, or null.
	 */
	public function get_cycle_id(): ?int {
		return $this->cycle_id;
	}

	/**
	 * Type slug.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Payload, or null.
	 *
	 * @return array<int|string, mixed>|null
	 */
	public function get_payload(): ?array {
		return $this->payload;
	}

	/**
	 * When the engine stored the entry (GMT `Y-m-d H:i:s`), or null before save.
	 */
	public function get_date_created_gmt(): ?string {
		return $this->date_created_gmt;
	}

	/**
	 * The stored columns. The payload stays an array: the repository encodes it as JSON.
	 *
	 * @return array{id: int|null, contract_id: int, cycle_id: int|null, type: string, creation_key: string|null, payload: array<int|string, mixed>|null, date_created_gmt: string|null}
	 */
	public function to_storage(): array {
		return array(
			'id'               => $this->id,
			'contract_id'      => $this->contract_id,
			'cycle_id'         => $this->cycle_id,
			'type'             => $this->type,
			'creation_key'     => $this->creation_key,
			'payload'          => $this->payload,
			'date_created_gmt' => $this->date_created_gmt,
		);
	}

	/**
	 * The error for a type that is not a slug of 1-64 of `[a-z0-9_-]`, or null.
	 *
	 * @param mixed $type Type to check.
	 */
	private static function get_type_error( $type ): ?InvalidInputException {
		try {
			ArgumentValidator::validate_slug( 'type', $type );
		} catch ( InvalidInputException $e ) {
			return $e;
		}

		return null;
	}

	/**
	 * The error for a creation key that is not null or a string of 1-100 characters, or null.
	 *
	 * @param mixed $creation_key Creation key to check.
	 */
	private static function get_creation_key_error( $creation_key ): ?InvalidInputException {
		try {
			ArgumentValidator::validate_creation_key( $creation_key );
		} catch ( InvalidInputException $e ) {
			return $e;
		}

		return null;
	}

	/**
	 * The error for a payload that is not null or a JSON-encodable array, or null.
	 *
	 * @param mixed $payload Payload to check.
	 */
	private static function get_payload_error( $payload ): ?InvalidInputException {
		if ( null === $payload ) {
			return null;
		}
		if ( ! is_array( $payload ) ) {
			return new InvalidInputException( ValidationErrorCode::INVALID_TYPE, 'payload', 'HistoryEntry: payload must be null or an array.', array( 'type' => 'object' ) );
		}
		// Core is WordPress-free, so PHP's encoder checks it, not wp_json_encode().
		if ( false === json_encode( $payload ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			return new InvalidInputException( ValidationErrorCode::INVALID_VALUE, 'payload', 'HistoryEntry: payload cannot be encoded as JSON.' );
		}

		return null;
	}
}
