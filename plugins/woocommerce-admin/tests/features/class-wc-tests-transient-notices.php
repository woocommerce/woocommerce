<?php
/**
 * Task Lists Tests.
 *
 * @package Automattic\WooCommerce\Admin\Features
 */

use \Automattic\WooCommerce\Admin\Features\TransientNotices;

/**
 * Class WC_Tests_Transient_Notices
 */
class WC_Tests_Transient_Notices extends WC_Unit_Test_Case {

	/**
	 * Test that notices can be added.
	 */
	public function test_add_notices() {
		TransientNotices::add(
			array(
				'id'      => 'test-notice-1',
				'content' => 'Test notice 1',
			)
		);

		$notices = get_option( TransientNotices::QUEUE_OPTION );
		$this->assertCount( 1, $notices );

		TransientNotices::add(
			array(
				'id'      => 'test-notice-2',
				'content' => 'Test notice 2',
			)
		);

		$notices = get_option( TransientNotices::QUEUE_OPTION );
		$this->assertCount( 2, $notices );

		$this->assertArrayHasKey( 'test-notice-1', $notices );
		$this->assertArrayHasKey( 'test-notice-2', $notices );
	}

	/**
	 * Test that notices can be removed.
	 */
	public function test_remove_notices() {
		TransientNotices::add(
			array(
				'id'      => 'test-notice-to-remove',
				'content' => 'This notice should be removed',
			)
		);

		TransientNotices::remove( 'test-notice-to-remove' );

		$notices = get_option( TransientNotices::QUEUE_OPTION );
		$this->assertArrayNotHasKey( 'test-notice-to-remove', $notices );
	}
}
