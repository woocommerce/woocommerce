<?php
/**
 * Dependency-free behavioral checks for the explorer.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking\Tests;

use Automattic\WooCommerce\Tools\ModelChecking\Explorer;
use RuntimeException;

require_once __DIR__ . '/../src/Model.php';
require_once __DIR__ . '/../src/Explorer.php';

require_once __DIR__ . '/CounterModel.php';

/**
 * Exercise cycles, initial violations, traces, and incomplete exploration.
 */
final class ExplorerChecks {
	/**
	 * Run the standalone checks.
	 *
	 * @since 11.4.0
	 * @throws RuntimeException If a behavioral check fails.
	 * @return void
	 */
	public function run(): void {
		$sut    = new Explorer();
		$passed = $sut->check( new CounterModel( null ) );
		self::expect( $passed['complete'] && 3 === $passed['states'] && null === $passed['violation'], 'A cyclic finite graph must be exhausted.' );

		$failed = $sut->check( new CounterModel( 2 ) );
		self::expect( 'CounterBound' === $failed['violation'], 'A reachable violation must be found.' );
		self::expect( array( 'init', 'increment', 'increment' ) === array_column( $failed['trace'], 'action' ), 'The counterexample must give a shortest replayable path.' );
		self::expect( 2 === $failed['trace'][2]['state']['count'], 'The trace must end at the violating state.' );

		$initial = $sut->check( new CounterModel( 0 ) );
		self::expect( 'CounterBound' === $initial['violation'] && 1 === count( $initial['trace'] ), 'The initial state must be checked.' );

		try {
			$sut->check( new CounterModel( null ), 1 );
			throw new RuntimeException( 'A state limit must not be reported as a pass.' );
		} catch ( RuntimeException $error ) {
			self::expect( 'State limit reached; exploration is incomplete.' === $error->getMessage(), 'The state limit must fail explicitly.' );
		}
		fwrite( STDOUT, 'Explorer checks passed.' . PHP_EOL );
	}

	/**
	 * Fail the command when a behavioral expectation does not hold.
	 *
	 * @param bool   $condition Expected condition.
	 * @param string $message Failure explanation.
	 * @throws RuntimeException If a behavioral check fails.
	 * @return void
	 */
	private static function expect( bool $condition, string $message ): void {
		if ( ! $condition ) {
			throw new RuntimeException( $message );
		}
	}
}

( new ExplorerChecks() )->run();
