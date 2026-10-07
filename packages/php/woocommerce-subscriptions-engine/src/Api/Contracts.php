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
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\CycleView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\InstrumentRef;
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
	private const CREATE_KEYS = array( 'extension_slug' => true ) + self::CONTRACT_KEYS;

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
	 * Money keys (null resets to 0).
	 *
	 * @var array<int, string>
	 */
	private const MONEY_KEYS = array( 'billing_total', 'discount_total', 'shipping_total', 'tax_total' );

	/**
	 * Create a contract from explicit fields.
	 *
	 * Only `extension_slug` is required; the status defaults to `draft`. Dates accept a
	 * `DateTimeInterface` or a GMT `Y-m-d H:i:s` string; money accepts numbers or numeric
	 * strings, and a non-null money value requires a currency. `items` is a list of item
	 * rows; `addresses` is keyed `billing` / `shipping`. Unknown keys, also inside item rows
	 * and addresses, raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * @param array<string, mixed> $args Contract fields: `extension_slug` (required, the owning
	 *                                   extension), `status` (a registered contract status,
	 *                                   default `draft`), and any of {@see self::CONTRACT_KEYS}.
	 * @return ContractView The new contract, built from the written fields (no re-read): items
	 *                      and addresses as given.
	 * @throws InvalidArgumentException If `extension_slug` is missing or a value is invalid.
	 */
	public static function create( array $args ): ContractView {
		$filtered_args  = self::filter_known_keys( __METHOD__, $args, self::CREATE_KEYS );
		$extension_slug = self::nullable_string( 'extension_slug', $filtered_args['extension_slug'] ?? null );
		unset( $filtered_args['extension_slug'] );

		try {
			$contract = Contract::create( array( 'extension_slug' => $extension_slug ) );
			self::apply( __METHOD__, $contract, $filtered_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		( new ContractRepository() )->insert( $contract );

		return ContractView::from_contract( $contract, true );
	}

	/**
	 * Write the given fields to an existing contract.
	 *
	 * Takes the keys of {@see self::create()} except `extension_slug`. Only the columns of
	 * the present keys are written, so fields a concurrent writer changed in between keep
	 * its values; `items` and `addresses` replace the whole set; `null` clears a
	 * nullable field (and resets a money field to 0). Unknown keys (`extension_slug`
	 * included) raise a `_doing_it_wrong()` notice and are ignored.
	 *
	 * @param int                  $id   Contract id.
	 * @param array<string, mixed> $args Fields to write.
	 * @return ContractView|null The row as read before the write plus the written fields (a column
	 *                           another writer changed meanwhile may be stale here, not in storage);
	 *                           null when the contract does not exist (also when it is deleted
	 *                           before the write).
	 * @throws InvalidArgumentException If a value is invalid.
	 */
	public static function update( int $id, array $args ): ?ContractView {
		$filtered_args = self::filter_known_keys( __METHOD__, $args, self::CONTRACT_KEYS );

		$repository = new ContractRepository();
		$contract   = $repository->find( $id );
		if ( null === $contract ) {
			return null;
		}

		try {
			self::apply( __METHOD__, $contract, $filtered_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		$fields = array_keys( $filtered_args );
		if ( array() === $fields ) {
			return ContractView::from_contract( $contract, true );
		}

		if ( ! $repository->update_fields( $contract, $fields ) ) {
			return null;
		}

		return ContractView::from_contract( $contract, true );
	}

	/**
	 * Append a cycle to a contract's chain `(contract_id, kind)`.
	 *
	 * The first public form of the cycle append tool: append-if-absent on the chain's
	 * unique positions. `starts_at_gmt`, `ends_at_gmt` and `currency` are required.
	 * `status` (a registered cycle status) defaults to `pending`; `kind` defaults to
	 * `billing`; `sequence_no` defaults to the head's plus one; `count` defaults to
	 * MAX(count) + 1 in the chain (pass `null` for a non-counting cycle); `expected_total`
	 * defaults to 0; `order_id` is optional. The contract is not read: appending to an
	 * unknown contract id is a caller error. Unknown keys raise a `_doing_it_wrong()`
	 * notice and are ignored.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param array<string, mixed> $args        Cycle fields.
	 * @return CycleView The appended cycle.
	 * @throws InvalidArgumentException If a required key is missing or a value is invalid.
	 * @throws DomainException If the chain position is already taken.
	 */
	public static function add_cycle( int $contract_id, array $args ): CycleView {
		$filtered_args = self::filter_known_keys( __METHOD__, $args, self::CYCLE_KEYS );

		$status = $filtered_args['status'] ?? null;
		if ( null !== $status && ! is_string( $status ) ) {
			throw new InvalidArgumentException( 'Contracts: "status" must be a string.' );
		}

		$kind = $filtered_args['kind'] ?? Cycle::KIND_BILLING;
		if ( ! is_string( $kind ) ) {
			throw new InvalidArgumentException( 'Contracts: "kind" must be a string.' );
		}

		$starts_at = self::nullable_date( 'starts_at_gmt', $filtered_args['starts_at_gmt'] ?? null );
		$ends_at   = self::nullable_date( 'ends_at_gmt', $filtered_args['ends_at_gmt'] ?? null );
		$currency  = self::currency( $filtered_args['currency'] ?? null );

		$repository  = new ContractRepository();
		$head        = $repository->find_chain_head( $contract_id, $kind );
		$sequence_no = array_key_exists( 'sequence_no', $filtered_args )
			? self::nullable_id( 'sequence_no', $filtered_args['sequence_no'] )
			: ( null === $head ? 1 : $head->get_sequence_no() + 1 );
		$count       = array_key_exists( 'count', $filtered_args )
			? self::nullable_id( 'count', $filtered_args['count'] )
			: ( $repository->max_count( $contract_id, $kind ) ?? 0 ) + 1;

		$cycle_args = array(
			'contract_id'    => $contract_id,
			'kind'           => $kind,
			'sequence_no'    => $sequence_no,
			'count'          => $count,
			'status'         => $status,
			'starts_at_gmt'  => $starts_at,
			'ends_at_gmt'    => $ends_at,
			'expected_total' => self::money( 'expected_total', $filtered_args['expected_total'] ?? null ),
			'currency'       => $currency,
			'order_id'       => self::nullable_id( 'order_id', $filtered_args['order_id'] ?? null ),
		);

		try {
			$cycle = Cycle::create( $cycle_args );
		} catch ( DomainException $e ) {
			throw new InvalidArgumentException( $e->getMessage(), 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the entity message is not output.
		}

		try {
			$repository->append_cycle( $cycle, $head );
		} catch ( DuplicateCycleException $e ) {
			throw new DomainException( 'Contracts: the cycle position already exists.' );
		}

		return CycleView::from_cycle( $cycle );
	}

	/**
	 * Add a meta value to a contract, like `add_post_meta()`. A key may hold several values.
	 *
	 * @param int    $id     Contract id.
	 * @param string $key    Meta key.
	 * @param mixed  $value  Meta value; serialized when not scalar.
	 * @param bool   $unique When true, add nothing if the key already exists. Advisory: checked
	 *                       before the insert with no unique index, so concurrent adds can both write.
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
	 * Update a contract's meta values for `$key`, like `update_post_meta()`: adds the key
	 * when absent, else rewrites every value, or only the values equal to `$prev_value`.
	 * The absent-key check runs before the write with no unique index, so it is not a lock.
	 *
	 * @param int    $id         Contract id.
	 * @param string $key        Meta key.
	 * @param mixed  $value      New value; serialized when not scalar.
	 * @param mixed  $prev_value Only update values equal to this; null updates all. Any other
	 *                           value ('' and false included) matches literally.
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
	 * Delete a contract's meta values for `$key`, like `delete_post_meta()`.
	 *
	 * @param int    $id    Contract id.
	 * @param string $key   Meta key.
	 * @param mixed  $value Only delete values equal to this; null deletes every value for the key.
	 *                      Any other value ('' and false included) matches literally.
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

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- the DomainException comes from the entity setters, not a throw in this method.
	/**
	 * Validate the caller's field shapes and apply them to a contract through its setters,
	 * which enforce the entity invariants. Nothing is written to storage; an invalid value
	 * throws before any write.
	 *
	 * @param string               $method   Public facade method, for usage notices.
	 * @param Contract             $contract Contract to change.
	 * @param array<string, mixed> $args     Caller fields (known keys only, no `extension_slug`).
	 * @throws InvalidArgumentException If a value has the wrong shape.
	 * @throws DomainException If a value breaks an entity invariant (from the entity setters).
	 */
	private static function apply( string $method, Contract $contract, array $args ): void {
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
					$contract->set_status( self::string( $key, $value ) );
					break;
				case 'schedule_source':
					$contract->set_schedule_source( self::string( $key, $value ) );
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
					$contract->set_items( self::items( $method, $value ) );
					break;
				case 'addresses':
					$contract->set_addresses( self::addresses( $method, $value ) );
					break;
			}
		}

		$contract->set_payment_instrument( new InstrumentRef( $token_id, $gateway, $title ) );
		self::assert_money_has_currency( $contract, $args );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Keep the keys in `$allowed`; each other key raises a `_doing_it_wrong()` notice and is dropped.
	 *
	 * @param string                   $method  Public facade method, for the notice.
	 * @param array<int|string, mixed> $args    Caller arguments.
	 * @param array<string, true>      $allowed Accepted keys, as a key map.
	 * @param string                   $what    What the keys belong to, for the notice.
	 * @return array<string, mixed> The arguments with known keys only.
	 */
	private static function filter_known_keys( string $method, array $args, array $allowed, string $what = 'key' ): array {
		$filtered = array();
		foreach ( $args as $key => $value ) {
			if ( isset( $allowed[ $key ] ) ) {
				$filtered[ (string) $key ] = $value;
				continue;
			}

			_doing_it_wrong(
				esc_html( $method ),
				sprintf( 'Contracts: unknown %s "%s" ignored.', esc_html( $what ), esc_html( (string) $key ) ),
				'0.0.1'
			);
		}

		return $filtered;
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
	 * Validate a string.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not a string.
	 */
	private static function string( string $key, $value ): string {
		if ( ! is_string( $value ) ) {
			throw new InvalidArgumentException( sprintf( 'Contracts: "%s" must be a string.', esc_html( $key ) ) );
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
	 * Validate an item row list; unknown row keys are dropped with a notice.
	 *
	 * @param string $method Public facade method, for usage notices.
	 * @param mixed  $value  Caller value.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidArgumentException If the value is not a list of item rows.
	 */
	private static function items( string $method, $value ): array {
		$allowed = array_fill_keys( Contract::ITEM_FIELDS, true );
		$rows    = array();
		foreach ( self::item_rows( 'items', $value ) as $row ) {
			$rows[] = self::filter_known_keys( $method, $row, $allowed, 'item key' );
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
	 * Validate an addresses map keyed `billing` / `shipping`; unknown address keys are
	 * dropped with a notice.
	 *
	 * @param string $method Public facade method, for usage notices.
	 * @param mixed  $value  Caller value.
	 * @return array<string, array<string, mixed>>
	 * @throws InvalidArgumentException If the map is not keyed `billing` / `shipping` with array values.
	 */
	private static function addresses( string $method, $value ): array {
		$allowed = array_fill_keys( Contract::ADDRESS_FIELDS, true );
		if ( ! is_array( $value ) ) {
			throw new InvalidArgumentException( 'Contracts: "addresses" must be an array keyed "billing" / "shipping".' );
		}

		$addresses = array();
		foreach ( $value as $type => $address ) {
			if ( ! in_array( $type, array( Contract::ADDRESS_BILLING, Contract::ADDRESS_SHIPPING ), true ) || ! is_array( $address ) ) {
				throw new InvalidArgumentException( 'Contracts: "addresses" must be an array keyed "billing" / "shipping" with array values.' );
			}

			$addresses[ $type ] = self::filter_known_keys( $method, $address, $allowed, 'address key' );
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
