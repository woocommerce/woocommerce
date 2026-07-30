<?php
/**
 * DO NOT MERGE. Scratch test used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Intentionally fails.
 *
 * @package Automattic\WooCommerce\Tests\Internal\PRReadinessDummy2
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\PRReadinessDummy2;

use WC_Unit_Test_Case;

/**
 * Intentionally failing unit test.
 */
class DummyFailingTest extends WC_Unit_Test_Case {

	/**
	 * Fixed to pass.
	 */
	public function test_intentional_failure() {
		$this->assertTrue( true );
	}
}
