<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\PageController;
use WC_Unit_Test_Case;

/**
 * Tests for current screen detection in PageController.
 */
class PageControllerCurrentScreenTest extends WC_Unit_Test_Case {
	/**
	 * Backup of $GLOBALS['current_screen'].
	 *
	 * @var object|null
	 */
	private $current_screen_backup;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->current_screen_backup = $GLOBALS['current_screen'] ?? null;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$GLOBALS['current_screen'] = $this->current_screen_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		parent::tearDown();
	}

	/**
	 * @testdox Should fall back to a filterable false when the current screen cannot be determined.
	 */
	public function test_get_current_screen_id_returns_false_when_current_screen_is_unavailable(): void {
		$GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$filter_args = null;
		$filter      = function ( $screen_id, $current_screen ) use ( &$filter_args ) {
			$filter_args = array( $screen_id, $current_screen );
			return $screen_id;
		};
		add_filter( 'woocommerce_navigation_current_screen_id', $filter, 10, 2 );

		$screen_id = PageController::get_instance()->get_current_screen_id();

		remove_filter( 'woocommerce_navigation_current_screen_id', $filter );

		$this->assertFalse( $screen_id, 'get_current_screen_id() should return false when no screen is set.' );
		$this->assertSame(
			array( false, null ),
			$filter_args,
			'The false fallback should pass through the woocommerce_navigation_current_screen_id filter with a null screen.'
		);
	}
}
