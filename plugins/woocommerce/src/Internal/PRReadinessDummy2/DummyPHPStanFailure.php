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
 * Intentionally triggers a PHPStan error: calling a method on a null value.
 */
class DummyPHPStanFailure {

	/**
	 * Fixed to return a value.
	 *
	 * @return int
	 */
	public function run(): int {
		return 42;
	}
}
