<?php
/**
 * DO NOT MERGE. Scratch file used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Valid, WPCS/PHPStan-clean placeholder
 * so the very first commit on this PR has at least one PHP change, which is
 * needed to make the Lint and PHPStan check-runs actually run (and pass), so
 * the bot has at least one relevant, completed check-run to build its
 * first "all clear" comment from.
 *
 * @package Automattic\WooCommerce\Internal\PRReadinessDummy2
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy2;

/**
 * Intentionally trivial and always-passing placeholder class.
 */
class DummyReadinessProbe {

	/**
	 * Always returns true.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return true;
	}
}
