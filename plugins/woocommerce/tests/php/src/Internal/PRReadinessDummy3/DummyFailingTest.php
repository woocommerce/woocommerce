<?php
/**
 * DO NOT MERGE. Scratch test used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Intentionally fails.
 *
 * @package Automattic\WooCommerce\Tests\Internal\PRReadinessDummy3
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\PRReadinessDummy3;

use WC_Unit_Test_Case;

/**
 * Intentionally failing unit test.
 */
class DummyFailingTest extends WC_Unit_Test_Case {

	/**
	 * Intentionally fails to exercise the readiness bot's "Unit tests (PHP)" task.
	 */
	public function test_intentional_failure() {
		$this->assertTrue( false, 'Intentional failure for PR readiness bot dummy test PR.' );
	}
}
