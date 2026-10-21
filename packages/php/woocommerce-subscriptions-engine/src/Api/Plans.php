<?php
/**
 * Plans - the engine's public plan facade (reads and writes).
 *
 * Extensions create, update and read plans through explicit argument arrays: the engine records
 * the payloads it is given and interprets none of them. It checks integrity only (a
 * non-empty name, a registered status, object-shaped policies), then lets the plan's
 * owning extension validate the write through `woocommerce_subscriptions_engine_validate_plan`.
 * Any caller may read any plan; an update names the plan's owning extension and never
 * reaches a plan of another one (authorization is the caller's concern). Reads return
 * read-only {@see PlanView}s. The engine opens no transaction and keeps no cache.
 *
 * Plan policies are stored as given; the engine does not read them. An extension may
 * parse a `billing_policy` with {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy::from_array()}.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentFilter;

defined( 'ABSPATH' ) || exit;

/**
 * Public plan facade: reads and writes.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 *
 * @phpstan-type PlanFields array{name?: string, status?: string, billing_policy?: array<int|string, mixed>|null, pricing_policy?: array<int|string, mixed>|null, delivery_policy?: array<int|string, mixed>|null}
 */
final class Plans {

	/**
	 * Keys accepted by {@see self::create()} and {@see self::update()}, as a key map. The
	 * `extension_slug` sets the owning extension on create and scopes the write on update; it is never
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

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- create() and update() also throw ValidationException and RuntimeException indirectly, through validate_with_extension() and the repository.
	/**
	 * Create a plan.
	 *
	 * Unknown keys raise a `_doing_it_wrong()` notice and are ignored. Every invalid value is
	 * reported at once: ill-typed arguments refuse the call before the plan is built; when
	 * the arguments are well-typed, the plan's own refusals and the owning extension's
	 * (`woocommerce_subscriptions_engine_validate_plan`) arrive together.
	 *
	 * @param array<string, mixed> $args Plan fields: `extension_slug` (required, the owning
	 *                                   extension), `name` (required, non-empty), `status` (a
	 *                                   registered plan status, default `active`), and
	 *                                   `billing_policy`, `pricing_policy`, `delivery_policy`
	 *                                   (string-keyed arrays the owning extension interprets, or null).
	 * @return PlanView The new plan, built from the written fields (no re-read).
	 * @throws ValidationException If a required key is missing or a value is invalid (each error
	 *                             with the engine code, `field` and `params`), or the owning extension
	 *                             refuses the plan; entity and extension refusals arrive in one exception.
	 * @throws RuntimeException If a validation callback throws (the callback's throwable is chained
	 *                          as the previous exception) or the insert fails (no previous exception).
	 */
	public static function create( array $args ): PlanView {
		$filtered_args = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::PLAN_KEYS );

		$validator      = new ArgumentValidator();
		$extension_slug = $validator->get_nullable_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		$fields         = self::get_plan_fields( $validator, $filtered_args + array( 'name' => '' ) );
		try {
			$validator->throw_errors();
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		$fields['extension_slug'] = $extension_slug;
		$refusal                  = null;
		try {
			$plan = Plan::create( $fields );
		} catch ( InvalidInputException $e ) {
			$refusal = $e;
			$plan    = Plan::draft( $fields );
		}

		self::validate_with_extension( $plan, $refusal );

		( new PlanRepository() )->insert( $plan );

		return PlanView::from_plan( $plan );
	}

	/**
	 * Write the given fields to an existing plan of the given extension.
	 *
	 * Takes the keys of {@see self::create()}. `extension_slug` is required, as on create,
	 * but here it scopes the write to that extension and is never written (a plan's extension never changes):
	 * the plan is read and written only where its row carries that slug, so a plan of
	 * another extension reads as missing and is never written. Only the columns of the
	 * present plan fields are written, so fields a concurrent writer changed in between
	 * keep its values. A present policy key replaces the whole payload (null clears it);
	 * policies are never merged. Re-sending the stored status is accepted even when that
	 * status is no longer registered (its extension was deactivated). The arguments are
	 * checked before the plan is read, so ill-typed arguments throw even for a missing plan.
	 * Args with no plan field return the stored plan without validating or writing. Unknown
	 * keys raise a `_doing_it_wrong()` notice and are ignored. The engine opens no
	 * transaction: wrap the call in one when it must be atomic with other writes.
	 *
	 * @param int                  $plan_id Plan id.
	 * @param array<string, mixed> $args    `extension_slug` (required, the owning extension)
	 *                                      and the fields to write.
	 * @return PlanView|null The row as read before the write plus the written fields (a column
	 *                       another writer changed meanwhile may be stale here, not in storage);
	 *                       null when no plan of that extension has the id (also when it is
	 *                       deleted before the write).
	 * @throws ValidationException If `extension_slug` is missing or a value is invalid (each error
	 *                             with the engine code, `field` and `params`), or the owning extension
	 *                             refuses the plan; entity and extension refusals arrive in one exception.
	 * @throws RuntimeException If a validation callback throws (the callback's throwable is chained
	 *                          as the previous exception) or the update fails (no previous exception).
	 */
	public static function update( int $plan_id, array $args ): ?PlanView {
		$filtered_args = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::PLAN_KEYS );

		$validator      = new ArgumentValidator();
		$extension_slug = $validator->get_non_empty_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		$changes        = self::get_plan_fields( $validator, $filtered_args );
		try {
			$validator->throw_errors();
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		$repository = new PlanRepository();
		$plan       = $repository->find( $plan_id, (string) $extension_slug );
		if ( null === $plan ) {
			return null;
		}

		if ( array() === $changes ) {
			return PlanView::from_plan( $plan );
		}

		$refusal = null;
		try {
			self::apply( $plan, $changes );
		} catch ( InvalidInputException $e ) {
			$refusal = $e;
			$plan    = self::get_would_be_plan( $plan, $changes );
		}

		self::validate_with_extension( $plan, $refusal );

		$updated = $repository->update_fields( $plan, array_keys( $changes ) );
		if ( ! $updated ) {
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
	 * @throws ValidationException If a value is invalid, with every invalid value reported: an empty
	 *                             or non-string slug or status, `any` as a slug, a non-positive or
	 *                             non-integer id or limit, a negative or non-integer offset.
	 */
	public static function list( array $args = array() ): array {
		$filtered_args = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::LIST_KEYS );

		$validator = new ArgumentValidator();
		$query     = array(
			'orderby' => 'id',
			'order'   => 'asc',
			'limit'   => $validator->get_nullable_id( 'limit', $filtered_args['limit'] ?? null ) ?? self::DEFAULT_LIST_LIMIT,
			'offset'  => $validator->get_non_negative_int( 'offset', $filtered_args['offset'] ?? 0 ),
		);
		if ( array_key_exists( 'extension_slug', $filtered_args ) ) {
			$extension_slugs = array_values( array_unique( (array) $validator->get_string_list( 'extension_slug', $filtered_args['extension_slug'] ) ) );
			if ( in_array( 'any', $extension_slugs, true ) ) {
				$validator->add(
					new InvalidInputException(
						ValidationErrorCode::INVALID_VALUE,
						'extension_slug',
						'"extension_slug" must not be "any": leave it out to list every extension\'s plans.'
					)
				);
			}
			$query['extension_slugs'] = $extension_slugs;
		}
		if ( array_key_exists( 'status', $filtered_args ) ) {
			$query['status'] = $validator->get_string_list( 'status', $filtered_args['status'] );
		}
		if ( array_key_exists( 'ids', $filtered_args ) ) {
			$query['ids'] = $validator->get_id_list( 'ids', $filtered_args['ids'] );
		}
		try {
			$validator->throw_errors();
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		return array_map(
			static function ( Plan $plan ): PlanView {
				return PlanView::from_plan( $plan );
			},
			( new PlanRepository() )->query( $query )
		);
	}

	/**
	 * Check the shape of the present plan fields with the collector and return them
	 * normalized (a refused field is left out; the collector holds its error).
	 *
	 * @param ArgumentValidator    $validator Argument collector.
	 * @param array<string, mixed> $args      Caller fields (known keys only).
	 * @return array<string, mixed>
	 * @phpstan-return PlanFields
	 */
	private static function get_plan_fields( ArgumentValidator $validator, array $args ): array {
		$fields = array();
		if ( array_key_exists( 'name', $args ) ) {
			$name = $validator->get_string( 'name', $args['name'] );
			if ( null !== $name ) {
				$fields['name'] = trim( $name );
			}
		}
		if ( array_key_exists( 'status', $args ) ) {
			$status = $validator->get_string( 'status', $args['status'] );
			if ( null !== $status ) {
				$fields['status'] = $status;
			}
		}
		foreach ( array( 'billing_policy', 'pricing_policy', 'delivery_policy' ) as $key ) {
			if ( array_key_exists( $key, $args ) ) {
				$fields[ $key ] = $validator->get_nullable_array( $key, $args[ $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Apply normalized changes to a plan through its setters, which enforce the entity
	 * invariants. Every setter runs; their refusals are thrown together. Nothing is written
	 * to storage.
	 *
	 * @param Plan                 $plan    Plan to change.
	 * @param array<string, mixed> $changes Normalized fields from {@see self::get_plan_fields()}.
	 * @phpstan-param PlanFields $changes
	 * @throws InvalidInputException If a value breaks an entity invariant.
	 */
	private static function apply( Plan $plan, array $changes ): void {
		$errors = array();
		if ( array_key_exists( 'name', $changes ) ) {
			try {
				$plan->set_name( $changes['name'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}
		if ( array_key_exists( 'status', $changes ) ) {
			try {
				$plan->set_status( $changes['status'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}
		if ( array_key_exists( 'billing_policy', $changes ) ) {
			try {
				$plan->set_billing_policy( $changes['billing_policy'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}
		if ( array_key_exists( 'pricing_policy', $changes ) ) {
			try {
				$plan->set_pricing_policy( $changes['pricing_policy'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}
		if ( array_key_exists( 'delivery_policy', $changes ) ) {
			try {
				$plan->set_delivery_policy( $changes['delivery_policy'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}

		if ( array() !== $errors ) {
			throw InvalidInputException::from_errors( $errors ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The caller converts it with ValidationException::from_core(), which escapes the message.
		}
	}

	/**
	 * The would-be plan of a refused update: the stored plan with the attempted changes,
	 * refused values included, for the validation callbacks. Never stored.
	 *
	 * @param Plan                 $plan    Stored plan.
	 * @param array<string, mixed> $changes Normalized fields from {@see self::get_plan_fields()}.
	 */
	private static function get_would_be_plan( Plan $plan, array $changes ): Plan {
		$stored = array(
			'id'               => $plan->get_id(),
			'date_created_gmt' => $plan->get_date_created_gmt(),
			'date_updated_gmt' => $plan->get_date_updated_gmt(),
		) + $plan->to_storage();

		return Plan::draft( array_merge( $stored, $changes ) );
	}

	/**
	 * Let the plan's extension validate the would-be plan before it is written, also when the
	 * plan itself was refused, and report both refusals together: the plan's errors first.
	 *
	 * @param Plan                       $plan    The would-be plan (unsaved on create; unchecked when refused).
	 * @param InvalidInputException|null $refusal The plan's own refusal, or null.
	 * @throws ValidationException If the plan was refused or a callback added errors.
	 * @throws RuntimeException If a callback threw, with the callback's throwable as the previous
	 *                          exception. The REST controller relies on that to tell it from a failed write.
	 */
	private static function validate_with_extension( Plan $plan, ?InvalidInputException $refusal ): void {
		$errors         = new WP_Error();
		$extension_slug = (string) $plan->get_extension_slug();

		try {
			/**
			 * Fires before every plan write (PHP facade or REST), on create and on update,
			 * including status-only updates, so the plan's extension can refuse it.
			 *
			 * Add errors to $errors to refuse the write; act only on your own $extension_slug.
			 * Give an error `field` data (dot path, such as `billing_policy.interval`, with
			 * optional `params`) and REST reports it under that top-level arg in `rest_invalid_param`,
			 * with any `status` of its own folded into the 400. An error without `field` is
			 * reported as a general 400; one with the code `rest_invalid_param` is reported
			 * without its data.
			 * The view is the would-be state after the write (its id is 0 on create) and is
			 * read-only. It also fires when the engine refuses the plan itself (an empty name,
			 * an unregistered status): validate your own payloads and do not rely on `name`,
			 * `status` or `extension_slug` being valid. The engine's errors and yours are
			 * reported together. A callback that throws fails the write as a server error.
			 *
			 * Changed with plans as records: `$plan` is a read-only {@see PlanView}. Earlier
			 * engine versions passed the Core `Plan` entity; a callback still typed on `Plan`
			 * throws here, which refuses every plan write, so update such callbacks together
			 * with this engine version.
			 *
			 * @param WP_Error $errors Error collector.
			 * @param PlanView $plan   The would-be plan.
			 * @param string   $extension_slug Owning extension slug.
			 */
			do_action( 'woocommerce_subscriptions_engine_validate_plan', $errors, PlanView::from_plan( $plan ), $extension_slug );
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Plans: plan validation for extension "%s" (plan %s) threw: %s', $extension_slug, null === $plan->get_id() ? 'new' : (string) $plan->get_id(), $e->getMessage() ),
				array(
					'source'         => self::LOG_SOURCE,
					'extension_slug' => $extension_slug,
					'plan_id'        => $plan->get_id(),
				)
			);

			throw new RuntimeException( 'Plans: the plan could not be validated.', 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Literal message.
		}

		if ( null === $refusal ) {
			if ( $errors->has_errors() ) {
				throw new ValidationException( $errors ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The exception escapes its message.
			}
			return;
		}

		$engine_errors = ValidationException::from_core( $refusal );
		$all_errors    = $engine_errors->get_errors();
		$all_errors->merge_from( $errors );
		$messages = array_merge( array( $engine_errors->getMessage() ), array_map( 'esc_html', $errors->get_error_messages() ) );

		throw new ValidationException( $all_errors, implode( ' ', $messages ), $refusal ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Both parts are escaped.
	}
}
