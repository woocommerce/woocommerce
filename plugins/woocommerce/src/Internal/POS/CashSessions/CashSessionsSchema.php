<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

/**
 * Route arguments, response schemas and response formatting for the cash sessions REST API.
 *
 * Money is returned as decimal strings at the session precision; timestamps as UTC with a Z suffix.
 *
 * @since 11.3.0
 */
class CashSessionsSchema {

	/**
	 * Pattern for nonnegative decimal amounts; precision is checked against the session.
	 */
	private const MONEY_PATTERN = '^[0-9]+(\.[0-9]+)?$';

	/**
	 * UUID pattern in either case; the WordPress "uuid" format only accepts lowercase, and iOS sends uppercase.
	 */
	private const UUID_PATTERN = '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$';

	/**
	 * Arguments for listing sessions.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_list_sessions_args(): array {
		return array_merge(
			array(
				'device_id' => array(
					'description' => __( 'Only sessions of this device.', 'woocommerce' ),
					'type'        => 'string',
				),
				'drawer_id' => array(
					'description' => __( 'Only sessions of this drawer name, compared case-insensitively.', 'woocommerce' ),
					'type'        => 'string',
				),
				'status'    => array(
					'description' => __( 'Only sessions with this status.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => CashSessionStatus::get_all(),
				),
			),
			$this->get_pagination_args()
		);
	}

	/**
	 * Arguments for opening a session.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_open_session_args(): array {
		return array(
			'request_id'     => $this->request_id_arg(),
			'device_id'      => array(
				'description' => __( 'Identifier of the POS installation.', 'woocommerce' ),
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 128,
				'required'    => true,
			),
			'opening_amount' => $this->money_arg( __( 'Cash in the drawer at opening.', 'woocommerce' ), true ),
			'drawer_id'      => $this->drawer_arg(),
		);
	}

	/**
	 * Arguments for closing a session.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_close_session_args(): array {
		return array(
			'request_id'        => $this->request_id_arg(),
			'expected_revision' => array(
				'description' => __( 'Session revision the cashier reviewed before counting.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'required'    => true,
			),
			'counted_amount'    => $this->money_arg( __( 'Cash counted by the cashier.', 'woocommerce' ), true ),
			'note'              => array(
				'description' => __( 'Optional closing note.', 'woocommerce' ),
				'type'        => 'string',
				'maxLength'   => 2000,
			),
		);
	}

	/**
	 * Arguments for recording a movement.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_create_movement_args(): array {
		return array(
			'request_id'  => $this->request_id_arg(),
			'type'        => array(
				'description' => __( 'Movement type.', 'woocommerce' ),
				'type'        => 'string',
				'enum'        => array( CashMovementType::CASH_SALE, CashMovementType::CASH_REFUND, CashMovementType::PAID_IN, CashMovementType::PAID_OUT ),
				'required'    => true,
			),
			'amount'      => $this->money_arg( __( 'Positive amount for paid in and paid out movements.', 'woocommerce' ), false ),
			'reason'      => array(
				'description' => __( 'Reason for paid in and paid out movements.', 'woocommerce' ),
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 500,
			),
			'order_id'    => $this->id_arg( __( 'Order of a cash sale or cash refund.', 'woocommerce' ) ),
			'refund_id'   => $this->id_arg( __( 'Refund of a cash refund.', 'woocommerce' ) ),
			'occurred_at' => $this->timestamp_arg( __( 'When a paid in or paid out happened.', 'woocommerce' ), false ),
		);
	}

	/**
	 * Arguments for recording a drawer event.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_create_drawer_event_args(): array {
		return array(
			'request_id'     => $this->request_id_arg(),
			'type'           => array(
				'description' => __( 'Drawer event type. open_requested is only the command; opened needs hardware feedback.', 'woocommerce' ),
				'type'        => 'string',
				'enum'        => DrawerEventType::get_all(),
				'required'    => true,
			),
			'reason'         => array(
				'description' => __( 'Why the drawer was opened.', 'woocommerce' ),
				'type'        => 'string',
				'enum'        => DrawerEventReason::get_all(),
				'required'    => true,
			),
			'occurred_at'    => $this->timestamp_arg( __( 'When the event happened on the device.', 'woocommerce' ), true ),
			'drawer_id'      => $this->drawer_arg(),
			'correlation_id' => array(
				'description' => __( 'Groups the request and the observed result of one drawer operation.', 'woocommerce' ),
				'type'        => 'string',
				'pattern'     => self::UUID_PATTERN,
			),
			'order_id'       => $this->id_arg( __( 'Related order.', 'woocommerce' ) ),
			'refund_id'      => $this->id_arg( __( 'Related refund of the order.', 'woocommerce' ) ),
			'movement_id'    => $this->id_arg( __( 'Related movement of this session.', 'woocommerce' ) ),
		);
	}

	/**
	 * Pagination arguments.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_pagination_args(): array {
		return array(
			'page'     => array(
				'description' => __( 'Current page.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'default'     => 1,
			),
			'per_page' => array(
				'description' => __( 'Items per page.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 100,
				'default'     => 10,
			),
		);
	}

	/**
	 * Response schema of a session.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_session_schema(): array {
		$money = array( 'type' => 'string' );
		return $this->object_schema(
			'pos_cash_session',
			array(
				'id'                 => array( 'type' => 'integer' ),
				'device_id'          => array( 'type' => 'string' ),
				'drawer_id'          => array( 'type' => array( 'string', 'null' ) ),
				'status'             => array(
					'type' => 'string',
					'enum' => CashSessionStatus::get_all(),
				),
				'revision'           => array( 'type' => 'integer' ),
				'currency'           => array( 'type' => 'string' ),
				'currency_precision' => array( 'type' => 'integer' ),
				'opening_amount'     => $money,
				'cash_sales_total'   => $money,
				'cash_refunds_total' => $money,
				'paid_in_total'      => $money,
				'paid_out_total'     => $money,
				'expected_amount'    => $money,
				'counted_amount'     => array( 'type' => array( 'string', 'null' ) ),
				'variance'           => array( 'type' => array( 'string', 'null' ) ),
				'note'               => array( 'type' => 'string' ),
				'opened_by'          => array( 'type' => 'integer' ),
				'opened_by_name'     => array( 'type' => 'string' ),
				'closed_by'          => array( 'type' => array( 'integer', 'null' ) ),
				'closed_by_name'     => array( 'type' => array( 'string', 'null' ) ),
				'date_created_gmt'   => array( 'type' => 'string' ),
				'date_closed_gmt'    => array( 'type' => array( 'string', 'null' ) ),
			)
		);
	}

	/**
	 * Response schema of a movement.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_movement_schema(): array {
		return $this->object_schema(
			'pos_cash_movement',
			array(
				'id'               => array( 'type' => 'integer' ),
				'session_id'       => array( 'type' => 'integer' ),
				'type'             => array(
					'type' => 'string',
					'enum' => CashMovementType::get_all(),
				),
				'direction'        => array(
					'type' => 'string',
					'enum' => array( 'in', 'out' ),
				),
				'amount'           => array( 'type' => 'string' ),
				'reason'           => array( 'type' => 'string' ),
				'order_id'         => array( 'type' => array( 'integer', 'null' ) ),
				'refund_id'        => array( 'type' => array( 'integer', 'null' ) ),
				'created_by'       => array( 'type' => 'integer' ),
				'created_by_name'  => array( 'type' => 'string' ),
				'occurred_at'      => array( 'type' => 'string' ),
				'date_created_gmt' => array( 'type' => 'string' ),
			)
		);
	}

	/**
	 * Response schema of a drawer event.
	 *
	 * @since 11.3.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_drawer_event_schema(): array {
		$nullable_id = array( 'type' => array( 'integer', 'null' ) );
		return $this->object_schema(
			'pos_cash_drawer_event',
			array(
				'id'               => array( 'type' => 'integer' ),
				'session_id'       => array( 'type' => 'integer' ),
				'request_id'       => array( 'type' => 'string' ),
				'type'             => array(
					'type' => 'string',
					'enum' => DrawerEventType::get_all(),
				),
				'reason'           => array(
					'type' => 'string',
					'enum' => DrawerEventReason::get_all(),
				),
				'drawer_id'        => array( 'type' => 'string' ),
				'order_id'         => $nullable_id,
				'refund_id'        => $nullable_id,
				'movement_id'      => $nullable_id,
				'correlation_id'   => array( 'type' => array( 'string', 'null' ) ),
				'occurred_at'      => array( 'type' => 'string' ),
				'created_by'       => array( 'type' => 'integer' ),
				'created_by_name'  => array( 'type' => 'string' ),
				'date_created_gmt' => array( 'type' => 'string' ),
			)
		);
	}

	/**
	 * Format a session record from CashSessionService.
	 *
	 * Closed sessions report the expected, counted and variance values frozen at close.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $record array{row: array, totals: array<string, int>}.
	 * @return array<string, mixed>
	 */
	public function format_session( array $record ): array {
		$row       = $record['row'];
		$totals    = $record['totals'];
		$precision = (int) $row['currency_precision'];
		$closed    = CashSessionStatus::CLOSED === $row['status'];
		$money     = fn( $value ) => null === $value ? null : CashMoney::format( (int) $value, $precision );

		return array(
			'id'                 => (int) $row['id'],
			'device_id'          => (string) $row['device_id'],
			'drawer_id'          => null === $row['drawer_name'] ? null : (string) $row['drawer_name'],
			'status'             => (string) $row['status'],
			'revision'           => (int) $row['revision'],
			'currency'           => (string) $row['currency'],
			'currency_precision' => $precision,
			'opening_amount'     => $money( $totals[ CashMovementType::OPENING_FLOAT ] ),
			'cash_sales_total'   => $money( $totals[ CashMovementType::CASH_SALE ] ),
			'cash_refunds_total' => $money( $totals[ CashMovementType::CASH_REFUND ] ),
			'paid_in_total'      => $money( $totals[ CashMovementType::PAID_IN ] ),
			'paid_out_total'     => $money( $totals[ CashMovementType::PAID_OUT ] ),
			'expected_amount'    => $money( $closed ? $row['expected_amount'] : $totals['expected'] ),
			'counted_amount'     => $closed ? $money( $row['counted_amount'] ) : null,
			'variance'           => $closed ? $money( $row['variance'] ) : null,
			'note'               => (string) ( $row['note'] ?? '' ),
			'opened_by'          => (int) $row['opened_by'],
			'opened_by_name'     => (string) $row['opened_by_name'],
			'closed_by'          => null === $row['closed_by'] ? null : (int) $row['closed_by'],
			'closed_by_name'     => null === $row['closed_by_name'] ? null : (string) $row['closed_by_name'],
			'date_created_gmt'   => CashTimestamp::format( (string) $row['date_created_gmt'] ),
			'date_closed_gmt'    => CashTimestamp::format( null === $row['date_closed_gmt'] ? null : (string) $row['date_closed_gmt'] ),
		);
	}

	/**
	 * Format a movement row.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $row       Movement row.
	 * @param int                  $precision Session precision.
	 * @return array<string, mixed>
	 */
	public function format_movement( array $row, int $precision ): array {
		$outgoing = in_array( $row['type'], array( CashMovementType::CASH_REFUND, CashMovementType::PAID_OUT ), true );

		return array(
			'id'               => (int) $row['id'],
			'session_id'       => (int) $row['session_id'],
			'type'             => (string) $row['type'],
			'direction'        => $outgoing ? 'out' : 'in',
			'amount'           => CashMoney::format( (int) $row['amount'], $precision ),
			'reason'           => (string) $row['reason'],
			'order_id'         => null === $row['order_id'] ? null : (int) $row['order_id'],
			'refund_id'        => null === $row['refund_id'] ? null : (int) $row['refund_id'],
			'created_by'       => (int) $row['created_by'],
			'created_by_name'  => (string) $row['created_by_name'],
			'occurred_at'      => CashTimestamp::format( (string) $row['occurred_at_gmt'] ),
			'date_created_gmt' => CashTimestamp::format( (string) $row['date_created_gmt'] ),
		);
	}

	/**
	 * Format a drawer event row.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $row Drawer event row.
	 * @return array<string, mixed>
	 */
	public function format_drawer_event( array $row ): array {
		$nullable_int = fn( $value ) => null === $value ? null : (int) $value;

		return array(
			'id'               => (int) $row['id'],
			'session_id'       => (int) $row['session_id'],
			'request_id'       => (string) $row['request_id'],
			'type'             => (string) $row['type'],
			'reason'           => (string) $row['reason'],
			'drawer_id'        => (string) $row['drawer_name'],
			'order_id'         => $nullable_int( $row['order_id'] ),
			'refund_id'        => $nullable_int( $row['refund_id'] ),
			'movement_id'      => $nullable_int( $row['movement_id'] ),
			'correlation_id'   => null === $row['correlation_id'] ? null : (string) $row['correlation_id'],
			'occurred_at'      => CashTimestamp::format( (string) $row['occurred_at_gmt'] ),
			'created_by'       => (int) $row['created_by'],
			'created_by_name'  => (string) $row['created_by_name'],
			'date_created_gmt' => CashTimestamp::format( (string) $row['date_created_gmt'] ),
		);
	}

	/**
	 * Request ID argument.
	 *
	 * @return array<string, mixed>
	 */
	private function request_id_arg(): array {
		return array(
			'description' => __( 'Client-generated UUID that makes retries safe.', 'woocommerce' ),
			'type'        => 'string',
			'pattern'     => self::UUID_PATTERN,
			'required'    => true,
		);
	}

	/**
	 * Money argument.
	 *
	 * @param string $description Description.
	 * @param bool   $required    Whether it is required.
	 * @return array<string, mixed>
	 */
	private function money_arg( string $description, bool $required ): array {
		return array(
			'description' => $description,
			'type'        => 'string',
			'pattern'     => self::MONEY_PATTERN,
			'required'    => $required,
		);
	}

	/**
	 * Drawer name argument. Length is checked after trimming by DrawerName.
	 *
	 * @return array<string, mixed>
	 */
	private function drawer_arg(): array {
		return array(
			'description' => __( 'Drawer name from the app settings, compared case-insensitively.', 'woocommerce' ),
			'type'        => 'string',
		);
	}

	/**
	 * Positive ID argument.
	 *
	 * @param string $description Description.
	 * @return array<string, mixed>
	 */
	private function id_arg( string $description ): array {
		return array(
			'description' => $description,
			'type'        => 'integer',
			'minimum'     => 1,
		);
	}

	/**
	 * Timestamp argument. The explicit offset is checked by CashTimestamp.
	 *
	 * @param string $description Description.
	 * @param bool   $required    Whether it is required.
	 * @return array<string, mixed>
	 */
	private function timestamp_arg( string $description, bool $required ): array {
		return array(
			'description' => $description . ' ' . __( 'RFC 3339 with an explicit offset.', 'woocommerce' ),
			'type'        => 'string',
			'required'    => $required,
		);
	}

	/**
	 * Wrap properties in an object schema.
	 *
	 * @param string                              $title      Schema title.
	 * @param array<string, array<string, mixed>> $properties Properties.
	 * @return array<string, mixed>
	 */
	private function object_schema( string $title, array $properties ): array {
		foreach ( $properties as $name => $property ) {
			$properties[ $name ] = array_merge(
				array(
					'context'  => array( 'view', 'edit' ),
					'readonly' => true,
				),
				$property
			);
		}

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $title,
			'type'       => 'object',
			'properties' => $properties,
		);
	}
}
