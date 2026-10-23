<?php
/**
 * Contracts - the engine's public contract facade (reads and writes).
 *
 * Extensions create and progressively build contracts from explicit argument arrays:
 * the engine records the facts it is given and decides nothing about them. Any caller may
 * read or write any contract (authorization is the caller's concern). Reads return read-only
 * views ({@see ContractView}, {@see CycleView}). No transactions, no cache, no hooks.
 *
 * One lock per contract ({@see self::lock()}): every write runs under a lock, the caller's or,
 * for an update or meta write made without one, an internal one the engine takes for that call.
 * Cycle writes require the caller's lock. A write is refused with {@see ContractLockedException}
 * while another operation holds the lock.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\CycleView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PaymentInstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DuplicateCycleException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentFilter;

defined( 'ABSPATH' ) || exit;

/**
 * Public contract facade: reads and writes.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 *
 * @phpstan-type ContractFields array{customer_id?: int|null, currency?: string|null, selling_plan_id?: int|null, origin_order_id?: int|null, status?: string, payment_method?: string|null, payment_method_title?: string|null, payment_token_id?: int|null, start_gmt?: string|null, next_payment_gmt?: string|null, last_payment_gmt?: string|null, last_attempt_gmt?: string|null, trial_end_gmt?: string|null, end_gmt?: string|null, schedule_source?: string, billing_total?: string, discount_total?: string, shipping_total?: string, tax_total?: string, items?: array<int, array<string, mixed>>, addresses?: array<string, array<string, mixed>>}
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
	 * Keys accepted by {@see self::create()}: the update keys plus the create-only `extension_slug`
	 * and `creation_key`.
	 *
	 * @var array<string, true>
	 */
	private const CREATE_KEYS = array(
		'extension_slug' => true,
		'creation_key'   => true,
	) + self::CONTRACT_KEYS;

	/**
	 * Option keys accepted by {@see self::create()}.
	 *
	 * @var array<string, true>
	 */
	private const CREATE_OPTION_KEYS = array(
		'operation' => true,
	);

	/**
	 * Lock operation label of a new contract when the caller names none.
	 */
	private const CREATE_OPERATION = 'create';

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
	 * lock expires after {@see ContractLock::TTL_SECONDS}. An expired lock is taken over. A lock
	 * gives no fresh read snapshot: inside the caller's own transaction, read after taking it.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $operation   Operation label (1-64 of `[a-z0-9_-]`), shown to callers that conflict.
	 * @return ContractLock|null The held lock; null when another operation holds it or the contract does not exist.
	 * @throws InvalidArgumentException If the label is invalid.
	 */
	public static function lock( int $contract_id, string $operation ): ?ContractLock {
		return ( new ContractRepository() )->acquire_lock( $contract_id, $operation );
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Create a contract locked from birth: finish building it (cycles, status) with the returned
	 * lock, then release it. A `creation_key` (1-100 characters, trimmed) makes the create
	 * idempotent per extension, also inside a caller's transaction (there is no lock to take
	 * first, so a taken key is recognised by its error number, not a read). Without a key every
	 * call creates a contract. Unknown keys and options get `_doing_it_wrong()` and are ignored.
	 *
	 * @param array<string, mixed> $args    `extension_slug` (required, the owner), `status` (default
	 *                                      `draft`), `creation_key`, and any of {@see self::CONTRACT_KEYS}.
	 *                                      Dates take a `DateTimeInterface` or GMT `Y-m-d H:i:s`;
	 *                                      money needs a currency. `schedule_source` other than
	 *                                      `subscriptions_engine` (the default) is never dispatched.
	 * @param array<string, mixed> $options `operation`: the lock's label (1-64 of `[a-z0-9_-]`, default `create`).
	 * @return array{contract: ContractView, lock: ContractLock}|null The contract as written (no re-read) and its
	 *                                                                lock; null when `creation_key` is taken.
	 * @throws ValidationException If `extension_slug` is missing, a value is invalid or the operation label is not a
	 *                             string, with every invalid value reported.
	 * @throws InvalidArgumentException If the operation label is not 1-64 of `[a-z0-9_-]`: the lock refuses it, alone.
	 */
	public static function create( array $args, array $options = array() ): ?array {
		$filtered_options = ArgumentFilter::filter_known_keys( __METHOD__, $options, self::CREATE_OPTION_KEYS, 'option' );
		$filtered_args    = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::CREATE_KEYS );

		$validator      = new ArgumentValidator();
		$operation      = $validator->get_string( 'operation', $filtered_options['operation'] ?? self::CREATE_OPERATION );
		$extension_slug = $validator->get_nullable_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		$creation_key   = $validator->get_nullable_string( 'creation_key', $filtered_args['creation_key'] ?? null );
		$fields         = self::get_contract_fields( $validator, $filtered_args );
		try {
			$validator->throw_errors();
			$contract = Contract::create(
				array(
					'extension_slug' => $extension_slug,
					'creation_key'   => $creation_key,
				) + $fields
			);
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		$lock = ( new ContractRepository() )->insert( $contract, (string) $operation );
		if ( null === $lock ) {
			return null;
		}

		return array(
			'contract' => ContractView::from_contract( $contract, true ),
			'lock'     => $lock,
		);
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Write the given fields (the keys of {@see self::create()} except `extension_slug`) to an
	 * existing contract. Only the present keys are written; `items` and `addresses` replace the
	 * whole set; `null` clears a nullable field (a money field to 0). Unknown keys raise a
	 * `_doing_it_wrong()` notice and are ignored. The arguments are checked before the contract is
	 * read, so invalid arguments throw even for a missing contract.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $args        Fields to write.
	 * @param ContractLock|null    $lock        The caller's lock on this contract, or null for an internal one.
	 * @return ContractView|null The row as read before the write plus the written fields; null
	 *                           when the contract does not exist (also when it is deleted before the write).
	 * @throws ValidationException If a value is invalid, with every invalid value reported.
	 * @throws InvalidArgumentException If the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 */
	public static function update( int $contract_id, array $args, ?ContractLock $lock = null ): ?ContractView {
		if ( null !== $lock ) {
			ContractRepository::assert_lock_for( $lock, $contract_id );
		}

		$filtered_args = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::CONTRACT_KEYS );

		$validator = new ArgumentValidator();
		$changes   = self::get_contract_fields( $validator, $filtered_args );
		try {
			$validator->throw_errors();
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		$repository = new ContractRepository();

		if ( array() === $changes ) {
			$contract = $repository->find( $contract_id );
			return null === $contract ? null : ContractView::from_contract( $contract, true );
		}

		if ( null !== $lock ) {
			$contract = self::update_contract( $repository, $contract_id, $changes, $lock );
			return null === $contract ? null : ContractView::from_contract( $contract, true );
		}

		// The internal lock covers the read the write is validated against, not only the write.
		$internal_lock = $repository->acquire_internal_lock( $contract_id );
		if ( null === $internal_lock ) {
			return null;
		}
		try {
			$contract = self::update_contract( $repository, $contract_id, $changes, $internal_lock );
		} finally {
			$repository->release_internal_lock( $internal_lock );
		}
		if ( null === $contract ) {
			return null;
		}

		// The view reports no lock: the one read with the row was the internal lock, now released.
		$contract->clear_lock_state();

		return ContractView::from_contract( $contract, true );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Read the contract, apply the checked fields, and write them behind {@see self::update()}.
	 *
	 * @param ContractRepository   $repository  Repository.
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $changes     Normalized fields from {@see self::get_contract_fields()}.
	 * @phpstan-param ContractFields $changes
	 * @param ContractLock         $lock        The held lock.
	 * @return Contract|null The contract with the written fields; null when it does not exist.
	 * @throws ValidationException If a value breaks an entity invariant.
	 */
	private static function update_contract( ContractRepository $repository, int $contract_id, array $changes, ContractLock $lock ): ?Contract {
		$contract = $repository->find( $contract_id );
		if ( null === $contract ) {
			return null;
		}

		try {
			self::apply( $contract, $changes );
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		$updated = $repository->update_fields( $contract, array_keys( $changes ), $lock );
		if ( ! $updated ) {
			return null;
		}

		return $contract;
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Append a cycle to a contract's chain `(contract_id, kind)`, append-if-absent on the chain's
	 * unique positions: a taken position or count is a lost race and returns null. A storage
	 * failure throws a `RuntimeException` and writes nothing. The lock is the contract's
	 * existence check: the engine deletes a contract only to discard a failed create, before its
	 * lock is handed out. Unknown keys raise a `_doing_it_wrong()` notice.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $args        Cycle fields: `starts_at_gmt`, `ends_at_gmt` and
	 *                                          `currency` (required); `status` (registered, default
	 *                                          `pending`); `kind` (default `billing`); `sequence_no`
	 *                                          (null: the head's plus one); `count` (null: not
	 *                                          counted; unique in the chain); `expected_total`
	 *                                          (default 0); `order_id`.
	 * @param ContractLock         $lock        The caller's lock on this contract, from {@see self::lock()},
	 *                                          {@see self::create()} or the due handler's context.
	 * @return CycleView|null The appended cycle, or null when the position or count is already taken.
	 * @throws ValidationException If a required key is missing or a value is invalid, with every
	 *                             invalid value reported (ill-typed arguments before the cycle is built).
	 * @throws InvalidArgumentException If the lock is for another contract.
	 * @throws ContractLockedException If `$lock` was lost.
	 */
	public static function add_cycle( int $contract_id, array $args, ContractLock $lock ): ?CycleView {
		$filtered_args = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::CYCLE_KEYS );

		$validator  = new ArgumentValidator();
		$cycle_args = array(
			'contract_id'    => $contract_id,
			'kind'           => $filtered_args['kind'] ?? null,
			'sequence_no'    => $validator->get_nullable_id( 'sequence_no', $filtered_args['sequence_no'] ?? null ),
			'count'          => $validator->get_nullable_id( 'count', $filtered_args['count'] ?? null ),
			'status'         => $filtered_args['status'] ?? null,
			'starts_at_gmt'  => $validator->get_nullable_date( 'starts_at_gmt', $filtered_args['starts_at_gmt'] ?? null ),
			'ends_at_gmt'    => $validator->get_nullable_date( 'ends_at_gmt', $filtered_args['ends_at_gmt'] ?? null ),
			'expected_total' => $validator->get_money( 'expected_total', $filtered_args['expected_total'] ?? null ),
			'currency'       => $validator->get_currency( $filtered_args['currency'] ?? null ),
			'order_id'       => $validator->get_nullable_id( 'order_id', $filtered_args['order_id'] ?? null ),
		);
		try {
			$validator->throw_errors();
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		try {
			$cycle = Cycle::create( $cycle_args );
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
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
	 * `from_status`, exactly one gets true. A same-status call returns true once the cycle
	 * carries the status and the values. Unknown keys raise a `_doing_it_wrong()` notice.
	 *
	 * @param int                  $cycle_id Cycle id.
	 * @param array<string, mixed> $args     `from_status` (required, the status the caller read),
	 *                                       `to_status` (required, registered), and `order_id`
	 *                                       (?int) and `reason` (?string), written when present
	 *                                       (null clears).
	 * @param ContractLock         $lock     The caller's lock on the cycle's contract, from {@see self::lock()},
	 *                                       {@see self::create()} or the due handler's context.
	 * @return bool True when this call made the transition, or for a same-status call when the
	 *              cycle carries the status and the values.
	 * @throws ValidationException If a status is missing or unregistered (field `to_status`), or a value is
	 *                             invalid, with every invalid value reported.
	 * @throws InvalidArgumentException If the cycle does not exist, or it belongs to another contract than the lock.
	 * @throws ContractLockedException If `$lock` was lost.
	 */
	public static function transition_cycle( int $cycle_id, array $args, ContractLock $lock ): bool {
		$filtered_args = ArgumentFilter::filter_known_keys( __METHOD__, $args, self::TRANSITION_KEYS );

		$validator   = new ArgumentValidator();
		$from_status = $validator->get_non_empty_string( 'from_status', $filtered_args['from_status'] ?? null );
		$to_status   = $validator->get_non_empty_string( 'to_status', $filtered_args['to_status'] ?? null );
		$changes     = array();
		if ( array_key_exists( 'order_id', $filtered_args ) ) {
			$changes['order_id'] = $validator->get_nullable_id( 'order_id', $filtered_args['order_id'] );
		}
		if ( array_key_exists( 'reason', $filtered_args ) ) {
			$changes['reason'] = $validator->get_nullable_string( 'reason', $filtered_args['reason'] );
		}

		try {
			$validator->throw_errors();

			return ( new ContractRepository() )->transition_cycle( $cycle_id, (string) $from_status, (string) $to_status, $changes, $lock );
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
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
	 * @param bool              $unique      When true, add nothing if the key already exists.
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null for an internal one.
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
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null for an internal one.
	 * @return bool True when a value was added or changed; false when nothing changed.
	 * @throws InvalidArgumentException If `$key` is empty, the contract does not exist, or the lock is for another contract.
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
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null for an internal one.
	 * @return bool True when at least one value was deleted; false also for a missing contract.
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
	 * `$offset` throws a {@see ValidationException}.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $limit       Maximum cycles to return.
	 * @param int $offset      Cycles to skip (for paging).
	 * @return array<int, CycleView> Cycles newest first.
	 * @throws ValidationException If `$limit` or `$offset` is negative, with both reported.
	 */
	public static function get_cycles( int $contract_id, int $limit = 20, int $offset = 0 ): array {
		$validator = new ArgumentValidator();
		$limit     = (int) $validator->get_non_negative_int( 'limit', $limit );
		$offset    = (int) $validator->get_non_negative_int( 'offset', $offset );
		try {
			$validator->throw_errors();
		} catch ( InvalidInputException $e ) {
			throw ValidationException::from_core( $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- from_core() escapes the message.
		}

		return self::cycle_views( ( new ContractRepository() )->find_cycle_history( $contract_id, Cycle::KIND_BILLING, $limit, $offset ) );
	}

	/**
	 * Check the shape of the present contract fields with the collector and return them
	 * normalized, `items` and `addresses` without unknown keys (a refused field is left
	 * out or null; the collector holds its error).
	 *
	 * @param ArgumentValidator    $validator Argument collector.
	 * @param array<string, mixed> $args      Caller fields (known keys only; `extension_slug` and `creation_key` are ignored).
	 * @return array<string, mixed>
	 * @phpstan-return ContractFields
	 */
	private static function get_contract_fields( ArgumentValidator $validator, array $args ): array {
		$fields = array();
		if ( array_key_exists( 'customer_id', $args ) ) {
			$fields['customer_id'] = $validator->get_nullable_id( 'customer_id', $args['customer_id'] );
		}
		if ( array_key_exists( 'currency', $args ) ) {
			$fields['currency'] = $validator->get_currency( $args['currency'] );
		}
		if ( array_key_exists( 'selling_plan_id', $args ) ) {
			$fields['selling_plan_id'] = $validator->get_nullable_id( 'selling_plan_id', $args['selling_plan_id'] );
		}
		if ( array_key_exists( 'origin_order_id', $args ) ) {
			$fields['origin_order_id'] = $validator->get_nullable_id( 'origin_order_id', $args['origin_order_id'] );
		}
		if ( array_key_exists( 'status', $args ) ) {
			$value = $validator->get_string( 'status', $args['status'] );
			if ( null !== $value ) {
				$fields['status'] = $value;
			}
		}
		if ( array_key_exists( 'payment_method', $args ) ) {
			$fields['payment_method'] = $validator->get_nullable_string( 'payment_method', $args['payment_method'] );
		}
		if ( array_key_exists( 'payment_method_title', $args ) ) {
			$fields['payment_method_title'] = $validator->get_nullable_string( 'payment_method_title', $args['payment_method_title'] );
		}
		if ( array_key_exists( 'payment_token_id', $args ) ) {
			$fields['payment_token_id'] = $validator->get_nullable_id( 'payment_token_id', $args['payment_token_id'] );
		}
		if ( array_key_exists( 'start_gmt', $args ) ) {
			$fields['start_gmt'] = $validator->get_nullable_date( 'start_gmt', $args['start_gmt'] );
		}
		if ( array_key_exists( 'next_payment_gmt', $args ) ) {
			$fields['next_payment_gmt'] = $validator->get_nullable_date( 'next_payment_gmt', $args['next_payment_gmt'] );
		}
		if ( array_key_exists( 'last_payment_gmt', $args ) ) {
			$fields['last_payment_gmt'] = $validator->get_nullable_date( 'last_payment_gmt', $args['last_payment_gmt'] );
		}
		if ( array_key_exists( 'last_attempt_gmt', $args ) ) {
			$fields['last_attempt_gmt'] = $validator->get_nullable_date( 'last_attempt_gmt', $args['last_attempt_gmt'] );
		}
		if ( array_key_exists( 'trial_end_gmt', $args ) ) {
			$fields['trial_end_gmt'] = $validator->get_nullable_date( 'trial_end_gmt', $args['trial_end_gmt'] );
		}
		if ( array_key_exists( 'end_gmt', $args ) ) {
			$fields['end_gmt'] = $validator->get_nullable_date( 'end_gmt', $args['end_gmt'] );
		}
		if ( array_key_exists( 'schedule_source', $args ) ) {
			$value = $validator->get_string( 'schedule_source', $args['schedule_source'] );
			if ( null !== $value ) {
				$fields['schedule_source'] = $value;
			}
		}
		if ( array_key_exists( 'billing_total', $args ) ) {
			$value = $validator->get_money( 'billing_total', $args['billing_total'] );
			if ( null !== $value ) {
				$fields['billing_total'] = $value;
			}
		}
		if ( array_key_exists( 'discount_total', $args ) ) {
			$value = $validator->get_money( 'discount_total', $args['discount_total'] );
			if ( null !== $value ) {
				$fields['discount_total'] = $value;
			}
		}
		if ( array_key_exists( 'shipping_total', $args ) ) {
			$value = $validator->get_money( 'shipping_total', $args['shipping_total'] );
			if ( null !== $value ) {
				$fields['shipping_total'] = $value;
			}
		}
		if ( array_key_exists( 'tax_total', $args ) ) {
			$value = $validator->get_money( 'tax_total', $args['tax_total'] );
			if ( null !== $value ) {
				$fields['tax_total'] = $value;
			}
		}
		if ( array_key_exists( 'items', $args ) ) {
			$items = $validator->get_list_of_arrays( 'items', $args['items'] );
			if ( null !== $items ) {
				$fields['items'] = ArgumentFilter::filter_known_keys_in_rows( self::class, $items, array_fill_keys( Contract::ITEM_FIELDS, true ), 'item key' );
			}
		}
		if ( array_key_exists( 'addresses', $args ) ) {
			$addresses = $validator->get_contract_addresses( $args['addresses'] );
			if ( null !== $addresses ) {
				$fields['addresses'] = ArgumentFilter::filter_known_keys_in_rows( self::class, $addresses, array_fill_keys( Contract::ADDRESS_FIELDS, true ), 'address key' );
			}
		}

		return $fields;
	}

	/**
	 * Apply normalized changes to a contract through its setters, which enforce the entity
	 * invariants, then check that money has a currency. Every refusal is thrown together.
	 * Nothing is written to storage.
	 *
	 * @param Contract             $contract Contract to change.
	 * @param array<string, mixed> $changes  Normalized fields from {@see self::get_contract_fields()}.
	 * @phpstan-param ContractFields $changes
	 * @throws InvalidInputException If a value breaks an entity invariant.
	 */
	private static function apply( Contract $contract, array $changes ): void {
		$errors = array();
		if ( array_key_exists( 'customer_id', $changes ) ) {
			$contract->set_customer_id( $changes['customer_id'] );
		}
		if ( array_key_exists( 'currency', $changes ) ) {
			$contract->set_currency( $changes['currency'] );
		}
		if ( array_key_exists( 'selling_plan_id', $changes ) ) {
			$contract->set_selling_plan_id( $changes['selling_plan_id'] );
		}
		if ( array_key_exists( 'origin_order_id', $changes ) ) {
			$contract->set_origin_order_id( $changes['origin_order_id'] );
		}
		if ( array_key_exists( 'status', $changes ) ) {
			try {
				$contract->set_status( $changes['status'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}
		if ( array_key_exists( 'schedule_source', $changes ) ) {
			try {
				$contract->set_schedule_source( $changes['schedule_source'] );
			} catch ( InvalidInputException $e ) {
				$errors[] = $e;
			}
		}
		if ( array_key_exists( 'start_gmt', $changes ) ) {
			$contract->set_start_gmt( $changes['start_gmt'] );
		}
		if ( array_key_exists( 'next_payment_gmt', $changes ) ) {
			$contract->set_next_payment_gmt( $changes['next_payment_gmt'] );
		}
		if ( array_key_exists( 'last_payment_gmt', $changes ) ) {
			$contract->set_last_payment_gmt( $changes['last_payment_gmt'] );
		}
		if ( array_key_exists( 'last_attempt_gmt', $changes ) ) {
			$contract->set_last_attempt_gmt( $changes['last_attempt_gmt'] );
		}
		if ( array_key_exists( 'trial_end_gmt', $changes ) ) {
			$contract->set_trial_end_gmt( $changes['trial_end_gmt'] );
		}
		if ( array_key_exists( 'end_gmt', $changes ) ) {
			$contract->set_end_gmt( $changes['end_gmt'] );
		}
		if ( array_key_exists( 'billing_total', $changes ) ) {
			$contract->set_billing_total( $changes['billing_total'] );
		}
		if ( array_key_exists( 'discount_total', $changes ) ) {
			$contract->set_discount_total( $changes['discount_total'] );
		}
		if ( array_key_exists( 'shipping_total', $changes ) ) {
			$contract->set_shipping_total( $changes['shipping_total'] );
		}
		if ( array_key_exists( 'tax_total', $changes ) ) {
			$contract->set_tax_total( $changes['tax_total'] );
		}
		if ( array_key_exists( 'items', $changes ) ) {
			$contract->set_items( $changes['items'] );
		}
		if ( array_key_exists( 'addresses', $changes ) ) {
			$contract->set_addresses( $changes['addresses'] );
		}

		$instrument = $contract->get_payment_instrument();
		$contract->set_payment_instrument(
			new PaymentInstrumentRef(
				array_key_exists( 'payment_token_id', $changes ) ? $changes['payment_token_id'] : $instrument->get_token_id(),
				array_key_exists( 'payment_method', $changes ) ? $changes['payment_method'] : $instrument->get_gateway(),
				array_key_exists( 'payment_method_title', $changes ) ? $changes['payment_method_title'] : $instrument->get_title()
			)
		);

		$money_error = $contract->get_money_currency_error();
		if ( null !== $money_error ) {
			$errors[] = $money_error;
		}
		if ( array() !== $errors ) {
			throw InvalidInputException::from_errors( $errors ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The caller converts it with ValidationException::from_core(), which escapes the message.
		}
	}

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
