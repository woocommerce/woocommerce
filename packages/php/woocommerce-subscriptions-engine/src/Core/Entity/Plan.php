<?php
/**
 * Plan - a subscription selling plan: cadence, pricing, and delivery policy for
 * one or more products.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\DeliveryPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

defined( 'ABSPATH' ) || exit;

/**
 * Plan entity.
 *
 * Construct via {@see self::create()} for a new (unsaved) plan or
 * {@see self::from_storage()} when hydrating a stored row.
 */
final class Plan {

	public const DEFAULT_CATEGORY = 'SUBSCRIPTION';

	public const DEFAULT_STATUS = 'active';

	public const STATUS_ACTIVE = 'active';

	public const STATUS_ARCHIVED = 'archived';

	public const ALLOWED_STATUSES = array( self::STATUS_ACTIVE, self::STATUS_ARCHIVED );

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
	 * Optional description.
	 *
	 * @var string|null
	 */
	private $description;

	/**
	 * Billing cadence. Required - every plan has one.
	 *
	 * @var BillingPolicy
	 */
	private $billing_policy;

	/**
	 * Optional delivery policy.
	 *
	 * @var DeliveryPolicy|null
	 */
	private $delivery_policy;

	/**
	 * Optional pricing payload, owned and interpreted by the plan's extension.
	 *
	 * @var array<string, mixed>|null
	 */
	private $pricing_policy;

	/**
	 * Plan category.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * Merchant lifecycle status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Manual display order.
	 *
	 * @var int
	 */
	private $sort_order;

	/**
	 * Optional stable external identifier, unique at the storage layer - the
	 * consumer-side dedup key. Immutable post-create.
	 *
	 * @var string|null
	 */
	private $merchant_code;

	/**
	 * Owning extension slug, or null until owner semantics are assigned.
	 *
	 * @var string|null
	 */
	private $extension_slug;

	/**
	 * Use {@see self::create()} or {@see self::from_storage()}.
	 *
	 * @param int|null                  $id              Plan id, or null before save.
	 * @param string                    $name            Display name.
	 * @param string|null               $description     Optional description.
	 * @param BillingPolicy             $billing_policy  Billing cadence.
	 * @param DeliveryPolicy|null       $delivery_policy Optional delivery policy.
	 * @param array<string, mixed>|null $pricing_policy  Optional pricing payload.
	 * @param string                    $category        Plan category.
	 * @param string                    $status          Merchant lifecycle status.
	 * @param int                       $sort_order      Manual display order.
	 * @param string|null               $merchant_code   Optional stable external identifier.
	 * @param string|null               $extension_slug  Owning extension slug.
	 */
	private function __construct(
		?int $id,
		string $name,
		?string $description,
		BillingPolicy $billing_policy,
		?DeliveryPolicy $delivery_policy,
		?array $pricing_policy,
		string $category,
		string $status,
		int $sort_order,
		?string $merchant_code,
		?string $extension_slug
	) {
		self::validate_status( $status );

		$this->id              = $id;
		$this->name            = $name;
		$this->description     = $description;
		$this->billing_policy  = $billing_policy;
		$this->delivery_policy = $delivery_policy;
		$this->pricing_policy  = $pricing_policy;
		$this->category        = $category;
		$this->status          = $status;
		$this->sort_order      = $sort_order;
		$this->merchant_code   = $merchant_code;
		$this->extension_slug  = $extension_slug;
	}

	/**
	 * Build a new, unsaved plan.
	 *
	 * @param array<string, mixed> $args Plan attributes.
	 * @throws InvalidArgumentException If pricing_policy is not an object (string-keyed array) or null.
	 */
	public static function create( array $args ): self {
		$pricing_policy = self::assert_object_or_null( $args['pricing_policy'] ?? null );

		$billing_policy = $args['billing_policy'] ?? null;
		if ( ! $billing_policy instanceof BillingPolicy ) {
			throw new InvalidArgumentException( 'Plan: billing_policy is required and must be a BillingPolicy instance.' );
		}

		$delivery_policy = $args['delivery_policy'] ?? null;
		if ( null !== $delivery_policy && ! $delivery_policy instanceof DeliveryPolicy ) {
			throw new InvalidArgumentException( 'Plan: delivery_policy must be a DeliveryPolicy instance or null.' );
		}

		return new self(
			null,
			ScalarCoercion::coerce_string( $args['name'] ?? null ),
			ScalarCoercion::coerce_nullable_string( $args['description'] ?? null ),
			$billing_policy,
			$delivery_policy,
			$pricing_policy,
			ScalarCoercion::coerce_string( $args['category'] ?? null, self::DEFAULT_CATEGORY ),
			ScalarCoercion::coerce_string( $args['status'] ?? null, self::DEFAULT_STATUS ),
			ScalarCoercion::coerce_int( $args['sort_order'] ?? null, 0 ),
			ScalarCoercion::coerce_nullable_string( $args['merchant_code'] ?? null ),
			ScalarCoercion::coerce_nullable_string( $args['extension_slug'] ?? null )
		);
	}

	/**
	 * Hydrate from a stored row. Policy columns arrive JSON-decoded.
	 *
	 * The pricing payload is checked only for shape (object or null); its
	 * semantics belong to the owning extension.
	 *
	 * @param array<string, mixed> $row Decoded plan row.
	 * @throws InvalidArgumentException If the stored pricing_policy is not an object.
	 */
	public static function from_storage( array $row ): self {
		$pricing_policy = self::assert_object_or_null( $row['pricing_policy'] ?? null );

		return new self(
			isset( $row['id'] ) ? ScalarCoercion::coerce_int( $row['id'] ) : null,
			ScalarCoercion::coerce_string( $row['name'] ?? null ),
			ScalarCoercion::coerce_nullable_string( $row['description'] ?? null ),
			BillingPolicy::from_array( is_array( $row['billing_policy'] ?? null ) ? $row['billing_policy'] : array() ),
			isset( $row['delivery_policy'] ) && is_array( $row['delivery_policy'] ) ? DeliveryPolicy::from_array( $row['delivery_policy'] ) : null,
			$pricing_policy,
			ScalarCoercion::coerce_string( $row['category'] ?? null, self::DEFAULT_CATEGORY ),
			ScalarCoercion::coerce_string( $row['status'] ?? null, self::DEFAULT_STATUS ),
			ScalarCoercion::coerce_int( $row['sort_order'] ?? null, 0 ),
			ScalarCoercion::coerce_nullable_string( $row['merchant_code'] ?? null ),
			ScalarCoercion::coerce_nullable_string( $row['extension_slug'] ?? null )
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
	 * Optional description.
	 */
	public function get_description(): ?string {
		return $this->description;
	}

	/**
	 * Set the description.
	 *
	 * @param string|null $description Description.
	 */
	public function set_description( ?string $description ): void {
		$this->description = $description;
	}

	/**
	 * Billing cadence.
	 */
	public function get_billing_policy(): BillingPolicy {
		return $this->billing_policy;
	}

	/**
	 * Set the billing cadence.
	 *
	 * @param BillingPolicy $billing_policy Billing cadence.
	 */
	public function set_billing_policy( BillingPolicy $billing_policy ): void {
		$this->billing_policy = $billing_policy;
	}

	/**
	 * Optional delivery policy.
	 */
	public function get_delivery_policy(): ?DeliveryPolicy {
		return $this->delivery_policy;
	}

	/**
	 * Set the delivery policy.
	 *
	 * @param DeliveryPolicy|null $delivery_policy Delivery policy.
	 */
	public function set_delivery_policy( ?DeliveryPolicy $delivery_policy ): void {
		$this->delivery_policy = $delivery_policy;
	}

	/**
	 * Optional pricing payload, as stored.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_pricing_policy(): ?array {
		return $this->pricing_policy;
	}

	/**
	 * Set the pricing payload.
	 *
	 * @param array<string, mixed>|null $pricing_policy Pricing payload.
	 * @throws InvalidArgumentException If pricing_policy is not an object (string-keyed array).
	 */
	public function set_pricing_policy( ?array $pricing_policy ): void {
		$this->pricing_policy = self::assert_object_or_null( $pricing_policy );
	}

	/**
	 * Plan category.
	 */
	public function get_category(): string {
		return $this->category;
	}

	/**
	 * Set the plan category.
	 *
	 * @param string $category Plan category.
	 */
	public function set_category( string $category ): void {
		$this->category = $category;
	}

	/**
	 * Merchant lifecycle status.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Set the merchant lifecycle status.
	 *
	 * @param string $status Plan status.
	 * @throws InvalidArgumentException If the status is unknown.
	 */
	public function set_status( string $status ): void {
		self::validate_status( $status );
		$this->status = $status;
	}

	/**
	 * Manual display order.
	 */
	public function get_sort_order(): int {
		return $this->sort_order;
	}

	/**
	 * Set the manual display order.
	 *
	 * @param int $sort_order Sort order.
	 */
	public function set_sort_order( int $sort_order ): void {
		$this->sort_order = $sort_order;
	}

	/**
	 * Optional stable external identifier, or null. Immutable post-create.
	 */
	public function get_merchant_code(): ?string {
		return $this->merchant_code;
	}

	/**
	 * Owning extension slug, or null.
	 */
	public function get_extension_slug(): ?string {
		return $this->extension_slug;
	}

	/**
	 * Serialize to the storage column shape (excluding generated id/timestamps).
	 *
	 * Policy value objects are returned as arrays and the pricing payload as
	 * stored; the repository JSON-encodes them.
	 *
	 * @return array<string, mixed>
	 */
	public function to_storage(): array {
		return array(
			'name'            => $this->name,
			'description'     => $this->description,
			'billing_policy'  => $this->billing_policy->to_array(),
			'delivery_policy' => null !== $this->delivery_policy ? $this->delivery_policy->to_array() : null,
			'pricing_policy'  => $this->pricing_policy,
			'category'        => $this->category,
			'status'          => $this->status,
			'sort_order'      => $this->sort_order,
			'merchant_code'   => $this->merchant_code,
			'extension_slug'  => $this->extension_slug,
		);
	}

	/**
	 * Validate a plan lifecycle status.
	 *
	 * @param string $status Status to validate.
	 * @throws InvalidArgumentException If the status is unknown.
	 */
	private static function validate_status( string $status ): void {
		if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Plan: invalid status "%s".', $status )
			);
		}
	}

	/**
	 * Accept a pricing payload only as an object (string-keyed array) or null.
	 * An empty array is accepted (a JSON `{}` decodes to it).
	 *
	 * @param mixed $value Candidate payload.
	 * @return array<string, mixed>|null
	 * @throws InvalidArgumentException If the value is a list or not an array.
	 */
	private static function assert_object_or_null( $value ): ?array {
		if ( null === $value ) {
			return null;
		}

		$message = 'Plan: pricing_policy must be an object (string-keyed array) or null.';
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
