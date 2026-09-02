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
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy;
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

	public const ALLOWED_POLICY_TYPES = array( 'percentage', 'fixed_amount', 'price', 'bogo' );

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
	 * Optional pricing policy.
	 *
	 * @var PricingPolicy|null
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
	 * @param int|null            $id              Plan id, or null before save.
	 * @param string              $name            Display name.
	 * @param string|null         $description     Optional description.
	 * @param BillingPolicy       $billing_policy  Billing cadence.
	 * @param DeliveryPolicy|null $delivery_policy Optional delivery policy.
	 * @param PricingPolicy|null  $pricing_policy  Optional pricing policy.
	 * @param string              $category        Plan category.
	 * @param string              $status          Merchant lifecycle status.
	 * @param int                 $sort_order      Manual display order.
	 * @param string|null         $merchant_code   Optional stable external identifier.
	 * @param string|null         $extension_slug  Owning extension slug.
	 */
	private function __construct(
		?int $id,
		string $name,
		?string $description,
		BillingPolicy $billing_policy,
		?DeliveryPolicy $delivery_policy,
		?PricingPolicy $pricing_policy,
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
	 * @throws InvalidArgumentException If pricing_policy entries fail validation.
	 */
	public static function create( array $args ): self {
		$pricing_policy = $args['pricing_policy'] ?? null;
		if ( null !== $pricing_policy && ! $pricing_policy instanceof PricingPolicy ) {
			throw new InvalidArgumentException( 'Plan: pricing_policy must be a PricingPolicy instance or null.' );
		}
		if ( null !== $pricing_policy ) {
			self::validate_pricing_policy( $pricing_policy );
		}

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
	 * The stored pricing policy is re-validated here: a WordPress database can be
	 * mutated outside this engine's flows, and an out-of-range stored rule (a
	 * negative or over-100 value) would otherwise feed billing math silently. We
	 * fail loud on a corrupted row rather than risk a mischarge.
	 *
	 * @param array<string, mixed> $row Decoded plan row.
	 * @throws InvalidArgumentException If the stored pricing_policy fails validation.
	 */
	public static function from_storage( array $row ): self {
		$pricing_policy = isset( $row['pricing_policy'] ) && is_array( $row['pricing_policy'] )
			? PricingPolicy::from_array( $row['pricing_policy'] )
			: null;
		if ( null !== $pricing_policy ) {
			self::validate_pricing_policy( $pricing_policy );
		}

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
	 * Optional pricing policy.
	 */
	public function get_pricing_policy(): ?PricingPolicy {
		return $this->pricing_policy;
	}

	/**
	 * Set the pricing policy.
	 *
	 * @param PricingPolicy|null $pricing_policy Pricing policy.
	 * @throws InvalidArgumentException If pricing_policy entries fail validation.
	 */
	public function set_pricing_policy( ?PricingPolicy $pricing_policy ): void {
		if ( null !== $pricing_policy ) {
			self::validate_pricing_policy( $pricing_policy );
		}
		$this->pricing_policy = $pricing_policy;
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
	 * Apply this plan's pricing policy (if any) to a base price for the cycle.
	 *
	 * When no pricing policy is set, returns `$base_price` unchanged.
	 *
	 * @param float $base_price The product's base price for this cycle.
	 * @param int   $cycle      1-indexed cycle number (1 = first billing cycle).
	 */
	public function calculate_price( float $base_price, int $cycle = 1 ): float {
		if ( null === $this->pricing_policy ) {
			return $base_price;
		}

		return $this->pricing_policy->calculate_price( $base_price, $cycle );
	}

	/**
	 * Calculate the line total for this plan and cycle.
	 *
	 * When no pricing policy is set, this returns unit_price * quantity. Otherwise
	 * the plan delegates to its pricing policy.
	 *
	 * @param float $unit_price The product's base unit price for this cycle.
	 * @param float $quantity   Quantity on the line.
	 * @param int   $cycle      1-indexed cycle number.
	 */
	public function calculate_line_total( float $unit_price, float $quantity, int $cycle = 1 ): float {
		if ( null === $this->pricing_policy ) {
			return max( 0.0, $unit_price * $quantity );
		}

		return $this->pricing_policy->calculate_line_total( $unit_price, $quantity, $cycle );
	}

	/**
	 * Serialize to the storage column shape (excluding generated id/timestamps).
	 *
	 * Policy value objects are returned as arrays; the repository JSON-encodes them.
	 *
	 * @return array<string, mixed>
	 */
	public function to_storage(): array {
		return array(
			'name'            => $this->name,
			'description'     => $this->description,
			'billing_policy'  => $this->billing_policy->to_array(),
			'delivery_policy' => null !== $this->delivery_policy ? $this->delivery_policy->to_array() : null,
			'pricing_policy'  => null !== $this->pricing_policy ? $this->pricing_policy->to_array() : null,
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
	 * Validate every entry in a pricing policy's policies[] and one_time_fees[].
	 *
	 * Rules:
	 *  - policies[].type is one of percentage, fixed_amount, price, bogo.
	 *  - policies[].value is numeric and non-negative; percentage is capped at 100;
	 *    bogo must be exactly 0 (a value-less type - the benefit is an in-kind bonus
	 *    unit, so a non-zero value is meaningless and rejected rather than silently
	 *    stored).
	 *  - policies[].duration_cycles is optional, integer, and positive.
	 *  - one_time_fees[].amount is numeric and non-negative.
	 *  - one_time_fees[].taxable is a bool.
	 *  - one_time_fees[].tax_class is string or null (preserves '' != null).
	 *  - one_time_fees[].kind is intentionally not whitelisted - consumers extend
	 *    with namespaced kinds.
	 *
	 * @param PricingPolicy $pricing_policy Policy to validate.
	 * @throws InvalidArgumentException With a message naming the offending entry index.
	 */
	private static function validate_pricing_policy( PricingPolicy $pricing_policy ): void {
		foreach ( $pricing_policy->get_policies() as $index => $entry ) {
			if ( ! is_array( $entry ) ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.policies[%d]: must be an array, got %s', (int) $index, gettype( $entry ) )
				);
			}

			$type           = $entry['type'] ?? null;
			$value          = $entry['value'] ?? null;
			$starting_cycle = $entry['starting_cycle'] ?? null;
			$duration       = $entry['duration_cycles'] ?? null;

			if ( ! is_string( $type ) || ! in_array( $type, self::ALLOWED_POLICY_TYPES, true ) ) {
				$shown = is_scalar( $type ) ? (string) $type : gettype( $type );
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.policies[%d]: invalid type %s', (int) $index, $shown )
				);
			}

			if ( ! is_numeric( $value ) ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.policies[%d]: value must be numeric, got %s', (int) $index, gettype( $value ) )
				);
			}

			$value = (float) $value;

			if ( $value < 0 ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.policies[%d]: %s value must be non-negative, got %s', (int) $index, $type, $value )
				);
			}

			if ( 'percentage' === $type && $value > 100 ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.policies[%d]: percentage must not exceed 100, got %s', (int) $index, $value )
				);
			}

			if ( 'bogo' === $type && 0.0 !== $value ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.policies[%d]: bogo is value-less; value must be 0 or omitted, got %s', (int) $index, $value )
				);
			}

			if ( null !== $starting_cycle ) {
				if ( ! is_int( $starting_cycle ) ) {
					throw new InvalidArgumentException(
						sprintf( 'pricing_policy.policies[%d]: starting_cycle must be an integer, got %s', (int) $index, gettype( $starting_cycle ) )
					);
				}

				if ( $starting_cycle < 1 ) {
					throw new InvalidArgumentException(
						sprintf( 'pricing_policy.policies[%d]: starting_cycle must be at least 1, got %d', (int) $index, $starting_cycle )
					);
				}
			}

			if ( null !== $duration ) {
				if ( ! is_int( $duration ) ) {
					throw new InvalidArgumentException(
						sprintf( 'pricing_policy.policies[%d]: duration_cycles must be an integer, got %s', (int) $index, gettype( $duration ) )
					);
				}

				if ( $duration < 1 ) {
					throw new InvalidArgumentException(
						sprintf( 'pricing_policy.policies[%d]: duration_cycles must be at least 1, got %d', (int) $index, $duration )
					);
				}
			}
		}

		foreach ( $pricing_policy->get_one_time_fees() as $index => $entry ) {
			$amount    = $entry['amount'] ?? null;
			$taxable   = $entry['taxable'] ?? null;
			$tax_class = $entry['tax_class'] ?? null;

			if ( ! is_numeric( $amount ) ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.one_time_fees[%d]: amount must be numeric, got %s', (int) $index, gettype( $amount ) )
				);
			}

			if ( (float) $amount < 0 ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.one_time_fees[%d]: amount must be non-negative, got %s', (int) $index, $amount )
				);
			}

			if ( ! is_bool( $taxable ) ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.one_time_fees[%d]: taxable must be a bool, got %s', (int) $index, gettype( $taxable ) )
				);
			}

			if ( null !== $tax_class && ! is_string( $tax_class ) ) {
				throw new InvalidArgumentException(
					sprintf( 'pricing_policy.one_time_fees[%d]: tax_class must be string or null, got %s', (int) $index, gettype( $tax_class ) )
				);
			}
		}
	}
}
