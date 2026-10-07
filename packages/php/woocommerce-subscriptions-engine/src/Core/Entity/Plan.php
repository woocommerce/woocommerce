<?php
/**
 * Plan - a stored selling plan record. Its billing, pricing and delivery policies
 * are opaque payloads of the owning extension; the engine checks their shape only.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use DomainException;
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
	 * Use {@see self::create()} or {@see self::from_storage()}. Coerces each attribute
	 * to its property type; unknown keys are ignored, missing keys take the default.
	 *
	 * @param array<string, mixed> $data Raw attributes keyed by property name.
	 */
	private function __construct( array $data ) {
		$this->id               = Coercion::coerce_nullable_int( $data['id'] ?? null );
		$this->name             = Coercion::coerce_string( $data['name'] ?? null );
		$this->status           = Coercion::coerce_string( $data['status'] ?? null, PlanStatus::ACTIVE );
		$this->extension_slug   = Coercion::coerce_nullable_string( $data['extension_slug'] ?? null );
		$this->billing_policy   = Coercion::coerce_nullable_string_keyed( $data['billing_policy'] ?? null );
		$this->pricing_policy   = Coercion::coerce_nullable_string_keyed( $data['pricing_policy'] ?? null );
		$this->delivery_policy  = Coercion::coerce_nullable_string_keyed( $data['delivery_policy'] ?? null );
		$this->date_created_gmt = Coercion::coerce_nullable_string( $data['date_created_gmt'] ?? null );
		$this->date_updated_gmt = Coercion::coerce_nullable_string( $data['date_updated_gmt'] ?? null );
	}

	/**
	 * Build a new, unsaved plan. `extension_slug` and a non-empty `name` are required.
	 *
	 * @param array<string, mixed> $args Keys `name`, `status` (default {@see PlanStatus::ACTIVE}), `extension_slug`, `billing_policy`, `pricing_policy`, `delivery_policy`.
	 * @throws DomainException If the plan attributes are not valid.
	 */
	public static function create( array $args ): self {
		// A new plan is always unsaved; never adopt a caller-supplied id or stored dates.
		unset( $args['id'], $args['date_created_gmt'], $args['date_updated_gmt'] );

		// Checked before construction, which would re-key a list into an object.
		self::assert_policy( 'billing_policy', $args['billing_policy'] ?? null );
		self::assert_policy( 'pricing_policy', $args['pricing_policy'] ?? null );
		self::assert_policy( 'delivery_policy', $args['delivery_policy'] ?? null );

		$plan = new self( $args );

		self::assert_name( $plan->name );
		self::assert_status( $plan->status );
		self::assert_extension_slug( $plan->extension_slug );

		return $plan;
	}

	/**
	 * Hydrate from a stored row, without validation. Policy columns arrive JSON-decoded.
	 *
	 * The stored status is taken as is: a status registered by a since-deactivated
	 * extension still hydrates.
	 *
	 * @param array<string, mixed> $row Decoded plan row.
	 */
	public static function from_storage( array $row ): self {
		return new self( $row );
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
	 * @param string $name Display name; must not be empty.
	 * @throws DomainException If the name is empty.
	 */
	public function set_name( string $name ): void {
		self::assert_name( $name );
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
	 * @throws DomainException If the status is not registered.
	 */
	public function set_status( string $status ): void {
		if ( $status === $this->status ) {
			return;
		}

		self::assert_status( $status );
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
	 * @param array<array-key, mixed>|null $billing_policy Billing payload; must be string-keyed.
	 * @throws DomainException If the payload is not an object (string-keyed array).
	 */
	public function set_billing_policy( ?array $billing_policy ): void {
		self::assert_policy( 'billing_policy', $billing_policy );
		$this->billing_policy = Coercion::coerce_nullable_string_keyed( $billing_policy );
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
	 * @param array<array-key, mixed>|null $pricing_policy Pricing payload; must be string-keyed.
	 * @throws DomainException If the payload is not an object (string-keyed array).
	 */
	public function set_pricing_policy( ?array $pricing_policy ): void {
		self::assert_policy( 'pricing_policy', $pricing_policy );
		$this->pricing_policy = Coercion::coerce_nullable_string_keyed( $pricing_policy );
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
	 * @param array<array-key, mixed>|null $delivery_policy Delivery payload; must be string-keyed.
	 * @throws DomainException If the payload is not an object (string-keyed array).
	 */
	public function set_delivery_policy( ?array $delivery_policy ): void {
		self::assert_policy( 'delivery_policy', $delivery_policy );
		$this->delivery_policy = Coercion::coerce_nullable_string_keyed( $delivery_policy );
	}

	/**
	 * Creation time (GMT) as stored, or null before insert.
	 */
	public function get_date_created_gmt(): ?string {
		return $this->date_created_gmt;
	}

	/**
	 * Assign the creation time after a successful insert.
	 *
	 * @param string $date_created_gmt Stored creation time (GMT, `Y-m-d H:i:s`).
	 */
	public function set_date_created_gmt( string $date_created_gmt ): void {
		$this->date_created_gmt = $date_created_gmt;
	}

	/**
	 * Last update time (GMT) as stored, or null before insert.
	 */
	public function get_date_updated_gmt(): ?string {
		return $this->date_updated_gmt;
	}

	/**
	 * Assign the update time after a successful write.
	 *
	 * @param string $date_updated_gmt Stored update time (GMT, `Y-m-d H:i:s`).
	 */
	public function set_date_updated_gmt( string $date_updated_gmt ): void {
		$this->date_updated_gmt = $date_updated_gmt;
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
	 * Refuse an empty or whitespace-only name.
	 *
	 * @param string $name Name to check.
	 * @throws DomainException If `$name` is empty.
	 */
	private static function assert_name( string $name ): void {
		if ( '' === trim( $name ) ) {
			throw new DomainException( 'Plan: name is required and must be a non-empty string.' );
		}
	}

	/**
	 * Refuse a status that is not a registered plan status.
	 *
	 * @param string $status Status to check.
	 * @throws DomainException If `$status` is not registered.
	 */
	private static function assert_status( string $status ): void {
		if ( ! PlanStatus::is_registered( $status ) ) {
			throw new DomainException( sprintf( 'Plan: status "%s" is not registered.', $status ) );
		}
	}

	/**
	 * Refuse a missing or empty owning extension slug.
	 *
	 * @param string|null $extension_slug Extension slug to check.
	 * @throws DomainException If `$extension_slug` is null or empty.
	 */
	private static function assert_extension_slug( ?string $extension_slug ): void {
		if ( null === $extension_slug || '' === $extension_slug ) {
			throw new DomainException( 'Plan: extension_slug is required and must be a non-empty string.' );
		}
	}

	/**
	 * Refuse a policy payload that is not an object (string-keyed array) or null. An empty
	 * array is accepted (a JSON `{}` decodes to it). The same rule for each of the three policies.
	 *
	 * @param string $field Policy field name, for the error message.
	 * @param mixed  $value Candidate payload.
	 * @throws DomainException If the value is a list or not an array.
	 */
	private static function assert_policy( string $field, $value ): void {
		if ( null === $value ) {
			return;
		}

		$message = sprintf( 'Plan: %s must be an object (string-keyed array) or null.', $field );
		if ( ! is_array( $value ) ) {
			throw new DomainException( $message );
		}

		foreach ( array_keys( $value ) as $key ) {
			if ( ! is_string( $key ) ) {
				throw new DomainException( $message );
			}
		}
	}
}
