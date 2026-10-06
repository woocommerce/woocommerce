<?php
/**
 * Contracts - the engine's public contract write facade.
 *
 * Extensions create and progressively build contracts from explicit argument arrays:
 * the engine records the facts it is given and decides nothing about them. Any caller
 * may write any contract (authorization is the caller's concern). The engine opens no
 * transaction and keeps no cache, so a caller may wrap several calls in its own
 * transaction. No hooks fire.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DomainException;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\InstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DuplicateCycleException;

defined( 'ABSPATH' ) || exit;

/**
 * Public contract write facade.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Contracts {

	/**
	 * Keys accepted by {@see self::create()} (plus `owner`) and {@see self::update()}.
	 *
	 * @var array<int, string>
	 */
	private const CONTRACT_KEYS = array(
		'customer_id',
		'currency',
		'selling_plan_id',
		'origin_order_id',
		'status',
		'payment_method',
		'payment_method_title',
		'payment_token_id',
		'start_gmt',
		'next_payment_gmt',
		'last_payment_gmt',
		'last_attempt_gmt',
		'trial_end_gmt',
		'end_gmt',
		'schedule_source',
		'billing_total',
		'discount_total',
		'shipping_total',
		'tax_total',
		'items',
		'addresses',
		'plan_snapshot',
		'items_snapshot',
	);

	/**
	 * Keys accepted by {@see self::add_cycle()}.
	 *
	 * @var array<int, string>
	 */
	private const CYCLE_KEYS = array(
		'status',
		'kind',
		'sequence_no',
		'count',
		'starts_at_gmt',
		'ends_at_gmt',
		'expected_total',
		'currency',
		'order_id',
		'plan_snapshot',
		'items_snapshot',
	);

	/**
	 * Money keys (null resets to 0).
	 *
	 * @var array<int, string>
	 */
	private const MONEY_KEYS = array( 'billing_total', 'discount_total', 'shipping_total', 'tax_total' );

	/**
	 * Create a contract from explicit fields.
	 *
	 * Only `owner` is required; the status defaults to `draft`. Dates accept a
	 * `DateTimeInterface` or a GMT `Y-m-d H:i:s` string; money accepts numbers or numeric
	 * strings, and a non-null money value requires a currency. `items` is a list of item
	 * rows; `addresses` is keyed `billing` / `shipping`; `plan_snapshot` / `items_snapshot`
	 * are payload arrays recorded as the contract's current snapshots.
	 *
	 * @param array<string, mixed> $args Contract fields: `owner` (required, the owning extension
	 *                                   slug), `status` (a registered contract status, default
	 *                                   `draft`), and any of {@see self::CONTRACT_KEYS}.
	 * @return int The new contract id.
	 * @throws InvalidArgumentException If a key is unknown or a value is invalid.
	 */
	public static function create( array $args ): int {
		self::assert_known_keys( $args, array_merge( array( 'owner' ), self::CONTRACT_KEYS ) );

		$owner = $args['owner'] ?? null;
		if ( ! is_string( $owner ) || '' === $owner ) {
			throw new InvalidArgumentException( 'Contracts: "owner" is required and must be a non-empty string.' );
		}
		unset( $args['owner'] );

		$contract  = Contract::create( array( 'extension_slug' => $owner ) );
		$snapshots = self::apply( $contract, $args );

		$repository = new ContractRepository();
		$id         = $repository->insert( $contract );
		self::store_snapshots( $repository, $contract, $snapshots );

		return $id;
	}

	/**
	 * Write the given fields to an existing contract.
	 *
	 * Takes the keys of {@see self::create()} except `owner`. Only the columns of the
	 * present keys are written, so fields a concurrent writer changed in between keep
	 * its values; `items` and `addresses` replace the whole set; `null` clears a
	 * nullable field (and resets a money field to 0).
	 *
	 * @param int                  $id   Contract id.
	 * @param array<string, mixed> $args Fields to write.
	 * @return bool True when written; false when the contract does not exist.
	 * @throws InvalidArgumentException If a key is unknown or a value is invalid.
	 */
	public static function update( int $id, array $args ): bool {
		self::assert_known_keys( $args, self::CONTRACT_KEYS );

		$repository = new ContractRepository();
		$contract   = $repository->find( $id );
		if ( null === $contract ) {
			return false;
		}

		$snapshots = self::apply( $contract, $args );
		$fields    = array_values( array_diff( array_keys( $args ), array( 'plan_snapshot', 'items_snapshot' ) ) );

		if ( array() !== $fields ) {
			$repository->update_fields( $contract, $fields );
		}
		self::store_snapshots( $repository, $contract, $snapshots );

		return true;
	}

	/**
	 * Append a cycle to a contract's chain `(contract_id, kind)`.
	 *
	 * The first public form of the cycle append tool: append-if-absent on the chain's
	 * unique positions. `status` (a registered cycle status), `starts_at_gmt` and
	 * `ends_at_gmt` are required. `kind` defaults to `billing`; `sequence_no` defaults to
	 * the head's plus one; `count` defaults to MAX(count) + 1 in the chain (pass `null` for
	 * a non-counting cycle); `expected_total` defaults to 0; `currency` defaults to the contract's;
	 * `order_id` is optional. Without `plan_snapshot` / `items_snapshot` payloads the
	 * cycle references the contract's current snapshots; payloads attach to the new cycle
	 * only. The owner is copied from the contract.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $args        Cycle fields.
	 * @return int The new cycle id.
	 * @throws InvalidArgumentException If the contract is unknown, a key is unknown, or a value is invalid.
	 * @throws DomainException If the chain position is already taken.
	 */
	public static function add_cycle( int $contract_id, array $args ): int {
		self::assert_known_keys( $args, self::CYCLE_KEYS );

		$repository = new ContractRepository();
		$contract   = $repository->find_summary( $contract_id );
		if ( null === $contract ) {
			throw new InvalidArgumentException( sprintf( 'Contracts: contract %d does not exist.', (int) $contract_id ) );
		}

		$status = $args['status'] ?? null;
		if ( ! is_string( $status ) || ! CycleStatus::is_registered( $status ) ) {
			throw new InvalidArgumentException( 'Contracts: "status" is required and must be a registered cycle status.' );
		}

		$kind = $args['kind'] ?? Cycle::KIND_BILLING;
		if ( ! is_string( $kind ) || '' === $kind ) {
			throw new InvalidArgumentException( 'Contracts: "kind" must be a non-empty string.' );
		}

		$starts_at = self::nullable_date( 'starts_at_gmt', $args['starts_at_gmt'] ?? null );
		$ends_at   = self::nullable_date( 'ends_at_gmt', $args['ends_at_gmt'] ?? null );
		if ( null === $starts_at || null === $ends_at ) {
			throw new InvalidArgumentException( 'Contracts: "starts_at_gmt" and "ends_at_gmt" are required.' );
		}

		$currency = array_key_exists( 'currency', $args ) ? self::currency( $args['currency'] ) : $contract->get_currency();
		if ( null === $currency ) {
			throw new InvalidArgumentException( 'Contracts: a cycle needs a currency, given or from the contract.' );
		}

		$head        = $repository->find_chain_head( $contract_id, $kind );
		$sequence_no = array_key_exists( 'sequence_no', $args )
			? self::nullable_id( 'sequence_no', $args['sequence_no'] )
			: ( null === $head ? 1 : $head->get_sequence_no() + 1 );
		if ( null === $sequence_no ) {
			throw new InvalidArgumentException( 'Contracts: "sequence_no" must be a positive integer.' );
		}
		$count = array_key_exists( 'count', $args )
			? self::nullable_id( 'count', $args['count'] )
			: ( $repository->max_count( $contract_id, $kind ) ?? 0 ) + 1;

		$cycle_args = array(
			'contract_id'    => $contract_id,
			'kind'           => $kind,
			'sequence_no'    => $sequence_no,
			'count'          => $count,
			'status'         => $status,
			'starts_at_gmt'  => $starts_at,
			'ends_at_gmt'    => $ends_at,
			'expected_total' => self::money( 'expected_total', $args['expected_total'] ?? null ),
			'currency'       => $currency,
			'order_id'       => self::nullable_id( 'order_id', $args['order_id'] ?? null ),
			'extension_slug' => $contract->get_extension_slug(),
		);

		if ( array_key_exists( 'plan_snapshot', $args ) ) {
			if ( ! is_array( $args['plan_snapshot'] ) ) {
				throw new InvalidArgumentException( 'Contracts: "plan_snapshot" must be an array.' );
			}
			$cycle_args['plan_snapshot'] = PlanSnapshot::from_array( self::string_keyed( $args['plan_snapshot'] ) );
		} else {
			$cycle_args['plan_snapshot_id'] = $contract->get_plan_snapshot_id();
		}

		if ( array_key_exists( 'items_snapshot', $args ) ) {
			$cycle_args['items_snapshot'] = ItemsSnapshot::from_items( self::item_rows( 'items_snapshot', $args['items_snapshot'] ) );
		} else {
			$cycle_args['items_snapshot_id'] = $contract->get_items_snapshot_id();
		}

		try {
			$cycle = Cycle::create( $cycle_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( esc_html( $e->getMessage() ) );
		}

		try {
			$repository->append_cycle( $cycle, $head );
		} catch ( DuplicateCycleException $e ) {
			throw new DomainException( 'Contracts: the cycle position already exists.' );
		}

		return (int) $cycle->get_id();
	}

	/**
	 * Add a meta value to a contract (WordPress `add_post_meta()` semantics). A key may
	 * hold several values.
	 *
	 * @param int    $id     Contract id.
	 * @param string $key    Meta key.
	 * @param mixed  $value  Meta value; serialized when not scalar.
	 * @param bool   $unique When true, add nothing if the key already exists.
	 * @return int|null The meta row id; null when the contract does not exist or `$unique` and the key exists.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function add_meta( int $id, string $key, $value, bool $unique = false ): ?int {
		self::assert_meta_key( $key );

		$repository = new ContractRepository();
		if ( ! $repository->exists( $id ) ) {
			return null;
		}

		return $repository->add_meta( $id, $key, $value, $unique );
	}

	/**
	 * Update a contract's meta values for `$key` (WordPress `update_post_meta()` semantics):
	 * adds the key when absent, else rewrites every value, or only the values equal to
	 * `$prev_value` when given.
	 *
	 * @param int    $id         Contract id.
	 * @param string $key        Meta key.
	 * @param mixed  $value      New value; serialized when not scalar.
	 * @param mixed  $prev_value Only update values equal to this; null updates all.
	 * @return bool True when a value was added or changed; false when nothing changed or the contract does not exist.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function update_meta( int $id, string $key, $value, $prev_value = null ): bool {
		self::assert_meta_key( $key );

		$repository = new ContractRepository();
		if ( ! $repository->exists( $id ) ) {
			return false;
		}

		return $repository->update_meta( $id, $key, $value, $prev_value );
	}

	/**
	 * Delete a contract's meta values for `$key` (WordPress `delete_post_meta()` semantics).
	 *
	 * @param int    $id    Contract id.
	 * @param string $key   Meta key.
	 * @param mixed  $value Only delete values equal to this; null deletes every value for the key.
	 * @return bool True when at least one value was deleted.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	public static function delete_meta( int $id, string $key, $value = null ): bool {
		self::assert_meta_key( $key );

		return ( new ContractRepository() )->delete_meta( $id, $key, $value );
	}

	/**
	 * Read contract meta (WordPress `get_post_meta()` semantics), oldest value first.
	 *
	 * @param int    $id     Contract id.
	 * @param string $key    Meta key; empty for every key.
	 * @param bool   $single With a key: return the first value only.
	 * @return mixed Empty key: values grouped by key. Key + `$single`: the first value, or ''
	 *               when absent. Key only: the list of values (`[]` when absent).
	 */
	public static function get_meta( int $id, string $key = '', bool $single = false ) {
		return ( new ContractRepository() )->get_meta( $id, $key, $single );
	}

	/**
	 * Validate the caller's fields and apply them to a contract through its setters.
	 * Nothing is written to storage; an invalid value throws before any write.
	 *
	 * @param Contract             $contract Contract to change.
	 * @param array<string, mixed> $args     Caller fields (known keys only, no `owner`).
	 * @return array{plan: array<string, mixed>|null, items: array<int, array<string, mixed>>|null} Snapshot payloads to store.
	 * @throws InvalidArgumentException If a value is invalid.
	 */
	private static function apply( Contract $contract, array $args ): array {
		$snapshots  = array(
			'plan'  => null,
			'items' => null,
		);
		$instrument = $contract->get_payment_instrument();
		$token_id   = $instrument->get_token_id();
		$gateway    = $instrument->get_gateway();
		$title      = $instrument->get_title();

		foreach ( $args as $key => $value ) {
			switch ( $key ) {
				case 'customer_id':
					$contract->set_customer_id( self::nullable_id( $key, $value ) );
					break;
				case 'selling_plan_id':
					$contract->set_selling_plan_id( self::nullable_id( $key, $value ) );
					break;
				case 'origin_order_id':
					$contract->set_origin_order_id( self::nullable_id( $key, $value ) );
					break;
				case 'payment_token_id':
					$token_id = self::nullable_id( $key, $value );
					break;
				case 'payment_method':
					$gateway = self::nullable_string( $key, $value );
					break;
				case 'payment_method_title':
					$title = self::nullable_string( $key, $value );
					break;
				case 'currency':
					$contract->set_currency( self::currency( $value ) );
					break;
				case 'status':
					if ( ! is_string( $value ) || ! ContractStatus::is_registered( $value ) ) {
						throw new InvalidArgumentException( 'Contracts: "status" must be a registered contract status.' );
					}
					$contract->set_status( $value );
					break;
				case 'schedule_source':
					if ( ! is_string( $value ) || ! in_array( $value, array( Contract::SCHEDULE_SOURCE_PRIMITIVE, Contract::SCHEDULE_SOURCE_GATEWAY ), true ) ) {
						throw new InvalidArgumentException( 'Contracts: "schedule_source" must be "primitive" or "gateway".' );
					}
					$contract->set_schedule_source( $value );
					break;
				case 'start_gmt':
					$contract->set_start_gmt( self::nullable_date( $key, $value ) );
					break;
				case 'next_payment_gmt':
					$contract->set_next_payment_gmt( self::nullable_date( $key, $value ) );
					break;
				case 'last_payment_gmt':
					$contract->set_last_payment_gmt( self::nullable_date( $key, $value ) );
					break;
				case 'last_attempt_gmt':
					$contract->set_last_attempt_gmt( self::nullable_date( $key, $value ) );
					break;
				case 'trial_end_gmt':
					$contract->set_trial_end_gmt( self::nullable_date( $key, $value ) );
					break;
				case 'end_gmt':
					$contract->set_end_gmt( self::nullable_date( $key, $value ) );
					break;
				case 'billing_total':
					$contract->set_billing_total( self::money( $key, $value ) );
					break;
				case 'discount_total':
					$contract->set_discount_total( self::money( $key, $value ) );
					break;
				case 'shipping_total':
					$contract->set_shipping_total( self::money( $key, $value ) );
					break;
				case 'tax_total':
					$contract->set_tax_total( self::money( $key, $value ) );
					break;
				case 'items':
					$contract->set_items( self::items( $value ) );
					break;
				case 'addresses':
					$contract->set_addresses( self::addresses( $value ) );
					break;
				case 'plan_snapshot':
					if ( ! is_array( $value ) ) {
						throw new InvalidArgumentException( 'Contracts: "plan_snapshot" must be an array.' );
					}
					$snapshots['plan'] = self::string_keyed( $value );
					break;
				case 'items_snapshot':
					$snapshots['items'] = self::item_rows( $key, $value );
					break;
			}
		}

		$contract->set_payment_instrument( new InstrumentRef( $token_id, $gateway, $title ) );
		self::assert_money_has_currency( $contract, $args );

		return $snapshots;
	}

	/**
	 * Record the snapshot payloads, when any, as the contract's current snapshots.
	 *
	 * @param ContractRepository                                                                   $repository Repository.
	 * @param Contract                                                                             $contract   Stored contract.
	 * @param array{plan: array<string, mixed>|null, items: array<int, array<string, mixed>>|null} $snapshots  Payloads.
	 */
	private static function store_snapshots( ContractRepository $repository, Contract $contract, array $snapshots ): void {
		if ( null !== $snapshots['plan'] || null !== $snapshots['items'] ) {
			$repository->store_contract_snapshots( $contract, $snapshots['plan'], $snapshots['items'] );
		}
	}

	/**
	 * Refuse any key outside `$allowed`.
	 *
	 * @param array<string, mixed> $args    Caller arguments.
	 * @param array<int, string>   $allowed Accepted keys.
	 * @throws InvalidArgumentException If a key is unknown.
	 */
	private static function assert_known_keys( array $args, array $allowed ): void {
		foreach ( array_keys( $args ) as $key ) {
			if ( ! in_array( $key, $allowed, true ) ) {
				throw new InvalidArgumentException( sprintf( 'Contracts: unknown key "%s".', esc_html( (string) $key ) ) );
			}
		}
	}

	/**
	 * Refuse money facts without a currency (data integrity): a non-null money value
	 * needs a currency on the resulting contract, and the currency cannot be cleared
	 * while a money fact is non-zero.
	 *
	 * @param Contract             $contract Contract with the caller's fields applied.
	 * @param array<string, mixed> $args     Caller fields.
	 * @throws InvalidArgumentException If a money fact has no currency.
	 */
	private static function assert_money_has_currency( Contract $contract, array $args ): void {
		if ( null !== $contract->get_currency() ) {
			return;
		}

		foreach ( self::MONEY_KEYS as $key ) {
			if ( array_key_exists( $key, $args ) && null !== $args[ $key ] ) {
				throw new InvalidArgumentException( sprintf( 'Contracts: "%s" requires the contract to have a currency.', esc_html( $key ) ) );
			}
		}

		$totals = array( $contract->get_billing_total(), $contract->get_discount_total(), $contract->get_shipping_total(), $contract->get_tax_total() );
		foreach ( $totals as $total ) {
			if ( 0.0 !== (float) $total ) {
				throw new InvalidArgumentException( 'Contracts: the currency cannot be cleared while a money total is non-zero.' );
			}
		}
	}

	/**
	 * Refuse an empty meta key.
	 *
	 * @param string $key Meta key.
	 * @throws InvalidArgumentException If `$key` is empty.
	 */
	private static function assert_meta_key( string $key ): void {
		if ( '' === $key ) {
			throw new InvalidArgumentException( 'Contracts: the meta key must not be empty.' );
		}
	}

	/**
	 * Validate a currency code, or null.
	 *
	 * @param mixed $value Caller value.
	 * @throws InvalidArgumentException If the value is not null or a three-letter uppercase code.
	 */
	private static function currency( $value ): ?string {
		if ( null !== $value && ( ! is_string( $value ) || 1 !== preg_match( '/^[A-Z]{3}$/', $value ) ) ) {
			throw new InvalidArgumentException( 'Contracts: "currency" must be null or a three-letter uppercase ISO-4217 code.' );
		}

		return $value;
	}

	/**
	 * Validate a string, or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not null or a string.
	 */
	private static function nullable_string( string $key, $value ): ?string {
		if ( null !== $value && ! is_string( $value ) ) {
			throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be null or a string.', esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate a positive integer id, or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not null or a positive integer.
	 */
	private static function nullable_id( string $key, $value ): ?int {
		if ( null === $value ) {
			return null;
		}

		if ( is_string( $value ) && 1 === preg_match( '/^[0-9]+$/', $value ) ) {
			$value = (int) $value;
		}

		if ( ! is_int( $value ) || $value <= 0 ) {
			throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be null or a positive integer.', esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate a GMT datetime, or null, as a UTC `Y-m-d H:i:s` string.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value `DateTimeInterface`, GMT `Y-m-d H:i:s` string, or null.
	 * @throws InvalidArgumentException If the value is not a valid datetime.
	 */
	private static function nullable_date( string $key, $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		if ( $value instanceof DateTimeInterface ) {
			return ( new DateTimeImmutable( '@' . $value->getTimestamp() ) )->format( 'Y-m-d H:i:s' );
		}

		if ( is_string( $value ) ) {
			$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
			if ( false !== $parsed && $parsed->format( 'Y-m-d H:i:s' ) === $value ) {
				return $value;
			}
		}

		throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be null, a DateTimeInterface, or a GMT "Y-m-d H:i:s" string.', esc_html( $key ) ) );
	}

	/**
	 * Validate a money value, normalized to the storage scale; null is 0.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Number, numeric string, or null.
	 * @throws InvalidArgumentException If the value is not numeric.
	 */
	private static function money( string $key, $value ): string {
		if ( null === $value ) {
			return MoneyScale::normalize_money( 0 );
		}

		if ( ! is_int( $value ) && ! is_float( $value ) && ! ( is_string( $value ) && is_numeric( $value ) ) ) {
			throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be a number or a numeric string.', esc_html( $key ) ) );
		}

		return MoneyScale::normalize_money( $value );
	}

	/**
	 * Validate an item row list.
	 *
	 * @param mixed $value Caller value.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidArgumentException If the value is not a list of item rows with known keys.
	 */
	private static function items( $value ): array {
		$rows = self::item_rows( 'items', $value );

		foreach ( $rows as $row ) {
			$unknown = array_diff( array_keys( $row ), Contract::ITEM_FIELDS );
			if ( array() !== $unknown ) {
				throw new InvalidArgumentException( sprintf( 'Contracts: unknown item key "%s".', esc_html( (string) reset( $unknown ) ) ) );
			}
		}

		return $rows;
	}

	/**
	 * Validate a list of array rows.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidArgumentException If the value is not a list of arrays.
	 */
	private static function item_rows( string $key, $value ): array {
		if ( ! is_array( $value ) || ( array() !== $value && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) ) {
			throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be a list of arrays.', esc_html( $key ) ) );
		}

		$rows = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be a list of arrays.', esc_html( $key ) ) );
			}
			$rows[] = self::string_keyed( $row );
		}

		return $rows;
	}

	/**
	 * Validate an addresses map keyed `billing` / `shipping`.
	 *
	 * @param mixed $value Caller value.
	 * @return array<string, array<string, mixed>>
	 * @throws InvalidArgumentException If the map has unknown types or address keys.
	 */
	private static function addresses( $value ): array {
		if ( ! is_array( $value ) ) {
			throw new InvalidArgumentException( 'Contracts: "addresses" must be an array keyed "billing" / "shipping".' );
		}

		$addresses = array();
		foreach ( $value as $type => $address ) {
			if ( ! in_array( $type, array( Contract::ADDRESS_BILLING, Contract::ADDRESS_SHIPPING ), true ) || ! is_array( $address ) ) {
				throw new InvalidArgumentException( 'Contracts: "addresses" must be an array keyed "billing" / "shipping" with array values.' );
			}

			$unknown = array_diff( array_keys( $address ), Contract::ADDRESS_FIELDS );
			if ( array() !== $unknown ) {
				throw new InvalidArgumentException( sprintf( 'Contracts: unknown address key "%s".', esc_html( (string) reset( $unknown ) ) ) );
			}

			$addresses[ $type ] = self::string_keyed( $address );
		}

		return $addresses;
	}

	/**
	 * Re-key an array as string-keyed.
	 *
	 * @param array<int|string, mixed> $value Array.
	 * @return array<string, mixed>
	 */
	private static function string_keyed( array $value ): array {
		$out = array();
		foreach ( $value as $key => $entry ) {
			$out[ (string) $key ] = $entry;
		}

		return $out;
	}
}
