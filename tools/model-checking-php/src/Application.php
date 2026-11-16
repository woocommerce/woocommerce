<?php
/**
 * CLI runner for the stock-reservation examples.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking;

use Automattic\WooCommerce\Tools\ModelChecking\Examples\StockReservation;
use RuntimeException;
use Throwable;

/**
 * Check the example matrix and require the expected counterexample.
 */
final class Application {
	/**
	 * Run all cases or the case named on the command line.
	 *
	 * @since 11.4.0
	 * @param string[] $args Command-line arguments.
	 * @throws RuntimeException Caught locally and reported as a nonzero exit.
	 * @return int Exit status.
	 */
	public function run( array $args ): int {
		try {
			$cases = StockReservation::cases();
			if ( count( $args ) > 2 || ( isset( $args[1] ) && ! isset( $cases[ $args[1] ] ) ) ) {
				throw new RuntimeException( 'Usage: php run.php [small|larger|found-rows|split]' );
			}
			if ( isset( $args[1] ) ) {
				$cases = array( $args[1] => $cases[ $args[1] ] );
			}
			$explorer = new Explorer();
			foreach ( $cases as $name => $config ) {
				$result   = $explorer->check( new StockReservation( $config ) );
				$expected = 'split' === $name ? 'NoOverReservation' : null;
				if ( $expected !== $result['violation'] || ( null === $expected && ! $result['complete'] ) ) {
					throw new RuntimeException( 'Unexpected result for ' . $name );
				}
				$result['case']   = $name;
				$result['result'] = null === $expected ? 'passed' : 'expected counterexample';
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The CLI runs without WordPress.
				fwrite( STDOUT, json_encode( $result, JSON_THROW_ON_ERROR ) . PHP_EOL );
			}
			return 0;
		} catch ( Throwable $error ) {
			fwrite( STDERR, $error->getMessage() . PHP_EOL );
			return 1;
		}
	}
}
