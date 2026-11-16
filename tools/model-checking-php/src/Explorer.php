<?php
/**
 * Breadth-first safety checking for the isolated POC.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking;

use InvalidArgumentException;
use RuntimeException;
use SplQueue;

/**
 * Explore reachable states and retain a shortest counterexample.
 */
final class Explorer {
	/**
	 * Explore until the graph is exhausted, an invariant fails, or the limit is hit.
	 *
	 * @since 11.4.0
	 * @template TState of array<string, mixed>
	 * @param Model $model Model to explore.
	 * @phpstan-param Model<TState> $model
	 * @param int   $max_states Maximum states to retain.
	 * @throws InvalidArgumentException If the state limit is not positive.
	 * @throws RuntimeException If exploration exceeds the state limit.
	 * @return array{complete: bool, violation: ?string, states: int, generated: int, trace: list<array{action: string, state: TState}>}
	 */
	public function check( Model $model, int $max_states = 100000 ): array {
		if ( $max_states < 1 ) {
			throw new InvalidArgumentException( 'The state limit must be positive.' );
		}

		$initial = self::encode( $model->initial_state() );
		$states  = array( $initial );
		$parents = array( null );
		$actions = array( 'init' );
		$visited = array( $initial => 0 );
		/**
		 * Worklist of discovered state indexes.
		 *
		 * @var SplQueue<int> $queue
		 */
		$queue = new SplQueue();
		$queue->enqueue( 0 );
		$generated = 1;

		while ( ! $queue->isEmpty() ) {
			$index = $queue->dequeue();
			/**
			 * Current model state.
			 *
			 * @var TState $state
			 */
			$state     = json_decode( $states[ $index ], true, 512, JSON_THROW_ON_ERROR );
			$violation = $model->violation( $state );
			if ( null !== $violation ) {
				$trace = array();
				for ( $cursor = $index; null !== $cursor; $cursor = $parents[ $cursor ] ) {
					/**
					 * State on the counterexample path.
					 *
					 * @var TState $traced_state
					 */
					$traced_state = json_decode( $states[ $cursor ], true, 512, JSON_THROW_ON_ERROR );
					$trace[]      = array(
						'action' => $actions[ $cursor ],
						'state'  => $traced_state,
					);
				}
				return array(
					'complete'  => false,
					'violation' => $violation,
					'states'    => count( $states ),
					'generated' => $generated,
					'trace'     => array_reverse( $trace ),
				);
			}

			foreach ( $model->transitions( $state ) as $transition ) {
				++$generated;
				$key = self::encode( $transition['state'] );
				if ( array_key_exists( $key, $visited ) ) {
					continue;
				}
				if ( count( $states ) >= $max_states ) {
					throw new RuntimeException( 'State limit reached; exploration is incomplete.' );
				}
				$next            = count( $states );
				$visited[ $key ] = $next;
				$states[]        = $key;
				$parents[]       = $index;
				$actions[]       = $transition['action'];
				$queue->enqueue( $next );
			}
		}

		return array(
			'complete'  => true,
			'violation' => null,
			'states'    => count( $states ),
			'generated' => $generated,
			'trace'     => array(),
		);
	}

	/**
	 * Keep exact state keys rather than fingerprints.
	 *
	 * @param array<string, mixed> $state JSON-compatible state.
	 * @return string
	 */
	private static function encode( array $state ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The CLI runs without WordPress.
		return json_encode( $state, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION );
	}
}
