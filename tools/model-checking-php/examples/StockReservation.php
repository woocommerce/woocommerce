<?php
/**
 * A finite model of WooCommerce stock reservation.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking\Examples;

use Automattic\WooCommerce\Tools\ModelChecking\Model;
use Automattic\WooCommerce\Tools\ModelChecking\Phase;

/**
 * Model one product, fixed stock, default hooks, and unpaid orders.
 *
 * @phpstan-type CaseConfig array{stock: int, max_quantity: int, hold_for: int, max_time: int, found_rows: bool, split: bool}
 * @phpstan-type StockState array{held: array<string, int>, expires: array<string, int>, request: array<string, int>, attempts: array<string, int>, phase: array<string, string>, now: int, seen: array<string, int>}
 * @implements Model<StockState>
 */
final class StockReservation implements Model {
	/** Modeled orders. */
	private const ORDERS = array( 'A', 'B' );

	/**
	 * Finite configuration.
	 *
	 * @var CaseConfig
	 */
	private $config;

	/**
	 * Set the finite bounds for one run.
	 *
	 * @since 11.4.0
	 * @param array $config Configuration.
	 * @phpstan-param CaseConfig $config
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * Get the comparable passing cases and deliberately broken variant.
	 *
	 * @since 11.4.0
	 * @return array<string, CaseConfig>
	 */
	public static function cases(): array {
		$small = array(
			'stock'        => 1,
			'max_quantity' => 2,
			'hold_for'     => 2,
			'max_time'     => 3,
			'found_rows'   => false,
			'split'        => false,
		);
		return array(
			'small'      => $small,
			'larger'     => array_merge(
				$small,
				array(
					'stock'        => 2,
					'max_quantity' => 3,
				)
			),
			'found-rows' => array_merge( $small, array( 'found_rows' => true ) ),
			'split'      => array_merge(
				$small,
				array(
					'max_quantity' => 1,
					'max_time'     => 0,
					'split'        => true,
				)
			),
		);
	}

	/**
	 * Start with no holds and no calls in flight.
	 *
	 * @since 11.4.0
	 * @return StockState
	 */
	public function initial_state(): array {
		return array(
			'held'     => array_fill_keys( self::ORDERS, 0 ),
			'expires'  => array_fill_keys( self::ORDERS, 0 ),
			'request'  => array_fill_keys( self::ORDERS, 1 ),
			'attempts' => array_fill_keys( self::ORDERS, 0 ),
			'phase'    => array_fill_keys( self::ORDERS, Phase::IDLE ),
			'now'      => 0,
			'seen'     => array_fill_keys( self::ORDERS, 0 ),
		);
	}

	/**
	 * Enumerate every call, database outcome, release, and clock step.
	 *
	 * @since 11.4.0
	 * @param array $state Current state.
	 * @phpstan-param StockState $state
	 * @return iterable<array{action: string, state: StockState}>
	 */
	public function transitions( array $state ): iterable {
		foreach ( self::ORDERS as $order ) {
			$phase = $state['phase'][ $order ];
			if ( in_array( $phase, array( Phase::IDLE, Phase::RESERVED, Phase::FAILED ), true ) ) {
				for ( $quantity = 1; $quantity <= $this->config['max_quantity']; ++$quantity ) {
					$next                       = $state;
					$next['request'][ $order ]  = $quantity;
					$next['attempts'][ $order ] = 0;
					$next['phase'][ $order ]    = Phase::QUERY;
					yield array(
						'action' => "start($order,$quantity)",
						'state'  => $next,
					);
				}
			}
			if ( Phase::QUERY === $phase ) {
				$next = $state;
				++$next['attempts'][ $order ];
				$next['phase'][ $order ] = 3 === $next['attempts'][ $order ] ? Phase::CLEANUP : Phase::QUERY;
				yield array(
					'action' => "databaseError($order)",
					'state'  => $next,
				);

				if ( $this->config['split'] ) {
					$next                    = $state;
					$next['seen'][ $order ]  = self::other_held( $state, $order );
					$next['phase'][ $order ] = Phase::WRITE;
					yield array(
						'action' => "readAvailability($order)",
						'state'  => $next,
					);
				} else {
					yield array(
						'action' => "tryReserve($order)",
						'state'  => $this->apply_result( $state, $order, self::other_held( $state, $order ) ),
					);
				}
			}
			if ( $this->config['split'] && Phase::WRITE === $phase ) {
				yield array(
					'action' => "writeReservation($order)",
					'state'  => $this->apply_result( $state, $order, $state['seen'][ $order ] ),
				);
			}
			if ( Phase::CLEANUP === $phase ) {
				$next                      = $state;
				$next['held'][ $order ]    = 0;
				$next['expires'][ $order ] = 0;
				$next['phase'][ $order ]   = Phase::FAILED;
				yield array(
					'action' => "cleanup($order)",
					'state'  => $next,
				);
			}
			if ( $state['held'][ $order ] > 0 ) {
				$next                      = $state;
				$next['held'][ $order ]    = 0;
				$next['expires'][ $order ] = 0;
				yield array(
					'action' => "release($order)",
					'state'  => $next,
				);
			}
		}
		if ( $state['now'] < $this->config['max_time'] ) {
			$next = $state;
			++$next['now'];
			yield array(
				'action' => 'tick',
				'state'  => $next,
			);
		}
	}

	/**
	 * Check finite types and total active holds.
	 *
	 * @since 11.4.0
	 * @param array $state State to check.
	 * @phpstan-param StockState $state
	 * @return string|null
	 */
	public function violation( array $state ): ?string {
		if ( $state['now'] < 0 || $state['now'] > $this->config['max_time'] ) {
			return 'TypeOK';
		}
		foreach ( self::ORDERS as $order ) {
			foreach ( array(
				'held'     => 0,
				'expires'  => 0,
				'request'  => 1,
				'attempts' => 0,
				'seen'     => 0,
			) as $field => $minimum ) {
				$maximum = array(
					'held'     => $this->config['max_quantity'],
					'expires'  => $this->config['max_time'] + $this->config['hold_for'],
					'request'  => $this->config['max_quantity'],
					'attempts' => 3,
					'seen'     => 2 * $this->config['max_quantity'],
				);
				if ( $state[ $field ][ $order ] < $minimum || $state[ $field ][ $order ] > $maximum[ $field ] ) {
					return 'TypeOK';
				}
			}
			if ( ! in_array( $state['phase'][ $order ], array( Phase::IDLE, Phase::QUERY, Phase::CLEANUP, Phase::RESERVED, Phase::FAILED, Phase::WRITE ), true ) ) {
				return 'TypeOK';
			}
		}
		return self::active( $state, 'A' ) + self::active( $state, 'B' ) <= $this->config['stock'] ? null : 'NoOverReservation';
	}

	/**
	 * Apply one serialized SQL attempt, including zero affected rows.
	 *
	 * @param array  $state Current state.
	 * @phpstan-param StockState $state
	 * @param string $order Order identifier.
	 * @param int    $counted Other active holds.
	 * @return StockState
	 */
	private function apply_result( array $state, string $order, int $counted ): array {
		$next = $state;
		++$next['attempts'][ $order ];
		if ( $this->config['stock'] - $counted < $state['request'][ $order ] ) {
			$next['phase'][ $order ] = Phase::CLEANUP;
			return $next;
		}
		$no_op                     = $state['held'][ $order ] === $state['request'][ $order ] && $state['expires'][ $order ] === $state['now'] + $this->config['hold_for'];
		$next['held'][ $order ]    = $state['request'][ $order ];
		$next['expires'][ $order ] = $state['now'] + $this->config['hold_for'];
		$next['phase'][ $order ]   = $no_op && ! $this->config['found_rows'] ? Phase::CLEANUP : Phase::RESERVED;
		return $next;
	}

	/**
	 * Count a row only while its reservation is active.
	 *
	 * @param array  $state Current state.
	 * @phpstan-param StockState $state
	 * @param string $order Order identifier.
	 * @return int
	 */
	private static function active( array $state, string $order ): int {
		return $state['expires'][ $order ] > $state['now'] ? $state['held'][ $order ] : 0;
	}

	/**
	 * Exclude the order whose existing row will be replaced.
	 *
	 * @param array  $state Current state.
	 * @phpstan-param StockState $state
	 * @param string $order Order identifier.
	 * @return int
	 */
	private static function other_held( array $state, string $order ): int {
		return self::active( $state, 'A' === $order ? 'B' : 'A' );
	}
}
