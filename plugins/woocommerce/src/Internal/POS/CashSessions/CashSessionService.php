<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exceptions become REST error data, never HTML output.

use InvalidArgumentException;
use OverflowException;
use RuntimeException;
use Throwable;

/**
 * Business rules for POS cash sessions: opening, cash movements, drawer events and closing.
 *
 * Every write carries a client request ID stored on the record it creates, so a retry with the same
 * payload returns the original record and a retry with different data is rejected. Movement writes bump
 * the session revision on an open session before inserting, and close only updates an open session at
 * the revision it computed totals for, so a movement is either included in the close or rejected.
 *
 * Session records returned here have the shape array{row: array, totals: array<string, int>}, with amounts
 * in minor units; CashSessionsSchema turns them into responses.
 *
 * @since 11.3.0
 */
class CashSessionService {

	/**
	 * Storage.
	 *
	 * @var CashSessionsDataStore
	 */
	private CashSessionsDataStore $data_store;

	/**
	 * Order and refund validation.
	 *
	 * @var CashSourceResolver
	 */
	private CashSourceResolver $source_resolver;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param CashSessionsDataStore $data_store      Storage.
	 * @param CashSourceResolver    $source_resolver Order and refund validation.
	 */
	final public function init( CashSessionsDataStore $data_store, CashSourceResolver $source_resolver ): void {
		$this->data_store      = $data_store;
		$this->source_resolver = $source_resolver;
	}

	/**
	 * Open a session with its opening float, or replay an earlier open request.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $params request_id, device_id, opening_amount and optional drawer_id.
	 * @return array{created: bool, session: array<string, mixed>}
	 * @throws CashSessionException When the request cannot be completed.
	 * @throws RuntimeException When the opening float cannot be stored.
	 * @throws Throwable Rethrown after rolling back.
	 */
	public function open_session( array $params ): array {
		$request_id = strtolower( (string) $params['request_id'] );
		$device_id  = (string) $params['device_id'];
		$drawer     = isset( $params['drawer_id'] ) ? $this->normalize_drawer( (string) $params['drawer_id'] ) : null;
		$hash       = self::payload_hash(
			array(
				'device_id'      => $device_id,
				'opening_amount' => $this->canonical_amount( (string) $params['opening_amount'] ),
				'drawer'         => null === $drawer ? null : DrawerName::key( $drawer ),
			)
		);

		$existing = $this->data_store->find_session_by_open_request( $request_id );
		if ( null !== $existing ) {
			return $this->replay_open( $existing, $hash );
		}

		$precision = wc_get_price_decimals();
		if ( $precision < 0 || $precision > CashMoney::MAX_PRECISION ) {
			throw CashSessionException::invalid(
				'woocommerce_rest_cash_unsupported_precision',
				__( 'The store currency precision is not supported for cash sessions.', 'woocommerce' )
			);
		}
		$opening_amount = $this->parse_amount( (string) $params['opening_amount'], $precision );

		$open = $this->data_store->find_open_session_by_device( $device_id );
		if ( null !== $open ) {
			throw $this->already_open( (int) $open['id'] );
		}

		$actor = $this->get_actor();
		$now   = $this->now_gmt();

		$this->start_transaction();
		try {
			$session_id = $this->data_store->insert_session(
				array(
					'device_id'          => $device_id,
					'drawer_name'        => $drawer,
					'drawer_key'         => null === $drawer ? null : DrawerName::key( $drawer ),
					'currency'           => get_woocommerce_currency(),
					'currency_precision' => $precision,
					'opened_by'          => $actor['id'],
					'opened_by_name'     => $actor['name'],
					'date_created_gmt'   => $now,
					'open_request_id'    => $request_id,
					'open_request_hash'  => $hash,
				)
			);
			if ( null === $session_id ) {
				$this->rollback();
				return $this->resolve_open_conflict( $request_id, $hash, $device_id );
			}

			$movement_id = $this->data_store->insert_movement(
				array(
					'session_id'       => $session_id,
					'type'             => CashMovementType::OPENING_FLOAT,
					'amount'           => $opening_amount,
					'reason'           => '',
					'created_by'       => $actor['id'],
					'created_by_name'  => $actor['name'],
					'occurred_at_gmt'  => $now,
					'date_created_gmt' => $now,
				)
			);
			if ( null === $movement_id ) {
				throw new RuntimeException( 'Could not record the opening float.' );
			}
			$this->commit();
		} catch ( Throwable $e ) {
			$this->rollback();
			throw $e;
		}

		return array(
			'created' => true,
			'session' => $this->get_session( $session_id ),
		);
	}

	/**
	 * Get a session with its current totals.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @return array<string, mixed>
	 * @throws CashSessionException When the session does not exist.
	 */
	public function get_session( int $session_id ): array {
		$row = $this->data_store->get_session( $session_id );
		if ( null === $row ) {
			throw CashSessionException::session_not_found();
		}
		return $this->with_totals( array( $row ) )[0];
	}

	/**
	 * List sessions, newest first.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $filters  Optional device_id, drawer_id and status.
	 * @param int                  $page     1-based page.
	 * @param int                  $per_page Page size.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 * @throws CashSessionException When a filter is invalid.
	 */
	public function list_sessions( array $filters, int $page, int $per_page ): array {
		$query = array();
		if ( isset( $filters['device_id'] ) ) {
			$query['device_id'] = (string) $filters['device_id'];
		}
		if ( isset( $filters['drawer_id'] ) ) {
			$query['drawer_key'] = DrawerName::key( $this->normalize_drawer( (string) $filters['drawer_id'] ) );
		}
		if ( isset( $filters['status'] ) ) {
			$query['status'] = (string) $filters['status'];
		}

		$result = $this->data_store->query_sessions( $query, $page, $per_page );

		return array(
			'items' => $this->with_totals( $result['rows'] ),
			'total' => $result['total'],
		);
	}

	/**
	 * Close a session with the cashier count, or replay an earlier close request.
	 *
	 * @since 11.3.0
	 *
	 * @param int                  $session_id Session ID.
	 * @param array<string, mixed> $params     request_id, expected_revision, counted_amount and optional note.
	 * @return array<string, mixed> Closed session.
	 * @throws CashSessionException When the request cannot be completed.
	 * @throws Throwable Rethrown after rolling back.
	 */
	public function close_session( int $session_id, array $params ): array {
		$request_id        = strtolower( (string) $params['request_id'] );
		$expected_revision = (int) $params['expected_revision'];
		$note              = sanitize_textarea_field( (string) ( $params['note'] ?? '' ) );
		$hash              = self::payload_hash(
			array(
				'expected_revision' => $expected_revision,
				'counted_amount'    => $this->canonical_amount( (string) $params['counted_amount'] ),
				'note'              => $note,
			)
		);

		// Replay lookup runs before the transaction so that no read snapshot predates the row lock below.
		$row = $this->data_store->get_session( $session_id );
		if ( null === $row ) {
			throw CashSessionException::session_not_found();
		}
		if ( $request_id === $row['close_request_id'] ) {
			return $this->replay_close( $row, $hash );
		}
		if ( CashSessionStatus::OPEN !== $row['status'] ) {
			throw CashSessionException::session_closed( $session_id );
		}

		$precision      = (int) $row['currency_precision'];
		$counted_amount = $this->parse_amount( (string) $params['counted_amount'], $precision );
		$actor          = $this->get_actor();

		$this->start_transaction();
		try {
			$locked = $this->data_store->lock_session( $session_id );
			if ( null === $locked ) {
				throw CashSessionException::session_not_found();
			}
			if ( $request_id === $locked['close_request_id'] ) {
				$this->rollback();
				return $this->replay_close( $locked, $hash );
			}
			if ( CashSessionStatus::OPEN !== $locked['status'] ) {
				throw CashSessionException::session_closed( $session_id );
			}
			if ( (int) $locked['revision'] !== $expected_revision ) {
				throw $this->revision_conflict( $session_id, (int) $locked['revision'] );
			}

			$totals   = $this->compute_totals( $this->data_store->get_movement_sums( array( $session_id ) )[ $session_id ] ?? array() );
			$expected = $totals['expected'];
			$closed   = $this->data_store->close_session(
				$session_id,
				$expected_revision,
				array(
					'expected_amount'    => $expected,
					'counted_amount'     => $counted_amount,
					'variance'           => CashMoney::add( $counted_amount, -$expected ),
					'note'               => $note,
					'closed_by'          => $actor['id'],
					'closed_by_name'     => $actor['name'],
					'date_closed_gmt'    => $this->now_gmt(),
					'close_request_id'   => $request_id,
					'close_request_hash' => $hash,
				)
			);
			if ( ! $closed ) {
				$current = $this->data_store->get_session( $session_id );
				if ( null !== $current && CashSessionStatus::OPEN === $current['status'] ) {
					throw $this->revision_conflict( $session_id, (int) $current['revision'] );
				}
				throw CashSessionException::session_closed( $session_id );
			}
			$this->commit();
		} catch ( Throwable $e ) {
			$this->rollback();
			throw $e;
		}

		return $this->get_session( $session_id );
	}

	/**
	 * Record a cash movement, or replay an earlier request.
	 *
	 * Cash sales and refunds reference an existing order or refund; the amount and time come from it.
	 * Paid in and paid out carry a positive amount and a reason.
	 *
	 * @since 11.3.0
	 *
	 * @param int                  $session_id Session ID.
	 * @param array<string, mixed> $params     Movement request fields.
	 * @return array{created: bool, movement: array<string, mixed>, session: array<string, mixed>}
	 * @throws CashSessionException When the request cannot be completed.
	 * @throws Throwable Rethrown after rolling back.
	 */
	public function record_movement( int $session_id, array $params ): array {
		$request_id = strtolower( (string) $params['request_id'] );
		$type       = (string) $params['type'];
		$session    = $this->data_store->get_session( $session_id );
		if ( null === $session ) {
			throw CashSessionException::session_not_found();
		}

		$this->check_movement_fields( $type, $params );
		$reason = isset( $params['reason'] ) ? trim( sanitize_text_field( (string) $params['reason'] ) ) : '';
		$hash   = self::payload_hash(
			array(
				'type'        => $type,
				'amount'      => isset( $params['amount'] ) ? $this->canonical_amount( (string) $params['amount'] ) : null,
				'reason'      => $reason,
				'order_id'    => isset( $params['order_id'] ) ? (int) $params['order_id'] : null,
				'refund_id'   => isset( $params['refund_id'] ) ? (int) $params['refund_id'] : null,
				'occurred_at' => isset( $params['occurred_at'] ) ? $this->parse_timestamp( (string) $params['occurred_at'] ) : null,
			)
		);

		$existing = $this->data_store->find_movement_by_request( $session_id, $request_id );
		if ( null !== $existing ) {
			return $this->replay_movement( $existing, $hash );
		}
		if ( CashSessionStatus::OPEN !== $session['status'] ) {
			throw CashSessionException::session_closed( $session_id );
		}

		$row = $this->build_movement_row( $session, $type, $params, $reason );
		if ( null !== $row['source_key'] ) {
			$recorded = $this->data_store->find_movement_by_source( $row['source_key'] );
			if ( null !== $recorded ) {
				throw $this->source_already_recorded( $recorded );
			}
		}
		$row['session_id']   = $session_id;
		$row['request_id']   = $request_id;
		$row['request_hash'] = $hash;

		$this->start_transaction();
		try {
			// The revision bump is the first statement: it locks the session row and fails once the session is closed.
			if ( ! $this->data_store->bump_revision( $session_id ) ) {
				$this->rollback();
				return $this->resolve_movement_conflict( $session_id, $request_id, $hash, null );
			}

			$totals = $this->data_store->get_movement_sums( array( $session_id ) )[ $session_id ] ?? array();
			$this->check_total_limit( $totals, $type, (int) $row['amount'] );

			$movement_id = $this->data_store->insert_movement( $row );
			if ( null === $movement_id ) {
				$this->rollback();
				return $this->resolve_movement_conflict( $session_id, $request_id, $hash, $row['source_key'] );
			}
			$this->commit();
		} catch ( Throwable $e ) {
			$this->rollback();
			throw $e;
		}

		return array(
			'created'  => true,
			'movement' => $this->get_movement_row( $movement_id ),
			'session'  => $this->get_session( $session_id ),
		);
	}

	/**
	 * List the movements of a session, oldest first.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @param int $page       1-based page.
	 * @param int $per_page   Page size.
	 * @return array{items: array<int, array<string, mixed>>, total: int, precision: int}
	 * @throws CashSessionException When the session does not exist.
	 */
	public function list_movements( int $session_id, int $page, int $per_page ): array {
		$session = $this->get_session_row( $session_id );
		$result  = $this->data_store->query_movements( $session_id, $page, $per_page );

		return array(
			'items'     => $result['rows'],
			'total'     => $result['total'],
			'precision' => (int) $session['currency_precision'],
		);
	}

	/**
	 * Record a drawer audit event, or replay an earlier request. Events never change cash totals.
	 *
	 * @since 11.3.0
	 *
	 * @param int                  $session_id Session ID.
	 * @param array<string, mixed> $params     Event request fields.
	 * @return array{created: bool, event: array<string, mixed>}
	 * @throws CashSessionException When the request cannot be completed.
	 * @throws RuntimeException When the stored event cannot be read.
	 * @throws Throwable Rethrown after rolling back.
	 */
	public function record_drawer_event( int $session_id, array $params ): array {
		$request_id  = strtolower( (string) $params['request_id'] );
		$session     = $this->get_session_row( $session_id );
		$drawer      = isset( $params['drawer_id'] ) ? $this->normalize_drawer( (string) $params['drawer_id'] ) : null;
		$occurred_at = $this->parse_timestamp( (string) $params['occurred_at'] );
		$references  = array(
			'order_id'    => isset( $params['order_id'] ) ? (int) $params['order_id'] : null,
			'refund_id'   => isset( $params['refund_id'] ) ? (int) $params['refund_id'] : null,
			'movement_id' => isset( $params['movement_id'] ) ? (int) $params['movement_id'] : null,
		);
		$correlation = isset( $params['correlation_id'] ) ? strtolower( (string) $params['correlation_id'] ) : null;
		$hash        = self::payload_hash(
			array_merge(
				$references,
				array(
					'type'           => (string) $params['type'],
					'reason'         => (string) $params['reason'],
					'occurred_at'    => $occurred_at,
					'correlation_id' => $correlation,
					'drawer'         => null === $drawer ? $session['drawer_key'] : DrawerName::key( $drawer ),
				)
			)
		);

		$existing = $this->data_store->find_drawer_event_by_request( $session_id, $request_id );
		if ( null !== $existing ) {
			if ( ! hash_equals( (string) $existing['request_hash'], $hash ) ) {
				throw CashSessionException::request_conflict();
			}
			return array(
				'created' => false,
				'event'   => $existing,
			);
		}
		if ( CashSessionStatus::OPEN !== $session['status'] ) {
			throw CashSessionException::session_closed( $session_id );
		}

		$this->check_event_drawer( $session, $drawer );
		$this->source_resolver->check_references( $references['order_id'], $references['refund_id'] );
		if ( null !== $references['movement_id'] ) {
			$movement = $this->data_store->get_movement( $references['movement_id'] );
			if ( null === $movement || (int) $movement['session_id'] !== $session_id ) {
				throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_reference', __( 'The movement does not belong to this cash session.', 'woocommerce' ) );
			}
		}

		$actor = $this->get_actor();
		$row   = array_merge(
			$references,
			array(
				'session_id'       => $session_id,
				'type'             => (string) $params['type'],
				'reason'           => (string) $params['reason'],
				'drawer_name'      => (string) $session['drawer_name'],
				'correlation_id'   => $correlation,
				'occurred_at_gmt'  => $occurred_at,
				'created_by'       => $actor['id'],
				'created_by_name'  => $actor['name'],
				'date_created_gmt' => $this->now_gmt(),
				'request_id'       => $request_id,
				'request_hash'     => $hash,
			)
		);

		$this->start_transaction();
		try {
			$locked = $this->data_store->lock_session( $session_id );
			if ( null === $locked || CashSessionStatus::OPEN !== $locked['status'] ) {
				throw CashSessionException::session_closed( $session_id );
			}
			$event_id = $this->data_store->insert_drawer_event( $row );
			if ( null === $event_id ) {
				$this->rollback();
				$winner = $this->data_store->find_drawer_event_by_request( $session_id, $request_id );
				if ( null === $winner ) {
					throw CashSessionException::request_in_progress();
				}
				if ( ! hash_equals( (string) $winner['request_hash'], $hash ) ) {
					throw CashSessionException::request_conflict();
				}
				return array(
					'created' => false,
					'event'   => $winner,
				);
			}
			$this->commit();
		} catch ( Throwable $e ) {
			$this->rollback();
			throw $e;
		}

		$event = $this->data_store->get_drawer_event( $event_id );
		if ( null === $event ) {
			throw new RuntimeException( 'Could not read the recorded drawer event.' );
		}
		return array(
			'created' => true,
			'event'   => $event,
		);
	}

	/**
	 * List the drawer events of a session, oldest first.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @param int $page       1-based page.
	 * @param int $per_page   Page size.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 * @throws CashSessionException When the session does not exist.
	 */
	public function list_drawer_events( int $session_id, int $page, int $per_page ): array {
		$this->get_session_row( $session_id );
		$result = $this->data_store->query_drawer_events( $session_id, $page, $per_page );

		return array(
			'items' => $result['rows'],
			'total' => $result['total'],
		);
	}

	/**
	 * Compute totals and expected cash from movement sums.
	 *
	 * Expected cash = opening float + cash sales - cash refunds + paid in - paid out.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, int> $sums Movement type => minor units.
	 * @return array<string, int> Totals per movement type plus 'expected'.
	 * @throws OverflowException When a total is out of range.
	 */
	public static function compute_totals( array $sums ): array {
		$totals = array();
		foreach ( CashMovementType::get_all() as $type ) {
			$totals[ $type ] = (int) ( $sums[ $type ] ?? 0 );
		}

		$expected = $totals[ CashMovementType::OPENING_FLOAT ];
		foreach ( array( CashMovementType::CASH_SALE, CashMovementType::PAID_IN ) as $type ) {
			$expected = CashMoney::add( $expected, $totals[ $type ] );
		}
		foreach ( array( CashMovementType::CASH_REFUND, CashMovementType::PAID_OUT ) as $type ) {
			$expected = CashMoney::add( $expected, -$totals[ $type ] );
		}
		$totals['expected'] = $expected;

		return $totals;
	}

	/**
	 * Build the stored row for a new movement, validating the amount or source.
	 *
	 * @param array<string, mixed> $session Session row.
	 * @param string               $type    Movement type.
	 * @param array<string, mixed> $params  Request fields.
	 * @param string               $reason  Sanitized reason.
	 * @return array<string, mixed>
	 * @throws CashSessionException When the amount, reason, time or source is invalid.
	 */
	private function build_movement_row( array $session, string $type, array $params, string $reason ): array {
		$currency  = (string) $session['currency'];
		$precision = (int) $session['currency_precision'];
		$actor     = $this->get_actor();
		$now       = $this->now_gmt();

		$row = array(
			'type'             => $type,
			'reason'           => $reason,
			'order_id'         => null,
			'refund_id'        => null,
			'source_key'       => null,
			'created_by'       => $actor['id'],
			'created_by_name'  => $actor['name'],
			'date_created_gmt' => $now,
		);

		if ( CashMovementType::CASH_SALE === $type || CashMovementType::CASH_REFUND === $type ) {
			$order_id = (int) $params['order_id'];
			$source   = CashMovementType::CASH_SALE === $type
				? $this->source_resolver->resolve_sale( $order_id, $currency, $precision )
				: $this->source_resolver->resolve_refund( $order_id, (int) $params['refund_id'], $currency, $precision );

			return array_merge(
				$row,
				array(
					'amount'          => $source['amount'],
					'order_id'        => $order_id,
					'refund_id'       => CashMovementType::CASH_REFUND === $type ? (int) $params['refund_id'] : null,
					'source_key'      => $source['source_key'],
					'occurred_at_gmt' => $source['occurred_at_gmt'],
				)
			);
		}

		$amount = $this->parse_amount( (string) $params['amount'], $precision );
		if ( $amount <= 0 ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_amount', __( 'The amount must be greater than zero.', 'woocommerce' ) );
		}
		if ( '' === $reason ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_movement', __( 'A reason is required.', 'woocommerce' ) );
		}

		return array_merge(
			$row,
			array(
				'amount'          => $amount,
				'occurred_at_gmt' => isset( $params['occurred_at'] ) ? $this->parse_timestamp( (string) $params['occurred_at'] ) : $now,
			)
		);
	}

	/**
	 * Check which fields a movement type requires or forbids.
	 *
	 * @param string               $type   Movement type.
	 * @param array<string, mixed> $params Request fields.
	 * @throws CashSessionException When a field is missing or not allowed.
	 */
	private function check_movement_fields( string $type, array $params ): void {
		$rules = array(
			CashMovementType::CASH_SALE   => array(
				'required'  => array( 'order_id' ),
				'forbidden' => array( 'amount', 'refund_id', 'occurred_at' ),
			),
			CashMovementType::CASH_REFUND => array(
				'required'  => array( 'order_id', 'refund_id' ),
				'forbidden' => array( 'amount', 'occurred_at' ),
			),
			CashMovementType::PAID_IN     => array(
				'required'  => array( 'amount', 'reason' ),
				'forbidden' => array( 'order_id', 'refund_id' ),
			),
			CashMovementType::PAID_OUT    => array(
				'required'  => array( 'amount', 'reason' ),
				'forbidden' => array( 'order_id', 'refund_id' ),
			),
		);
		if ( ! isset( $rules[ $type ] ) ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_movement', __( 'This movement type cannot be recorded.', 'woocommerce' ) );
		}

		foreach ( $rules[ $type ]['required'] as $field ) {
			if ( ! isset( $params[ $field ] ) ) {
				/* translators: 1: field name, 2: movement type. */
				throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_movement', sprintf( __( '%1$s is required for %2$s movements.', 'woocommerce' ), $field, $type ) );
			}
		}
		foreach ( $rules[ $type ]['forbidden'] as $field ) {
			if ( isset( $params[ $field ] ) ) {
				/* translators: 1: field name, 2: movement type. */
				throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_movement', sprintf( __( '%1$s is not allowed for %2$s movements.', 'woocommerce' ), $field, $type ) );
			}
		}
	}

	/**
	 * Check that a new movement keeps every total within the supported range.
	 *
	 * @param array<string, int> $sums   Current sums by type.
	 * @param string             $type   New movement type.
	 * @param int                $amount New movement amount.
	 * @throws CashSessionException When a total would be out of range.
	 */
	private function check_total_limit( array $sums, string $type, int $amount ): void {
		try {
			$sums[ $type ] = CashMoney::add( (int) ( $sums[ $type ] ?? 0 ), $amount );
			self::compute_totals( $sums );
		} catch ( OverflowException $e ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_amount', __( 'The cash session total would be too large.', 'woocommerce' ) );
		}
	}

	/**
	 * Check the drawer of a drawer event against the session binding.
	 *
	 * @param array<string, mixed> $session Session row.
	 * @param string|null          $drawer  Normalized drawer name from the request.
	 * @throws CashSessionException When no drawer is known or the names differ.
	 */
	private function check_event_drawer( array $session, ?string $drawer ): void {
		$bound_key = null === $session['drawer_key'] ? null : (string) $session['drawer_key'];

		if ( null === $drawer && null === $bound_key ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_drawer_required', __( 'This cash session has no drawer.', 'woocommerce' ) );
		}
		if ( null !== $drawer && DrawerName::key( $drawer ) !== $bound_key ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_drawer_mismatch', __( 'The drawer does not match the cash session drawer.', 'woocommerce' ) );
		}
	}

	/**
	 * Return a replayed open, or reject a changed payload.
	 *
	 * @param array<string, mixed> $row  Session row.
	 * @param string               $hash Payload hash of the retry.
	 * @return array{created: bool, session: array<string, mixed>}
	 * @throws CashSessionException When the payload differs.
	 */
	private function replay_open( array $row, string $hash ): array {
		if ( ! hash_equals( (string) $row['open_request_hash'], $hash ) ) {
			throw CashSessionException::request_conflict();
		}
		return array(
			'created' => false,
			'session' => $this->with_totals( array( $row ) )[0],
		);
	}

	/**
	 * Decide why an open insert failed: a concurrent retry, an open session on the device, or a race in progress.
	 *
	 * @param string $request_id Request ID.
	 * @param string $hash       Payload hash.
	 * @param string $device_id  Device ID.
	 * @return array{created: bool, session: array<string, mixed>}
	 * @throws CashSessionException When the open cannot be replayed.
	 */
	private function resolve_open_conflict( string $request_id, string $hash, string $device_id ): array {
		$existing = $this->data_store->find_session_by_open_request( $request_id );
		if ( null !== $existing ) {
			return $this->replay_open( $existing, $hash );
		}
		$open = $this->data_store->find_open_session_by_device( $device_id );
		if ( null !== $open ) {
			throw $this->already_open( (int) $open['id'] );
		}
		throw CashSessionException::request_in_progress();
	}

	/**
	 * Return a replayed close, or reject a changed payload.
	 *
	 * @param array<string, mixed> $row  Session row.
	 * @param string               $hash Payload hash of the retry.
	 * @return array<string, mixed>
	 * @throws CashSessionException When the payload differs.
	 */
	private function replay_close( array $row, string $hash ): array {
		if ( ! hash_equals( (string) $row['close_request_hash'], $hash ) ) {
			throw CashSessionException::request_conflict();
		}
		return $this->with_totals( array( $row ) )[0];
	}

	/**
	 * Return a replayed movement, or reject a changed payload.
	 *
	 * @param array<string, mixed> $row  Movement row.
	 * @param string               $hash Payload hash of the retry.
	 * @return array{created: bool, movement: array<string, mixed>, session: array<string, mixed>}
	 * @throws CashSessionException When the payload differs.
	 */
	private function replay_movement( array $row, string $hash ): array {
		if ( ! hash_equals( (string) $row['request_hash'], $hash ) ) {
			throw CashSessionException::request_conflict();
		}
		return array(
			'created'  => false,
			'movement' => $row,
			'session'  => $this->get_session( (int) $row['session_id'] ),
		);
	}

	/**
	 * Decide why a movement write failed after the pre-checks passed.
	 *
	 * @param int         $session_id Session ID.
	 * @param string      $request_id Request ID.
	 * @param string      $hash       Payload hash.
	 * @param string|null $source_key Source key of the movement, if any.
	 * @return array{created: bool, movement: array<string, mixed>, session: array<string, mixed>}
	 * @throws CashSessionException Describing the conflict.
	 */
	private function resolve_movement_conflict( int $session_id, string $request_id, string $hash, ?string $source_key ): array {
		$existing = $this->data_store->find_movement_by_request( $session_id, $request_id );
		if ( null !== $existing ) {
			return $this->replay_movement( $existing, $hash );
		}
		if ( null !== $source_key ) {
			$recorded = $this->data_store->find_movement_by_source( $source_key );
			if ( null !== $recorded ) {
				throw $this->source_already_recorded( $recorded );
			}
		}
		$session = $this->data_store->get_session( $session_id );
		if ( null !== $session && CashSessionStatus::OPEN !== $session['status'] ) {
			throw CashSessionException::session_closed( $session_id );
		}
		throw CashSessionException::request_in_progress();
	}

	/**
	 * Attach totals to session rows, reading all movement sums in one query.
	 *
	 * @param array<int, array<string, mixed>> $rows Session rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function with_totals( array $rows ): array {
		$sums = $this->data_store->get_movement_sums( array_column( $rows, 'id' ) );

		return array_map(
			fn( array $row ) => array(
				'row'    => $row,
				'totals' => self::compute_totals( $sums[ (int) $row['id'] ] ?? array() ),
			),
			$rows
		);
	}

	/**
	 * Read a session row or fail with 404.
	 *
	 * @param int $session_id Session ID.
	 * @return array<string, mixed>
	 * @throws CashSessionException When the session does not exist.
	 */
	private function get_session_row( int $session_id ): array {
		$row = $this->data_store->get_session( $session_id );
		if ( null === $row ) {
			throw CashSessionException::session_not_found();
		}
		return $row;
	}

	/**
	 * Read a movement that was just written.
	 *
	 * @param int $movement_id Movement ID.
	 * @return array<string, mixed>
	 * @throws RuntimeException When it cannot be read.
	 */
	private function get_movement_row( int $movement_id ): array {
		$row = $this->data_store->get_movement( $movement_id );
		if ( null === $row ) {
			throw new RuntimeException( 'Could not read the recorded cash movement.' );
		}
		return $row;
	}

	/**
	 * Parse a client amount at a precision.
	 *
	 * @param string $value     Decimal string.
	 * @param int    $precision Precision.
	 * @return int
	 * @throws CashSessionException When the amount is invalid.
	 */
	private function parse_amount( string $value, int $precision ): int {
		try {
			return CashMoney::parse( $value, $precision );
		} catch ( InvalidArgumentException | OverflowException $e ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_amount', $e->getMessage() );
		}
	}

	/**
	 * Canonical amount for payload hashes.
	 *
	 * @param string $value Decimal string.
	 * @return string
	 * @throws CashSessionException When the amount is malformed.
	 */
	private function canonical_amount( string $value ): string {
		try {
			return CashMoney::canonical( $value );
		} catch ( InvalidArgumentException $e ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_amount', $e->getMessage() );
		}
	}

	/**
	 * Parse an explicit-offset timestamp.
	 *
	 * @param string $value Timestamp.
	 * @return string UTC storage value.
	 * @throws CashSessionException When the timestamp is invalid.
	 */
	private function parse_timestamp( string $value ): string {
		try {
			return CashTimestamp::to_gmt( $value );
		} catch ( InvalidArgumentException $e ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_timestamp', $e->getMessage() );
		}
	}

	/**
	 * Normalize a drawer name.
	 *
	 * @param string $value Drawer name.
	 * @return string
	 * @throws CashSessionException When the name is invalid.
	 */
	private function normalize_drawer( string $value ): string {
		try {
			return DrawerName::normalize( $value );
		} catch ( InvalidArgumentException $e ) {
			throw CashSessionException::invalid( 'woocommerce_rest_cash_invalid_drawer', $e->getMessage() );
		}
	}

	/**
	 * Build the "already open" error.
	 *
	 * @param int $session_id Open session ID.
	 * @return CashSessionException
	 */
	private function already_open( int $session_id ): CashSessionException {
		return new CashSessionException(
			'woocommerce_rest_cash_session_already_open',
			__( 'This device already has an open cash session.', 'woocommerce' ),
			409,
			array( 'session_id' => $session_id )
		);
	}

	/**
	 * Build the stale revision error.
	 *
	 * @param int $session_id       Session ID.
	 * @param int $current_revision Current revision.
	 * @return CashSessionException
	 */
	private function revision_conflict( int $session_id, int $current_revision ): CashSessionException {
		return new CashSessionException(
			'woocommerce_rest_cash_session_revision_conflict',
			__( 'The cash session changed. Review the new totals and count again.', 'woocommerce' ),
			409,
			array(
				'session_id'       => $session_id,
				'current_revision' => $current_revision,
			)
		);
	}

	/**
	 * Build the duplicate source error.
	 *
	 * @param array<string, mixed> $movement The movement that already records the source.
	 * @return CashSessionException
	 */
	private function source_already_recorded( array $movement ): CashSessionException {
		return new CashSessionException(
			'woocommerce_rest_cash_source_already_recorded',
			__( 'This order or refund is already recorded in a cash session.', 'woocommerce' ),
			409,
			array(
				'session_id'  => (int) $movement['session_id'],
				'movement_id' => (int) $movement['id'],
			)
		);
	}

	/**
	 * The authenticated actor with a display name snapshot.
	 *
	 * @return array{id: int, name: string}
	 */
	private function get_actor(): array {
		$user = wp_get_current_user();
		return array(
			'id'   => (int) $user->ID,
			'name' => (string) $user->display_name,
		);
	}

	/**
	 * Current UTC time for storage.
	 *
	 * @return string
	 */
	private function now_gmt(): string {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Hash of a normalized request payload.
	 *
	 * @param array<string, mixed> $payload Normalized payload.
	 * @return string
	 */
	private static function payload_hash( array $payload ): string {
		ksort( $payload );
		return hash( 'sha256', (string) wp_json_encode( $payload ) );
	}

	/**
	 * Start a transaction when the site allows them.
	 */
	private function start_transaction(): void {
		wc_transaction_query( 'start' );
	}

	/**
	 * Commit the transaction.
	 */
	private function commit(): void {
		wc_transaction_query( 'commit' );
	}

	/**
	 * Roll back the transaction. Safe to call when none is active.
	 */
	private function rollback(): void {
		wc_transaction_query( 'rollback' );
	}
}
