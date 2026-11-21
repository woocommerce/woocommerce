<?php
/**
 * Finite graph fixture for the explorer checks.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking\Tests;

use Automattic\WooCommerce\Tools\ModelChecking\Model;

/**
 * A three-state graph with a self-loop and an optional violation.
 *
 * @implements Model<array{count: int}>
 */
final class CounterModel implements Model {
	/** @var int|null Count at which the invariant fails. */
	private $violation_at;

	/**
	 * Select whether and where to violate the invariant.
	 *
	 * @since 11.4.0
	 * @param int|null $violation_at Failing count, or null.
	 */
	public function __construct( ?int $violation_at ) {
		$this->violation_at = $violation_at;
	}

	/**
	 * Start at zero.
	 *
	 * @since 11.4.0
	 * @return array{count: int}
	 */
	public function initial_state(): array {
		return array( 'count' => 0 );
	}

	/**
	 * Include a cycle to exercise visited-state detection.
	 *
	 * @since 11.4.0
	 * @param array{count: int} $state Current state.
	 * @return iterable<array{action: string, state: array{count: int}}>
	 */
	public function transitions( array $state ): iterable {
		yield array(
			'action' => 'wait',
			'state'  => $state,
		);
		if ( $state['count'] < 2 ) {
			yield array(
				'action' => 'increment',
				'state'  => array( 'count' => $state['count'] + 1 ),
			);
		}
	}

	/**
	 * Check the selected safety property.
	 *
	 * @since 11.4.0
	 * @param array{count: int} $state State to check.
	 * @return string|null
	 */
	public function violation( array $state ): ?string {
		return $state['count'] === $this->violation_at ? 'CounterBound' : null;
	}
}
