<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Formerly triggered a PHPStan error;
 * now fixed.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy;

/**
 * Formerly called a method on a null value; now fixed.
 */
class DummyPHPStanFailure {

	/**
	 * Returns a fixed value instead of calling a method on null.
	 *
	 * @return int
	 */
	public function run(): int {
		return 0;
	}
}
