<?php
/**
 * Plans - the engine's public plan write facade.
 *
 * Extensions create and update plans from explicit argument arrays: the engine records
 * the payloads it is given and interprets none of them. It checks integrity only (a
 * non-empty name, a registered status, object-shaped policies), then lets the plan's
 * owner validate the write through `woocommerce_subscriptions_engine_validate_plan`.
 * Any caller may write any plan (authorization is the caller's concern). The engine
 * opens no transaction and keeps no cache.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WP_Error;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Public plan write facade.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Plans {

	/**
	 * Keys accepted by {@see self::create()} (plus `owner`) and {@see self::update()}.
	 *
	 * @var array<int, string>
	 */
	private const PLAN_KEYS = array( 'name', 'status', 'billing_policy', 'pricing_policy', 'delivery_policy' );

	/**
	 * Policy keys: each takes a string-keyed array (an object) or null.
	 *
	 * @var array<int, string>
	 */
	private const POLICY_KEYS = array( 'billing_policy', 'pricing_policy', 'delivery_policy' );

	/**
	 * Logger source.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- create() and update() also throw RuntimeException indirectly, through validate() and the repository.
	/**
	 * Create a plan.
	 *
	 * @param array<string, mixed> $args Plan fields: `owner` (required, the owning extension slug),
	 *                                   `name` (required, non-empty), `status` (a registered plan
	 *                                   status, default `active`), and `billing_policy`,
	 *                                   `pricing_policy`, `delivery_policy` (string-keyed arrays
	 *                                   the owner interprets, or null).
	 * @return int The new plan id.
	 * @throws InvalidArgumentException If a key is unknown or a value is invalid, or a
	 *                                  {@see PlanValidationException} when the owner refuses the plan.
	 * @throws RuntimeException If a validation callback throws or the insert fails.
	 */
	public static function create( array $args ): int {
		self::assert_known_keys( $args, array_merge( array( 'owner' ), self::PLAN_KEYS ) );

		$owner = $args['owner'] ?? null;
		if ( ! is_string( $owner ) || '' === $owner ) {
			throw new InvalidArgumentException( 'Plans: "owner" is required and must be a non-empty string.' );
		}
		if ( ! array_key_exists( 'name', $args ) ) {
			throw new InvalidArgumentException( 'Plans: "name" is required.' );
		}

		$plan = Plan::create(
			array(
				'name'           => '',
				'extension_slug' => $owner,
			)
		);
		self::apply( $plan, $args );
		self::validate( $plan );

		return ( new PlanRepository() )->insert( $plan );
	}

	/**
	 * Write the given fields to an existing plan.
	 *
	 * Takes the keys of {@see self::create()} except `owner`. A present policy key
	 * replaces the whole payload (null clears it); policies are never merged.
	 *
	 * @param int                  $id   Plan id.
	 * @param array<string, mixed> $args Fields to write.
	 * @return bool True when written; false when the plan does not exist.
	 * @throws InvalidArgumentException If the id is not positive, a key is unknown or a value is invalid,
	 *                                  or a {@see PlanValidationException} when the owner refuses the plan.
	 * @throws RuntimeException If a validation callback throws or the update fails.
	 */
	public static function update( int $id, array $args ): bool {
		if ( $id <= 0 ) {
			throw new InvalidArgumentException( 'Plans: the plan id must be a positive integer.' );
		}
		self::assert_known_keys( $args, self::PLAN_KEYS );

		$repository = new PlanRepository();
		$plan       = $repository->find( $id );
		if ( null === $plan ) {
			return false;
		}

		self::apply( $plan, $args );
		self::validate( $plan );

		return $repository->update( $plan );
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Add a meta value to a plan, like `add_post_meta()`. A key may hold several values.
	 *
	 * @param int    $id     Plan id.
	 * @param string $key    Meta key.
	 * @param mixed  $value  Meta value; serialized when not scalar.
	 * @param bool   $unique When true, add nothing if the key already exists. Advisory: checked
	 *                       before the insert with no unique index, so concurrent adds can both write.
	 * @return int|null The meta row id; null when the plan does not exist or `$unique` and the key exists.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function add_meta( int $id, string $key, $value, bool $unique = false ): ?int {
		self::assert_meta_key( $key );

		$repository = new PlanRepository();
		if ( ! $repository->exists( $id ) ) {
			return null;
		}

		return $repository->add_meta( $id, $key, $value, $unique );
	}

	/**
	 * Update a plan's meta values for `$key`, like `update_post_meta()`: adds the key
	 * when absent, else rewrites every value, or only the values equal to `$prev_value`.
	 * The absent-key check runs before the write with no unique index, so it is not a lock.
	 *
	 * @param int    $id         Plan id.
	 * @param string $key        Meta key.
	 * @param mixed  $value      New value; serialized when not scalar.
	 * @param mixed  $prev_value Only update values equal to this; null updates all. Any other
	 *                           value ('' and false included) matches literally.
	 * @return bool True when a value was added or changed; false when nothing changed or the plan does not exist.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function update_meta( int $id, string $key, $value, $prev_value = null ): bool {
		self::assert_meta_key( $key );

		$repository = new PlanRepository();
		if ( ! $repository->exists( $id ) ) {
			return false;
		}

		return $repository->update_meta( $id, $key, $value, $prev_value );
	}

	/**
	 * Delete a plan's meta values for `$key`, like `delete_post_meta()`.
	 *
	 * @param int    $id    Plan id.
	 * @param string $key   Meta key.
	 * @param mixed  $value Only delete values equal to this; null deletes every value for the key.
	 *                      Any other value ('' and false included) matches literally.
	 * @return bool True when at least one value was deleted.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function delete_meta( int $id, string $key, $value = null ): bool {
		self::assert_meta_key( $key );

		return ( new PlanRepository() )->delete_meta( $id, $key, $value );
	}

	/**
	 * Read plan meta (WordPress `get_post_meta()` semantics), oldest value first.
	 *
	 * @param int    $id     Plan id.
	 * @param string $key    Meta key; empty for every key.
	 * @param bool   $single With a key: return the first value only.
	 * @return mixed Empty key: values grouped by key. Key + `$single`: the first value, or ''
	 *               when absent. Key only: the list of values (`[]` when absent).
	 */
	public static function get_meta( int $id, string $key = '', bool $single = false ) {
		return ( new PlanRepository() )->get_meta( $id, $key, $single );
	}

	/**
	 * Validate the caller's fields and apply them to a plan through its setters.
	 * Nothing is written to storage.
	 *
	 * @param Plan                 $plan Plan to change.
	 * @param array<string, mixed> $args Caller fields (known keys only; `owner` is ignored).
	 * @throws InvalidArgumentException If a value is invalid.
	 */
	private static function apply( Plan $plan, array $args ): void {
		if ( array_key_exists( 'name', $args ) ) {
			$name = is_string( $args['name'] ) ? trim( $args['name'] ) : '';
			if ( '' === $name ) {
				throw new InvalidArgumentException( 'Plans: "name" must be a non-empty string.' );
			}
			$plan->set_name( $name );
		}

		if ( array_key_exists( 'status', $args ) ) {
			$status = $args['status'];
			if ( ! is_string( $status ) || ! PlanStatus::is_registered( $status ) ) {
				throw new InvalidArgumentException(
					sprintf( 'Plans: "status" must be a registered plan status, got "%s".', esc_html( is_scalar( $status ) ? (string) $status : gettype( $status ) ) )
				);
			}
			$plan->set_status( $status );
		}

		foreach ( self::POLICY_KEYS as $key ) {
			if ( ! array_key_exists( $key, $args ) ) {
				continue;
			}

			$value = $args[ $key ];
			if ( null !== $value && ! is_array( $value ) ) {
				throw new InvalidArgumentException( sprintf( 'Plans: "%s" must be an object (string-keyed array) or null.', esc_html( $key ) ) );
			}

			switch ( $key ) {
				case 'billing_policy':
					$plan->set_billing_policy( $value );
					break;
				case 'pricing_policy':
					$plan->set_pricing_policy( $value );
					break;
				default:
					$plan->set_delivery_policy( $value );
					break;
			}
		}
	}

	/**
	 * Let the plan's owner validate the would-be plan before it is written.
	 *
	 * @param Plan $plan The would-be plan (unsaved on create).
	 * @throws PlanValidationException If a callback added errors.
	 * @throws RuntimeException If a callback threw.
	 */
	private static function validate( Plan $plan ): void {
		$errors = new WP_Error();
		$owner  = (string) $plan->get_extension_slug();

		try {
			/**
			 * Fires before every plan write (PHP facade or REST), on create and on update,
			 * including status-only updates, so the plan's owner can refuse it.
			 *
			 * Add errors to $errors to refuse the write; act only on your own $owner slug.
			 * The view is the would-be state after the write (its id is 0 on create) and is
			 * read-only.
			 *
			 * @since 0.1.0
			 *
			 * @param WP_Error $errors Error collector.
			 * @param PlanView $plan   The would-be plan.
			 * @param string   $owner  Owning extension slug.
			 */
			do_action( 'woocommerce_subscriptions_engine_validate_plan', $errors, PlanView::from_plan( $plan ), $owner );
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Plans: plan validation for extension "%s" (plan %s) threw: %s', $owner, null === $plan->get_id() ? 'new' : (string) $plan->get_id(), $e->getMessage() ),
				array(
					'source'         => self::LOG_SOURCE,
					'extension_slug' => $owner,
					'plan_id'        => $plan->get_id(),
				)
			);

			throw new RuntimeException( 'Plans: the plan could not be validated.', 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Literal message.
		}

		if ( $errors->has_errors() ) {
			throw new PlanValidationException( $errors ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The exception escapes its message.
		}
	}

	/**
	 * Throw on any key outside `$allowed`.
	 *
	 * @param array<string, mixed> $args    Caller args.
	 * @param array<int, string>   $allowed Allowed keys.
	 * @throws InvalidArgumentException If a key is unknown.
	 */
	private static function assert_known_keys( array $args, array $allowed ): void {
		$unknown = array_diff( array_keys( $args ), $allowed );
		if ( array() !== $unknown ) {
			throw new InvalidArgumentException( sprintf( 'Plans: unknown key(s) %s.', esc_html( implode( ', ', array_map( 'strval', $unknown ) ) ) ) );
		}
	}

	/**
	 * Throw on an empty meta key.
	 *
	 * @param string $key Meta key.
	 * @throws InvalidArgumentException If the key is empty.
	 */
	private static function assert_meta_key( string $key ): void {
		if ( '' === $key ) {
			throw new InvalidArgumentException( 'Plans: the meta key must not be empty.' );
		}
	}
}
