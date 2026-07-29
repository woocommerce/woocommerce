<?php
/**
 * DO NOT MERGE. Scratch test used to manually verify the pr-readiness-comment
 * bot (see PR #67081) against a live PR. Formerly failed intentionally; now
 * fixed.
 *
 * @package Automattic\WooCommerce\Tests\Internal\PRReadinessDummy
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\PRReadinessDummy;

use WC_Unit_Test_Case;

/**
 * Formerly an intentionally failing unit test; now fixed.
 */
class DummyFailingTest extends WC_Unit_Test_Case {

	/**
	 * Exercises the readiness bot's "Unit tests (PHP)" task; now passes.
	 */
	public function test_intentional_failure() {
		$this->assertTrue( true );
	}
}
