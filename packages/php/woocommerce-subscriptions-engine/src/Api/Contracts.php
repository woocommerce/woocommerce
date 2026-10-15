<?php
/**
 * Contracts - the engine's public contract facade (reads and writes).
 *
 * Extensions create and progressively build contracts from explicit argument arrays:
 * the engine records the facts it is given and decides nothing about them. Any caller may
 * read or write any contract (authorization is the caller's concern). Reads return read-only
 * views ({@see ContractView}, {@see CycleView}). No transactions, no cache, no hooks.
 *
 * One lock per contract ({@see self::lock()}): a write that passes the lock is part of the
 * holder's operation; a write without one is a self-contained operation the engine guards
 * itself and refuses with {@see ContractLockedException} while another operation holds the lock.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use DomainException;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\CycleView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PaymentInstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DuplicateCycleException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Public contract facade: reads and writes.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Contracts {

	/**
	 * Keys accepted by {@see self::update()}, as a key map.
	 *
	 * @var array<string, true>
	 */
	private const CONTRACT_KEYS = array(
		'customer_id'          => true,
		'currency'             => true,
		'selling_plan_id'      => true,
		'origin_order_id'      => true,
		'status'               => true,
		'payment_method'       => true,
		'payment_method_title' => true,
		'payment_token_id'     => true,
		'start_gmt'            => true,
		'next_payment_gmt'     => true,
		'last_payment_gmt'     => true,
		'last_attempt_gmt'     => true,
		'trial_end_gmt'        => true,
		'end_gmt'              => true,
		'schedule_source'      => true,
		'billing_total'        => true,
		'discount_total'       => true,
		'shipping_total'       => true,
		'tax_total'            => true,
		'items'                => true,
		'addresses'            => true,
	);

	/**
	 * Keys accepted by {@see self::create()}: the update keys plus the create-only `extension_slug`.
	 *
	 * @var array<string, true>
	 */
	private const CREATE_KEYS = array(
		'extension_slug' => true,
	) + self::CONTRACT_KEYS;

	/**
	 * Keys accepted by {@see self::create_locked()}: the create keys plus `creation_key`.
	 *
	 * @var array<string, true>
	 */
	private const CREATE_LOCKED_KEYS = array(
		'creation_key' => true,
	) + self::CREATE_KEYS;

	/**
	 * Keys accepted by {@see self::add_cycle()}, as a key map.
	 *
	 * @var array<string, true>
	 */
	private const CYCLE_KEYS = array(
		'status'         => true,
		'kind'           => true,
		'sequence_no'    => true,
		'count'          => true,
		'starts_at_gmt'  => true,
		'ends_at_gmt'    => true,
		'expected_total' => true,
		'currency'       => true,
		'order_id'       => true,
	);

	/**
	 * Keys accepted by {@see self::transition_cycle()}, as a key map.
	 *
	 * @var array<string, true>
	 */
	private const TRANSITION_KEYS = array(
		'from_status' => true,
		'to_status'   => true,
		'order_id'    => true,
		'reason'      => true,
	);

	/**
	 * Take the contract's lock for `$operation`, for the synchronous section of this request.
	 * Pass the lock to every write of the operation and release it when done; an unreleased
	 * lock expires after {@see ContractLock::TTL_SECONDS}. An expired lock is taken over.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $operation   Operation label (1-64 characters), shown to callers that conflict.
	 * @return ContractLock|null The held lock; null when another operation holds it or the contract does not exist.
	 * @throws InvalidArgumentException If the label is invalid.
	 */
	public static function lock( int $contract_id, string $operation ): ?ContractLock {
		return ( new ContractRepository() )->acquire_lock( $contract_id, $operation );
	}

	/**
	 * Create a contract from explicit fields.
	 *
	 * Only `extension_slug` is required; the status defaults to `draft`. Dates accept a
	 * `DateTimeInterface` or a GMT `Y-m-d H:i:s` string; money accepts numbers or numeric
	 * strings, and a non-null money value requires a currency. `items` is a list of item
	 * rows; `addresses` is keyed `billing` / `shipping`. `schedule_source` says who keeps the
	 * payment schedule: `subscriptions_engine` (the default; the engine notifies the owner when
	 * `next_payment_gmt` passes) or the slug (1-64 of `a-z0-9_-`) of a gateway or extension that
	 * keeps its own schedule; the engine never dispatches those contracts. Unknown keys, also
	 * inside item rows and addresses, raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * @param array<string, mixed> $args Contract fields: `extension_slug` (required, the owning
	 *                                   extension), `status` (a registered contract status,
	 *                                   default `draft`), and any of {@see self::CONTRACT_KEYS}.
	 * @return ContractView The new contract, built from the written fields (no re-read): items
	 *                      and addresses as given.
	 * @throws InvalidArgumentException If `extension_slug` is missing or a value is invalid.
	 */
	public static function create( array $args ): ContractView {
		$contract = self::build( __METHOD__, $args, self::CREATE_KEYS );

		( new ContractRepository() )->insert( $contract );

		return ContractView::from_contract( $contract, true );
	}

	/**
	 * Create a contract locked from birth, so an extension can finish building it (cycles,
	 * status) before anyone else writes to it. Takes the keys of {@see self::create()} plus
	 * `creation_key` (1-100 characters), which makes the create idempotent per extension.
	 *
	 * @param array<string, mixed> $args      Contract fields, as for {@see self::create()}, plus `creation_key`.
	 * @param string               $operation Operation label (1-64 characters).
	 * @return array{contract: ContractView, lock: ContractLock}|null The new contract and its lock;
	 *                                                                null when the extension already created a contract with this `creation_key`.
	 * @throws InvalidArgumentException If `extension_slug` is missing, a value or the label is invalid.
	 */
	public static function create_locked( array $args, string $operation ): ?array {
		$contract = self::build( __METHOD__, $args, self::CREATE_LOCKED_KEYS );

		$lock = ( new ContractRepository() )->insert_locked( $contract, $operation );
		if ( null === $lock ) {
			return null;
		}

		return array(
			'contract' => ContractView::from_contract( $contract, true ),
			'lock'     => $lock,
		);
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Write the given fields to an existing contract.
	 *
	 * Takes the keys of {@see self::create()} except `extension_slug`. Only
	 * the columns of the present keys are written, so fields another writer changed in between
	 * keep its values; `items` and `addresses` replace the whole set; `null` clears a nullable
	 * field (and resets a money field to 0). Unknown keys raise a `_doing_it_wrong()` notice and
	 * are ignored. Without `$lock`, a write that replaces items or addresses runs under a lock
	 * the engine takes and releases itself.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $args        Fields to write.
	 * @param ContractLock|null    $lock        The caller's lock on this contract, or null.
	 * @return ContractView|null The row as read before the write plus the written fields; null
	 *                           when the contract does not exist (also when it is deleted before the write).
	 * @throws InvalidArgumentException If a value is invalid, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function update( int $contract_id, array $args, ?ContractLock $lock = null ): ?ContractView {
		if ( null !== $lock ) {
			ContractRepository::assert_lock_for( $lock, $contract_id );
		}

		$filtered_args = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::CONTRACT_KEYS );

		$repository = new ContractRepository();
		$contract   = $repository->find( $contract_id );
		if ( null === $contract ) {
			return null;
		}

		try {
			self::apply( $contract, $filtered_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		$fields = array_keys( $filtered_args );
		if ( array() === $fields ) {
			return ContractView::from_contract( $contract, true );
		}

		if ( ! $repository->update_fields( $contract, $fields, $lock ) ) {
			return null;
		}

		return ContractView::from_contract( $contract, true );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Append a cycle to a contract's chain `(contract_id, kind)`.
	 *
	 * The first public form of the cycle append tool: append-if-absent on the chain's
	 * unique positions. `starts_at_gmt`, `ends_at_gmt` and `currency` are required.
	 * `status` (a registered cycle status) defaults to `pending`; `kind` defaults to
	 * `billing`; an absent or null `sequence_no` is assigned on append as the head's plus one;
	 * `count` is the caller's chargeable number (absent or null for a non-counting cycle;
	 * unique within the chain); `expected_total` defaults to 0; `order_id` is optional.
	 * Unknown keys raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * Losing the append race is an outcome, not an error: when the chain position or count is
	 * already taken, nothing is written and the call returns null, as {@see self::transition_cycle()}
	 * returns false when its compare-and-set loses. A storage failure, such as a value longer
	 * than its column (`kind`), throws a `RuntimeException` and writes nothing. When the row landed
	 * but its assigned position cannot be read back, the view's `sequence_no` is 0 (logged).
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $args        Cycle fields.
	 * @param ContractLock|null    $lock        The caller's lock on this contract, or null.
	 * @return CycleView|null The appended cycle, or null when the position or count is already taken.
	 * @throws InvalidArgumentException If a required key is missing, a value is invalid, the contract
	 *                                  does not exist, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function add_cycle( int $contract_id, array $args, ?ContractLock $lock = null ): ?CycleView {
		$filtered_args = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::CYCLE_KEYS );

		$cycle_args = array(
			'contract_id'    => $contract_id,
			'kind'           => $filtered_args['kind'] ?? null,
			'sequence_no'    => ArgumentValidator::validate_nullable_id( 'sequence_no', $filtered_args['sequence_no'] ?? null ),
			'count'          => ArgumentValidator::validate_nullable_id( 'count', $filtered_args['count'] ?? null ),
			'status'         => $filtered_args['status'] ?? null,
			'starts_at_gmt'  => ArgumentValidator::validate_nullable_date( 'starts_at_gmt', $filtered_args['starts_at_gmt'] ?? null ),
			'ends_at_gmt'    => ArgumentValidator::validate_nullable_date( 'ends_at_gmt', $filtered_args['ends_at_gmt'] ?? null ),
			'expected_total' => ArgumentValidator::validate_money( 'expected_total', $filtered_args['expected_total'] ?? null ),
			'currency'       => ArgumentValidator::validate_currency( $filtered_args['currency'] ?? null ),
			'order_id'       => ArgumentValidator::validate_nullable_id( 'order_id', $filtered_args['order_id'] ?? null ),
		);

		try {
			$cycle = Cycle::create( $cycle_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		try {
			( new ContractRepository() )->append_cycle( $cycle, $lock );
		} catch ( DuplicateCycleException $e ) {
			return null;
		}

		return CycleView::from_cycle( $cycle );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Move a cycle between statuses as a compare-and-set: of concurrent callers that read
	 * `from_status`, exactly one gets true. `$args`: `from_status` (required, the status the
	 * caller read), `to_status` (required, a registered cycle status), and `order_id` (?int) and
	 * `reason` (?string), written when present (null clears). Unknown keys raise a
	 * `_doing_it_wrong()` notice and are ignored. A same-status call returns true once the cycle
	 * carries the status and the values.
	 *
	 * @param int                  $cycle_id Cycle id.
	 * @param array<string, mixed> $args     `from_status`, `to_status`, optional `order_id` and `reason`.
	 * @param ContractLock|null    $lock     The caller's lock on the cycle's contract, or null.
	 * @return bool True when this call made the transition, or for a same-status call when the
	 *              cycle carries the status and the values.
	 * @throws InvalidArgumentException If a status is missing or unregistered, a value is invalid,
	 *                                  the cycle does not exist, or it belongs to another contract than the lock.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function transition_cycle( int $cycle_id, array $args, ?ContractLock $lock = null ): bool {
		$filtered_args = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::TRANSITION_KEYS );
		$from_status   = ArgumentValidator::validate_non_empty_string( 'from_status', $filtered_args['from_status'] ?? null );
		$to_status     = ArgumentValidator::validate_non_empty_string( 'to_status', $filtered_args['to_status'] ?? null );
		$changes       = array();

		if ( array_key_exists( 'order_id', $filtered_args ) ) {
			$changes['order_id'] = ArgumentValidator::validate_nullable_id( 'order_id', $filtered_args['order_id'] );
		}
		if ( array_key_exists( 'reason', $filtered_args ) ) {
			$changes['reason'] = ArgumentValidator::validate_nullable_string( 'reason', $filtered_args['reason'] );
		}

		try {
			return ( new ContractRepository() )->transition_cycle( $cycle_id, $from_status, $to_status, $changes, $lock );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the repository message is not output.
		}
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * The cycles linked to an order, newest first, across contracts and chain kinds (one
	 * order may serve several cycles).
	 *
	 * @param int $order_id Order id.
	 * @return array<int, CycleView>
	 */
	public static function find_cycles_by_order( int $order_id ): array {
		return self::cycle_views( ( new ContractRepository() )->find_cycles_by_order_id( $order_id ) );
	}

	/**
	 * Add a meta value to a contract, like `add_post_meta()`. A key may hold several values.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       Meta value; serialized when not scalar.
	 * @param bool              $unique      When true, add nothing if the key already exists (checked
	 *                                       before the insert; the lock serializes facade writers).
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null.
	 * @return int|null The meta row id; null when `$unique` and the key exists.
	 * @throws InvalidArgumentException If `$key` is empty, the contract does not exist, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function add_meta( int $contract_id, string $key, $value, bool $unique = false, ?ContractLock $lock = null ): ?int {
		return ( new ContractRepository() )->add_meta( $contract_id, $key, $value, $unique, $lock );
	}

	/**
	 * Update a contract's meta values for `$key`, like `update_post_meta()`: adds the key
	 * when absent, else rewrites every value, or only the values equal to `$prev_value`.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       New value; serialized when not scalar.
	 * @param mixed             $prev_value  Only update values equal to this; null updates all. Any other
	 *                                       value ('' and false included) matches literally.
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null.
	 * @return bool True when a value was added or changed; false when nothing changed.
	 * @throws InvalidArgumentException If `$key` is empty, the key is added to a missing contract, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function update_meta( int $contract_id, string $key, $value, $prev_value = null, ?ContractLock $lock = null ): bool {
		return ( new ContractRepository() )->update_meta( $contract_id, $key, $value, $prev_value, $lock );
	}

	/**
	 * Delete a contract's meta values for `$key`, like `delete_post_meta()`.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       Only delete values equal to this; null deletes every value for the key.
	 *                                       Any other value ('' and false included) matches literally.
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null.
	 * @return bool True when at least one value was deleted.
	 * @throws InvalidArgumentException If `$key` is empty, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function delete_meta( int $contract_id, string $key, $value = null, ?ContractLock $lock = null ): bool {
		return ( new ContractRepository() )->delete_meta( $contract_id, $key, $value, $lock );
	}

	/**
	 * Read contract meta (WordPress `get_post_meta()` semantics), oldest value first.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $key         Meta key; empty for every key.
	 * @param bool   $single      With a key: return the first value only.
	 * @return mixed Empty key: values grouped by key. Key + `$single`: the first value, or ''
	 *               when absent. Key only: the list of values (`[]` when absent).
	 */
	public static function get_meta( int $contract_id, string $key = '', bool $single = false ) {
		return ( new ContractRepository() )->get_meta( $contract_id, $key, $single );
	}

	/**
	 * Fetch a contract by id, with its items and addresses.
	 *
	 * @param int $contract_id Contract id.
	 * @return ContractView|null The contract, or null when none exists.
	 */
	public static function get( int $contract_id ): ?ContractView {
		return self::view( ( new ContractRepository() )->find( $contract_id ), true );
	}

	/**
	 * The contracts created from an origin order, oldest first (children not loaded).
	 *
	 * @param int $order_id Origin order id.
	 * @return array<int, ContractView>
	 */
	public static function find_by_origin_order( int $order_id ): array {
		return self::views( ( new ContractRepository() )->find_by_origin_order( $order_id ) );
	}

	/**
	 * List contracts for an admin list screen - newest first by default, or
	 * filtered / sorted / paged / searched via a WooCommerce-style args array (cf.
	 * `wc_get_orders()`). The status + search filter matches {@see self::count()}, so a page
	 * and its total describe the same set.
	 *
	 * @param array<string, mixed> $args {
	 *     Optional. Query args.
	 *
	 *     @type int    $limit   Maximum contracts to return. Default 20.
	 *     @type int    $offset  Contracts to skip (for paging). Default 0.
	 *     @type string $status  Filter to one status ({@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus}); ignored when empty or invalid.
	 *     @type string $orderby One of id, next_payment, total, start; default id.
	 *     @type string $order   ASC or DESC (case-insensitive); default DESC.
	 *     @type string $search  Numeric term matches contract id or origin order id; text term matches the owning customer.
	 * }
	 * @return array<int, ContractView> Contracts in the requested order (children not loaded).
	 */
	public static function list( array $args = array() ): array {
		return self::views( ( new ContractRepository() )->query( $args ) );
	}

	/**
	 * The contract count per status - the read behind an admin list's status views bar.
	 * Keyed by every {@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus} value (absent statuses are 0); the `All` total
	 * is the caller's `array_sum()`. Independent of any search or paging.
	 *
	 * @return array<string, int> Status => count, every known status present.
	 */
	public static function count_by_status(): array {
		return ( new ContractRepository() )->count_by_status();
	}

	/**
	 * The number of contracts matching a list filter - the total behind a list view's
	 * pagination. Honours the SAME status + search args as {@see self::list()} and ignores
	 * paging / sort.
	 *
	 * @param array<string, mixed> $args Query args (only `status` and `search` are read).
	 * @return int The matching contract count.
	 */
	public static function count( array $args = array() ): int {
		return ( new ContractRepository() )->count( $args );
	}

	/**
	 * The line-item count for a page of contracts - the read behind an admin list's
	 * "Items" column. One grouped scan over the given ids, returned as a map keyed by
	 * every requested id (ids with no items are 0), so a list renders an items count
	 * per row without a per-row query. Ids are de-duplicated and int-cast.
	 *
	 * @param array<int, int> $contract_ids Contract ids to count items for.
	 * @return array<int, int> Contract id => line-item count, one entry per requested id.
	 */
	public static function item_counts( array $contract_ids ): array {
		return ( new ContractRepository() )->count_items_by_contract( $contract_ids );
	}

	/**
	 * List a single customer's contracts, newest first - the customer
	 * portal's owner-scoped list read.
	 *
	 * Owner-scoped by construction: the customer id is supplied by the caller (the
	 * authenticated user at the REST boundary), never inferred, so it never returns
	 * another customer's contracts. Each view projects the stored contract fields (items
	 * and addresses not loaded); a caller needing plan terms resolves `selling_plan_id`
	 * through {@see Plans::get()}.
	 *
	 * The status filter applies before paging, so a page holds `$limit` matching contracts.
	 *
	 * @param int                  $customer_id Owning customer id.
	 * @param int                  $limit       Maximum contracts to return.
	 * @param int                  $offset      Contracts to skip (for paging).
	 * @param array<string, mixed> $args {
	 *     Optional. Query args.
	 *
	 *     @type string|string[] $status One status or a list of them ({@see \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus});
	 *                                   unregistered values are dropped, and the filter is ignored when none remain.
	 * }
	 * @return array<int, ContractView> The customer's contracts, newest first.
	 */
	public static function list_for_customer( int $customer_id, int $limit = 20, int $offset = 0, array $args = array() ): array {
		return self::views(
			( new ContractRepository() )->find_by_customer_id(
				$customer_id,
				array(
					'limit'  => $limit,
					'offset' => $offset,
					'status' => $args['status'] ?? array(),
				)
			)
		);
	}

	/**
	 * Fetch a contract a customer owns - the customer portal's ownership-checked read.
	 *
	 * Returns null for BOTH an unknown id AND a contract owned by another customer (the
	 * asymmetric not-found rule), so a caller cannot probe for the existence of a
	 * contract it does not own.
	 *
	 * The returned view projects the stored contract fields with items and addresses; a
	 * caller needing plan terms resolves `selling_plan_id` through {@see Plans::get()}.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $customer_id Customer that must own the contract.
	 * @return ContractView|null The contract when owned by `$customer_id`, else null.
	 * @phpstan-impure
	 */
	public static function get_for_customer( int $contract_id, int $customer_id ): ?ContractView {
		return self::view( ( new ContractRepository() )->find_for_customer( $contract_id, $customer_id ), true );
	}

	/**
	 * Fetch a window of the contract's billing cycles, newest first. A negative `$limit` or
	 * `$offset` throws an InvalidArgumentException.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $limit       Maximum cycles to return.
	 * @param int $offset      Cycles to skip (for paging).
	 * @return array<int, CycleView> Cycles newest first.
	 */
	public static function get_cycles( int $contract_id, int $limit = 20, int $offset = 0 ): array {
		$limit  = ArgumentValidator::validate_non_negative_int( 'limit', $limit );
		$offset = ArgumentValidator::validate_non_negative_int( 'offset', $offset );

		return self::cycle_views( ( new ContractRepository() )->find_cycle_history( $contract_id, Cycle::KIND_BILLING, $limit, $offset ) );
	}

	/**
	 * Validate create arguments into a new, unsaved contract.
	 *
	 * @param string               $method Calling method, for unknown-key notices.
	 * @param array<string, mixed> $args   Caller fields.
	 * @param array<string, true>  $keys   The keys the calling method accepts.
	 * @throws InvalidArgumentException If `extension_slug` is missing or a value is invalid.
	 */
	private static function build( string $method, array $args, array $keys ): Contract {
		$filtered_args  = ArgumentValidator::filter_known_keys( $method, $args, $keys );
		$extension_slug = ArgumentValidator::validate_nullable_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		$creation_key   = ArgumentValidator::validate_nullable_string( 'creation_key', $filtered_args['creation_key'] ?? null );
		unset( $filtered_args['extension_slug'], $filtered_args['creation_key'] );

		try {
			$contract = Contract::create(
				array(
					'extension_slug' => $extension_slug,
					'creation_key'   => $creation_key,
				)
			);
			self::apply( $contract, $filtered_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		return $contract;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- the DomainException comes from the entity setters, not a throw in this method.
	/**
	 * Validate the caller's field shapes and apply them to a contract through its setters,
	 * which enforce the entity invariants. Nothing is written to storage; an invalid value
	 * throws before any write.
	 *
	 * @param Contract             $contract Contract to change.
	 * @param array<string, mixed> $args     Caller fields (known keys only, no `extension_slug`).
	 * @throws InvalidArgumentException If a value has the wrong shape.
	 * @throws DomainException If a value breaks an entity invariant (from the entity setters).
	 */
	private static function apply( Contract $contract, array $args ): void {
		$instrument = $contract->get_payment_instrument();
		$token_id   = $instrument->get_token_id();
		$gateway    = $instrument->get_gateway();
		$title      = $instrument->get_title();

		foreach ( $args as $key => $value ) {
			switch ( $key ) {
				case 'customer_id':
					$contract->set_customer_id( ArgumentValidator::validate_nullable_id( $key, $value ) );
					break;
				case 'selling_plan_id':
					$contract->set_selling_plan_id( ArgumentValidator::validate_nullable_id( $key, $value ) );
					break;
				case 'origin_order_id':
					$contract->set_origin_order_id( ArgumentValidator::validate_nullable_id( $key, $value ) );
					break;
				case 'currency':
					$contract->set_currency( ArgumentValidator::validate_currency( $value ) );
					break;
				case 'status':
					$contract->set_status( ArgumentValidator::validate_string( $key, $value ) );
					break;
				case 'schedule_source':
					$contract->set_schedule_source( ArgumentValidator::validate_string( $key, $value ) );
					break;
				case 'start_gmt':
					$contract->set_start_gmt( ArgumentValidator::validate_nullable_date( $key, $value ) );
					break;
				case 'next_payment_gmt':
					$contract->set_next_payment_gmt( ArgumentValidator::validate_nullable_date( $key, $value ) );
					break;
				case 'last_payment_gmt':
					$contract->set_last_payment_gmt( ArgumentValidator::validate_nullable_date( $key, $value ) );
					break;
				case 'last_attempt_gmt':
					$contract->set_last_attempt_gmt( ArgumentValidator::validate_nullable_date( $key, $value ) );
					break;
				case 'trial_end_gmt':
					$contract->set_trial_end_gmt( ArgumentValidator::validate_nullable_date( $key, $value ) );
					break;
				case 'end_gmt':
					$contract->set_end_gmt( ArgumentValidator::validate_nullable_date( $key, $value ) );
					break;
				case 'billing_total':
					$contract->set_billing_total( ArgumentValidator::validate_money( $key, $value ) );
					break;
				case 'discount_total':
					$contract->set_discount_total( ArgumentValidator::validate_money( $key, $value ) );
					break;
				case 'shipping_total':
					$contract->set_shipping_total( ArgumentValidator::validate_money( $key, $value ) );
					break;
				case 'tax_total':
					$contract->set_tax_total( ArgumentValidator::validate_money( $key, $value ) );
					break;
				case 'items':
					$contract->set_items( ArgumentValidator::validate_contract_items( $value, self::class ) );
					break;
				case 'addresses':
					$contract->set_addresses( ArgumentValidator::validate_contract_addresses( $value, self::class ) );
					break;
				case 'payment_token_id':
					$token_id = ArgumentValidator::validate_nullable_id( $key, $value );
					break;
				case 'payment_method':
					$gateway = ArgumentValidator::validate_nullable_string( $key, $value );
					break;
				case 'payment_method_title':
					$title = ArgumentValidator::validate_nullable_string( $key, $value );
					break;
			}
		}

		$contract->set_payment_instrument( new PaymentInstrumentRef( $token_id, $gateway, $title ) );
		$contract->assert_money_has_currency();
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * A view of `$contract`, or null.
	 *
	 * @param Contract|null $contract      Contract, or null.
	 * @param bool          $with_children Whether the read loaded items and addresses.
	 */
	private static function view( ?Contract $contract, bool $with_children ): ?ContractView {
		return null === $contract ? null : ContractView::from_contract( $contract, $with_children );
	}

	/**
	 * Views of cycles.
	 *
	 * @param array<int, Cycle> $cycles Cycles.
	 * @return array<int, CycleView>
	 */
	private static function cycle_views( array $cycles ): array {
		return array_map(
			static function ( Cycle $cycle ): CycleView {
				return CycleView::from_cycle( $cycle );
			},
			$cycles
		);
	}

	/**
	 * Views of row-only contracts (children not loaded).
	 *
	 * @param array<int, Contract> $contracts Contracts.
	 * @return array<int, ContractView>
	 */
	private static function views( array $contracts ): array {
		return array_map(
			static function ( Contract $contract ): ContractView {
				return ContractView::from_contract( $contract, false );
			},
			$contracts
		);
	}
}
