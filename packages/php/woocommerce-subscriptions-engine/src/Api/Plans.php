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
 * Billing payload contract: the engine reads one plan payload itself. Until every
 * contract carries a plan snapshot, renewal and reactivation fall back to the live
 * plan's `billing_policy` and read it with {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy::from_array_with_usable_cadence()}
 * (a string `period` of day, week, month or year, a positive int `interval`, and
 * optional cycle bounds and trial). A payload of another shape is still stored, but
 * renewal parks such a contract and reactivation rolls it without a cadence. The
 * snapshot's `billing_policy` is read the same way, and one that fails the rule falls
 * back to the live plan.
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
	 * Keys accepted by {@see self::update()}, as a key map.
	 *
	 * @var array<string, true>
	 */
	private const PLAN_KEYS = array(
		'name'            => true,
		'status'          => true,
		'billing_policy'  => true,
		'pricing_policy'  => true,
		'delivery_policy' => true,
	);

	/**
	 * Keys accepted by {@see self::create()}: the update keys plus the create-only `extension_slug`.
	 *
	 * @var array<string, true>
	 */
	private const CREATE_KEYS = array( 'extension_slug' => true ) + self::PLAN_KEYS;

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
	 * Unknown keys raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * @param array<string, mixed> $args Plan fields: `extension_slug` (required, the owning
	 *                                   extension), `name` (required, non-empty), `status` (a
	 *                                   registered plan status, default `active`), and
	 *                                   `billing_policy`, `pricing_policy`, `delivery_policy`
	 *                                   (string-keyed arrays the owner interprets, or null).
	 * @return int The new plan id.
	 * @throws InvalidArgumentException If a required key is missing or a value is invalid, or a
	 *                                  {@see PlanValidationException} when the owner refuses the plan.
	 * @throws RuntimeException If a validation callback throws or the insert fails.
	 */
	public static function create( array $args ): int {
		$args = self::known_keys( __METHOD__, $args, self::CREATE_KEYS );

		$extension_slug = $args['extension_slug'] ?? null;
		if ( ! is_string( $extension_slug ) || '' === $extension_slug ) {
			throw new InvalidArgumentException( 'Plans: "extension_slug" is required and must be a non-empty string.' );
		}
		unset( $args['extension_slug'] );
		if ( ! array_key_exists( 'name', $args ) ) {
			throw new InvalidArgumentException( 'Plans: "name" is required.' );
		}

		$plan = Plan::create(
			array(
				'name'           => '',
				'extension_slug' => $extension_slug,
			)
		);
		self::apply( $plan, $args );
		self::validate( $plan );

		return ( new PlanRepository() )->insert( $plan );
	}

	/**
	 * Write the given fields to an existing plan.
	 *
	 * Takes the keys of {@see self::create()} except `extension_slug`. Only the columns of
	 * the present keys are written, so fields a concurrent writer changed in between keep
	 * its values. A present policy key replaces the whole payload (null clears it);
	 * policies are never merged. Re-sending the stored status is accepted even when that
	 * status is no longer registered (its extension was deactivated). Unknown keys
	 * (`extension_slug` included) raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * @param int                  $id   Plan id.
	 * @param array<string, mixed> $args Fields to write.
	 * @return bool True when the plan exists and the write passed validation (an empty `$args`
	 *              validates the stored plan and writes nothing); false when the plan does not exist.
	 * @throws InvalidArgumentException If the id is not positive or a value is invalid,
	 *                                  or a {@see PlanValidationException} when the owner refuses the plan.
	 * @throws RuntimeException If a validation callback throws or the update fails.
	 */
	public static function update( int $id, array $args ): bool {
		if ( $id <= 0 ) {
			throw new InvalidArgumentException( 'Plans: the plan id must be a positive integer.' );
		}
		$args = self::known_keys( __METHOD__, $args, self::PLAN_KEYS );

		$repository = new PlanRepository();
		$plan       = $repository->find( $id );
		if ( null === $plan ) {
			return false;
		}

		self::apply( $plan, $args );
		self::validate( $plan );

		if ( array() !== $args ) {
			$repository->update_fields( $plan, array_keys( $args ) );
		}

		return true;
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
	 * @param array<string, mixed> $args Caller fields (known keys only, no `extension_slug`).
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
			// The stored status stays writable after its extension is deactivated (as in Plan::set_status()).
			if ( ! is_string( $status ) || ( $status !== $plan->get_status() && ! PlanStatus::is_registered( $status ) ) ) {
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
			 * Changed with plans as records: `$plan` is a read-only {@see PlanView}. Earlier
			 * engine versions passed the Core `Plan` entity; a callback still typed on `Plan`
			 * throws here, which refuses every plan write, so update such callbacks together
			 * with this engine version.
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
	 * Drop the keys outside `$allowed`, with a `_doing_it_wrong()` notice for each.
	 *
	 * @param string                   $method  Public facade method, for the notice.
	 * @param array<int|string, mixed> $args    Caller arguments.
	 * @param array<string, true>      $allowed Accepted keys, as a key map.
	 * @return array<string, mixed> The arguments with known keys only.
	 */
	private static function known_keys( string $method, array $args, array $allowed ): array {
		foreach ( array_keys( array_diff_key( $args, $allowed ) ) as $key ) {
			_doing_it_wrong(
				esc_html( $method ),
				sprintf( 'Plans: unknown key "%s" ignored.', esc_html( (string) $key ) ),
				'0.0.1'
			);
		}

		$known = array();
		foreach ( array_intersect_key( $args, $allowed ) as $key => $value ) {
			$known[ (string) $key ] = $value;
		}

		return $known;
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
