<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081). Now WPCS-compliant as part of the failing -> failing
 * transition test.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy2
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy2;

/**
 * Placeholder class, now WPCS-compliant.
 */
class DummyLintFailure {

	/**
	 * Always returns true.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return true;
	}
}
