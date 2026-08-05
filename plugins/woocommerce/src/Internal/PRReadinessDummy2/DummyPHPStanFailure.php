<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Intentionally fails PHPStan.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy2
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy2;

/**
 * Intentionally triggers multiple failures: PHPStan, Lint, and E2E test failures.
 */
class DummyPHPStanFailure {

	/**
	 * Intentionally broken method for PHPStan failure.
	 *
	 * @return int
	 */
	public function run(): int {
		$value = null;
		// This will fail PHPStan - calling method on null
		return $value->calculate();
	}

	/**
	 * Lint violation - bad formatting and spacing.
	 */
	public   function   poorlyFormatted(  ) {
		$x=1;$y=2;$z=$x+$y;
		return  $z  ;
	}

	/**
	 * E2E test failure - breaking a required method signature.
	 */
	public static function blocksRegistered() {
		// This will fail E2E tests expecting certain blocks
		return false;
	}
}
// Intentionally broken - multiple failures
