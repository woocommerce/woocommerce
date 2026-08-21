<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Its phpcs violations were fixed in
 * stage 4 while sibling files keep failing their checks.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy3
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy3;

/**
 * Previously violated phpcs; now clean.
 */
class DummyLintFailure {

	/**
	 * Returns true.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return true;
	}
}
