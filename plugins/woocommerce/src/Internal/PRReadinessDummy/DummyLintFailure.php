<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy;

/**
 * Formerly triggered a Lint failure; now WPCS-compliant.
 */
class DummyLintFailure {

	/**
	 * Always returns true. No-op commit for the clear->clear test step.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return true;
	}
}
