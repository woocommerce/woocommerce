<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Intentionally fails PHPStan.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy;

/**
 * Intentionally triggers a PHPStan error: calling a method on a null value.
 */
class DummyPHPStanFailure {

	/**
	 * Intentionally calls a method on null to trigger a PHPStan failure.
	 *
	 * @return int
	 */
	public function run(): int {
		$value = null;
		return $value->calculate();
	}
}
