<?php
/**
 * Plan - a stored selling plan record. Its billing, pricing and delivery policies
 * are opaque payloads of the owning extension; the engine checks their shape only.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;

defined( 'ABSPATH' ) || exit;

/**
 * Plan entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) plan or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Plan {

	/**
	 * Plan id, or null before it is persisted.
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Display name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Registered plan status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Owning extension slug, or null until owner semantics are assigned.
	 *
	 * @var string|null
	 */
	private $extension_slug;

	/**
	 * Billing payload, owned and interpreted by the plan's extension.
	 *
	 * @var array<string, mixed>|null
	 */
	private $billing_policy;

	/**
	 * Pricing payload, owned and interpreted by the plan's extension.
	 *
	 * @var array<string, mixed>|null
	 */
	private $pricing_policy;

	/**
	 * Delivery payload, owned and interpreted by the plan's extension.
	 *
	 * @var array<string, mixed>|null
	 */
	private $delivery_policy;

	/**
	 * Creation time (GMT, `Y-m-d H:i:s`) as stored, or null before insert.
	 *
	 * @var string|null
	 */
	private $date_created_gmt;

	/**
	 * Last update time (GMT, `Y-m-d H:i:s`) as stored, or null before insert.
	 *
	 * @var string|null
	 */
	private $date_updated_gmt;

	/**
	 * Use {@see self::create()} or {@see self::from_storage()}.
	 *
	 * @param int|null                  $id               Plan id, or null before save.
	 * @param string                    $name             Display name.
	 * @param string                    $status           Plan status.
	 * @param string|null               $extension_slug   Owning extension slug.
	 * @param array<string, mixed>|null $billing_policy   Billing payload.
	 * @param array<string, mixed>|null $pricing_policy   Pricing payload.
	 * @param array<string, mixed>|null $delivery_policy  Delivery payload.
	 * @param string|null               $date_created_gmt Stored creation time.
	 * @param string|null               $date_updated_gmt Stored update time.
	 */
	private function __construct(
		?int $id,
		string $name,
		string $status,
		?string $extension_slug,
		?array $billing_policy,
		?array $pricing_policy,
		?array $delivery_policy,
		?string $date_created_gmt,
		?string $date_updated_gmt
	) {
		$this->id               = $id;
		$this->name             = $name;
		$this->status           = $status;
		$this->extension_slug   = $extension_slug;
		$this->billing_policy   = $billing_policy;
		$this->pricing_policy   = $pricing_policy;
		$this->delivery_policy  = $delivery_policy;
		$this->date_created_gmt = $date_created_gmt;
		$this->date_updated_gmt = $date_updated_gmt;
	}

	/**
	 * Build a new, unsaved plan.
	 *
	 * @param array<string, mixed> $args Keys `name`, `status` (default {@see PlanStatus::ACTIVE}), `extension_slug`, `billing_policy`, `pricing_policy`, `delivery_policy`.
	 * @throws InvalidArgumentException If the status is not registered or a policy is not an object (string-keyed array) or null.
	 */
	public static function create( array $args ): self {
		$status = Coercion::coerce_string( $args['status'] ?? null, PlanStatus::ACTIVE );
		self::assert_registered_status( $status );

		return new self(
			null,
			Coercion::coerce_string( $args['name'] ?? null ),
			$status,
			Coercion::coerce_nullable_string( $args['extension_slug'] ?? null ),
			self::assert_object_or_null( 'billing_policy', $args['billing_policy'] ?? null ),
			self::assert_object_or_null( 'pricing_policy', $args['pricing_policy'] ?? null ),
			self::assert_object_or_null( 'delivery_policy', $args['delivery_policy'] ?? null ),
			null,
			null
		);
	}

	/**
	 * Hydrate from a stored row. Policy columns arrive JSON-decoded.
	 *
	 * The stored status is taken as is: a status registered by a since-deactivated
	 * extension still hydrates.
	 *
	 * @param array<string, mixed> $row Decoded plan row.
	 * @throws InvalidArgumentException If a stored policy is not an object or null.
	 */
	public static function from_storage( array $row ): self {
		return new self(
			isset( $row['id'] ) ? Coercion::coerce_int( $row['id'] ) : null,
			Coercion::coerce_string( $row['name'] ?? null ),
			Coercion::coerce_string( $row['status'] ?? null, PlanStatus::ACTIVE ),
			Coercion::coerce_nullable_string( $row['extension_slug'] ?? null ),
			self::assert_object_or_null( 'billing_policy', $row['billing_policy'] ?? null ),
			self::assert_object_or_null( 'pricing_policy', $row['pricing_policy'] ?? null ),
			self::assert_object_or_null( 'delivery_policy', $row['delivery_policy'] ?? null ),
			Coercion::coerce_nullable_string( $row['date_created_gmt'] ?? null ),
			Coercion::coerce_nullable_string( $row['date_updated_gmt'] ?? null )
		);
	}

	/**
	 * Plan id, or null before save.
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Assign the id after a successful insert.
	 *
	 * @param int $id Plan id.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Display name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the display name.
	 *
	 * @param string $name Display name.
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Plan status.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Set the plan status. Setting the current status is a no-op, so a hydrated
	 * unregistered status survives it.
	 *
	 * @param string $status Plan status; must be registered.
	 * @throws InvalidArgumentException If the status is not registered.
	 */
	public function set_status( string $status ): void {
		if ( $status === $this->status ) {
			return;
		}

		self::assert_registered_status( $status );
		$this->status = $status;
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_extension_slug(): ?string {
		return $this->extension_slug;
	}

	/**
	 * Billing payload, as stored.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_billing_policy(): ?array {
		return $this->billing_policy;
	}

	/**
	 * Replace the billing payload.
	 *
	 * @param array<string, mixed>|null $billing_policy Billing payload.
	 * @throws InvalidArgumentException If the payload is not an object (string-keyed array).
	 */
	public function set_billing_policy( ?array $billing_policy ): void {
		$this->billing_policy = self::assert_object_or_null( 'billing_policy', $billing_policy );
	}

	/**
	 * Pricing payload, as stored.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_pricing_policy(): ?array {
		return $this->pricing_policy;
	}

	/**
	 * Replace the pricing payload.
	 *
	 * @param array<string, mixed>|null $pricing_policy Pricing payload.
	 * @throws InvalidArgumentException If the payload is not an object (string-keyed array).
	 */
	public function set_pricing_policy( ?array $pricing_policy ): void {
		$this->pricing_policy = self::assert_object_or_null( 'pricing_policy', $pricing_policy );
	}

	/**
	 * Delivery payload, as stored.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_delivery_policy(): ?array {
		return $this->delivery_policy;
	}

	/**
	 * Replace the delivery payload.
	 *
	 * @param array<string, mixed>|null $delivery_policy Delivery payload.
	 * @throws InvalidArgumentException If the payload is not an object (string-keyed array).
	 */
	public function set_delivery_policy( ?array $delivery_policy ): void {
		$this->delivery_policy = self::assert_object_or_null( 'delivery_policy', $delivery_policy );
	}

	/**
	 * Creation time (GMT) as stored, or null before insert.
	 */
	public function get_date_created_gmt(): ?string {
		return $this->date_created_gmt;
	}

	/**
	 * Last update time (GMT) as stored, or null before insert.
	 */
	public function get_date_updated_gmt(): ?string {
		return $this->date_updated_gmt;
	}

	/**
	 * Serialize to the storage column shape (excluding the id and timestamps).
	 * Policies are returned as arrays or null; the repository JSON-encodes them.
	 *
	 * @return array<string, mixed>
	 */
	public function to_storage(): array {
		return array(
			'name'            => $this->name,
			'status'          => $this->status,
			'extension_slug'  => $this->extension_slug,
			'billing_policy'  => $this->billing_policy,
			'pricing_policy'  => $this->pricing_policy,
			'delivery_policy' => $this->delivery_policy,
		);
	}

	/**
	 * Throw unless `$status` is a registered plan status.
	 *
	 * @param string $status Status to check.
	 * @throws InvalidArgumentException If the status is not registered.
	 */
	private static function assert_registered_status( string $status ): void {
		if ( ! PlanStatus::is_registered( $status ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Plan: status "%s" is not registered.', $status )
			);
		}
	}

	/**
	 * Accept a policy payload only as an object (string-keyed array) or null.
	 * An empty array is accepted (a JSON `{}` decodes to it).
	 *
	 * @param string $field Policy field name, for the error message.
	 * @param mixed  $value Candidate payload.
	 * @return array<string, mixed>|null
	 * @throws InvalidArgumentException If the value is a list or not an array.
	 */
	private static function assert_object_or_null( string $field, $value ): ?array {
		if ( null === $value ) {
			return null;
		}

		$message = sprintf( 'Plan: %s must be an object (string-keyed array) or null.', $field );
		if ( ! is_array( $value ) ) {
			throw new InvalidArgumentException( $message );
		}

		$out = array();
		foreach ( $value as $key => $item ) {
			if ( ! is_string( $key ) ) {
				throw new InvalidArgumentException( $message );
			}
			$out[ $key ] = $item;
		}

		return $out;
	}
}
