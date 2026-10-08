<?php
/**
 * Plans - the engine's public plan facade (reads and writes).
 *
 * Extensions create, update and read plans through explicit argument arrays: the engine records
 * the payloads it is given and interprets none of them. It checks integrity only (a
 * non-empty name, a registered status, object-shaped policies), then lets the plan's
 * owner validate the write through `woocommerce_subscriptions_engine_validate_plan`.
 * Any caller may read any plan; an update names the plan's owning extension and never
 * reaches a plan of another one (authorization is the caller's concern). Reads return
 * read-only {@see PlanView}s. The engine opens no transaction and keeps no cache.
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

use DomainException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WP_Error;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\PlanView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Public plan facade: reads and writes.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Plans {

	/**
	 * Keys accepted by {@see self::create()} and {@see self::update()}, as a key map. The
	 * `extension_slug` sets the owner on create and scopes the write on update; it is never
	 * written on update. The other keys are the plan fields.
	 *
	 * @var array<string, true>
	 */
	private const PLAN_KEYS = array(
		'extension_slug'  => true,
		'name'            => true,
		'status'          => true,
		'billing_policy'  => true,
		'pricing_policy'  => true,
		'delivery_policy' => true,
	);

	/**
	 * Keys accepted by {@see self::list()}, as a key map.
	 *
	 * @var array<string, true>
	 */
	private const LIST_KEYS = array(
		'extension_slug' => true,
		'status'         => true,
		'ids'            => true,
		'limit'          => true,
		'offset'         => true,
	);

	/**
	 * Default `limit` of {@see self::list()}.
	 */
	private const DEFAULT_LIST_LIMIT = 200;

	/**
	 * Logger source.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- create() and update() also throw RuntimeException indirectly, through validate_with_owner() and the repository.
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
	 * @return PlanView The new plan, built from the written fields (no re-read).
	 * @throws InvalidArgumentException If a required key is missing or a value is invalid, or a
	 *                                  {@see PlanValidationException} when the owner refuses the plan.
	 * @throws RuntimeException If a validation callback throws or the insert fails.
	 */
	public static function create( array $args ): PlanView {
		$filtered_args  = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::PLAN_KEYS );
		$extension_slug = ArgumentValidator::validate_nullable_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		unset( $filtered_args['extension_slug'] );

		try {
			// The entity requires a name on create; apply() then sets every field, the name included.
			$plan = Plan::create(
				array(
					'extension_slug' => $extension_slug,
					'name'           => ArgumentValidator::validate_string( 'name', $filtered_args['name'] ?? '' ),
				)
			);
			self::apply( $plan, $filtered_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		self::validate_with_owner( $plan );

		( new PlanRepository() )->insert( $plan );

		return PlanView::from_plan( $plan );
	}

	/**
	 * Write the given fields to an existing plan of the given owner.
	 *
	 * Takes the keys of {@see self::create()}. `extension_slug` is required, as on create,
	 * but here it is the owner scope, never a written field (a plan's owner never changes):
	 * the plan is read and written only where its row carries that slug, so a plan of
	 * another extension reads as missing and is never written. Only the columns of the
	 * present plan fields are written, so fields a concurrent writer changed in between
	 * keep its values. A present policy key replaces the whole payload (null clears it);
	 * policies are never merged. Re-sending the stored status is accepted even when that
	 * status is no longer registered (its extension was deactivated). Args with no plan
	 * field validate the stored plan and write nothing. Unknown keys raise a
	 * `_doing_it_wrong()` notice and are ignored. The engine opens no transaction: wrap
	 * the call in one when it must be atomic with other writes.
	 *
	 * @param int                  $plan_id Plan id.
	 * @param array<string, mixed> $args    `extension_slug` (required, the owning extension)
	 *                                      and the fields to write.
	 * @return PlanView|null The row as read before the write plus the written fields (a column
	 *                       another writer changed meanwhile may be stale here, not in storage);
	 *                       null when no plan of that owner has the id (also when it is
	 *                       deleted before the write).
	 * @throws InvalidArgumentException If `extension_slug` is missing or a value is invalid, or a
	 *                                  {@see PlanValidationException} when the owner refuses the plan.
	 * @throws RuntimeException If a validation callback throws or the update fails.
	 */
	public static function update( int $plan_id, array $args ): ?PlanView {
		$filtered_args  = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::PLAN_KEYS );
		$extension_slug = ArgumentValidator::validate_non_empty_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		unset( $filtered_args['extension_slug'] );

		$repository = new PlanRepository();
		$plan       = $repository->find( $plan_id, $extension_slug );
		if ( null === $plan ) {
			return null;
		}

		try {
			self::apply( $plan, $filtered_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		self::validate_with_owner( $plan );

		$fields = array_keys( $filtered_args );
		if ( array() === $fields ) {
			return PlanView::from_plan( $plan );
		}

		if ( ! $repository->update_fields( $plan, $fields ) ) {
			return null;
		}

		return PlanView::from_plan( $plan );
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Add a meta value to a plan, like `add_post_meta()`. A key may hold several values.
	 * The plan is not looked up: meta for an unknown plan id is a caller error.
	 *
	 * @param int    $plan_id Plan id.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value; serialized when not scalar.
	 * @param bool   $unique  When true, add nothing if the key already exists. Advisory: checked
	 *                        before the insert with no unique index, so concurrent adds can both write.
	 * @return int|null The meta row id; null when `$unique` and the key exists.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function add_meta( int $plan_id, string $key, $value, bool $unique = false ): ?int {
		return ( new PlanRepository() )->add_meta( $plan_id, $key, $value, $unique );
	}

	/**
	 * Update a plan's meta values for `$key`, like `update_post_meta()`: adds the key
	 * when absent, else rewrites every value, or only the values equal to `$prev_value`.
	 * The absent-key check runs before the write with no unique index, so it is not a lock.
	 * The plan is not looked up: meta for an unknown plan id is a caller error.
	 *
	 * @param int    $plan_id    Plan id.
	 * @param string $key        Meta key.
	 * @param mixed  $value      New value; serialized when not scalar.
	 * @param mixed  $prev_value Only update values equal to this; null updates all. Any other
	 *                           value ('' and false included) matches literally.
	 * @return bool True when a value was added or changed; false when nothing changed.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function update_meta( int $plan_id, string $key, $value, $prev_value = null ): bool {
		return ( new PlanRepository() )->update_meta( $plan_id, $key, $value, $prev_value );
	}

	/**
	 * Delete a plan's meta values for `$key`, like `delete_post_meta()`.
	 *
	 * @param int    $plan_id Plan id.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Only delete values equal to this; null deletes every value for the key.
	 *                        Any other value ('' and false included) matches literally.
	 * @return bool True when at least one value was deleted.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function delete_meta( int $plan_id, string $key, $value = null ): bool {
		return ( new PlanRepository() )->delete_meta( $plan_id, $key, $value );
	}

	/**
	 * Read plan meta (WordPress `get_post_meta()` semantics), oldest value first.
	 *
	 * @param int    $plan_id Plan id.
	 * @param string $key     Meta key; empty for every key.
	 * @param bool   $single  With a key: return the first value only.
	 * @return mixed Empty key: values grouped by key. Key + `$single`: the first value, or ''
	 *               when absent. Key only: the list of values (`[]` when absent).
	 */
	public static function get_meta( int $plan_id, string $key = '', bool $single = false ) {
		return ( new PlanRepository() )->get_meta( $plan_id, $key, $single );
	}

	/**
	 * Fetch a plan by id, in any status.
	 *
	 * @param int $plan_id Plan id.
	 * @return PlanView|null The plan, or null when none exists.
	 */
	public static function get( int $plan_id ): ?PlanView {
		$plan = ( new PlanRepository() )->find( $plan_id );

		return null === $plan ? null : PlanView::from_plan( $plan );
	}

	/**
	 * List plans, oldest id first. Without args: every extension's plans in every status.
	 *
	 * Archived plans are never purged and count toward `limit`, so pass `status` (for
	 * example `active`) when reading a catalog to sell from. An empty list filter matches
	 * nothing. Unknown keys raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * @param array<string, mixed> $args {
	 *     Optional. Query args.
	 *
	 *     @type string|string[] $extension_slug Owning extension slug, or a list of them (duplicates are
	 *                                           ignored; `any` is refused). Absent: every extension.
	 *     @type string|string[] $status         Plan status, or a list of them. Absent: every status.
	 *     @type int[]           $ids            Only these plan ids: a list of positive integers (digit
	 *                                           strings are cast). A non-list or a non-positive id throws.
	 *     @type int             $limit          Maximum plans to return, a positive integer. Default 200.
	 *     @type int             $offset         Plans to skip (for paging), a non-negative integer. Default 0.
	 * }
	 * @return array<int, PlanView>
	 * @throws InvalidArgumentException If a value is invalid: an empty or non-string slug or status,
	 *                                  `any` as a slug, a non-positive or non-integer id, limit or offset.
	 */
	public static function list( array $args = array() ): array {
		$filtered_args = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::LIST_KEYS );

		$query = array(
			'orderby' => 'id',
			'order'   => 'asc',
			'limit'   => ArgumentValidator::validate_nullable_id( 'limit', $filtered_args['limit'] ?? null ) ?? self::DEFAULT_LIST_LIMIT,
			'offset'  => ArgumentValidator::validate_non_negative_int( 'offset', $filtered_args['offset'] ?? 0 ),
		);
		if ( array_key_exists( 'extension_slug', $filtered_args ) ) {
			$extension_slugs = array_values( array_unique( ArgumentValidator::validate_string_list( 'extension_slug', $filtered_args['extension_slug'] ) ) );
			if ( in_array( 'any', $extension_slugs, true ) ) {
				throw new InvalidArgumentException( '"extension_slug" must not be "any": leave it out to list every extension\'s plans.' );
			}
			$query['extension_slugs'] = $extension_slugs;
		}
		if ( array_key_exists( 'status', $filtered_args ) ) {
			$query['status'] = ArgumentValidator::validate_string_list( 'status', $filtered_args['status'] );
		}
		if ( array_key_exists( 'ids', $filtered_args ) ) {
			$query['ids'] = ArgumentValidator::validate_id_list( 'ids', $filtered_args['ids'] );
		}

		return array_map(
			static function ( Plan $plan ): PlanView {
				return PlanView::from_plan( $plan );
			},
			( new PlanRepository() )->query( $query )
		);
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- the DomainException comes from the entity setters, not a throw in this method.
	/**
	 * Validate the caller's field shapes and apply them to a plan through its setters,
	 * which enforce the entity invariants. Nothing is written to storage; an invalid value
	 * throws before any write.
	 *
	 * @param Plan                 $plan Plan to change.
	 * @param array<string, mixed> $args Caller fields (known keys only, no `extension_slug`).
	 * @throws InvalidArgumentException If a value has the wrong shape.
	 * @throws DomainException If a value breaks an entity invariant (from the entity setters).
	 */
	private static function apply( Plan $plan, array $args ): void {
		foreach ( $args as $key => $value ) {
			switch ( $key ) {
				case 'name':
					$plan->set_name( trim( ArgumentValidator::validate_string( $key, $value ) ) );
					break;
				case 'status':
					$plan->set_status( ArgumentValidator::validate_string( $key, $value ) );
					break;
				case 'billing_policy':
					$plan->set_billing_policy( ArgumentValidator::validate_nullable_array( $key, $value ) );
					break;
				case 'pricing_policy':
					$plan->set_pricing_policy( ArgumentValidator::validate_nullable_array( $key, $value ) );
					break;
				case 'delivery_policy':
					$plan->set_delivery_policy( ArgumentValidator::validate_nullable_array( $key, $value ) );
					break;
			}
		}
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Let the plan's owner validate the would-be plan before it is written.
	 *
	 * @param Plan $plan The would-be plan (unsaved on create).
	 * @throws PlanValidationException If a callback added errors.
	 * @throws RuntimeException If a callback threw.
	 */
	private static function validate_with_owner( Plan $plan ): void {
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
}
