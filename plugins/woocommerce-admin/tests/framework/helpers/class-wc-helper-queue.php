<?php
/**
 * Helper code for wc-admin unit tests.
 *
 * @package WooCommerce\Tests\Framework\Helpers
 */

/**
 * Class WC_Helper_Queue.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Queue {
	/**
	 * Run all pending queued actions.
	 *
	 * @return void
	 */
	public static function run_all_pending() {
		$jobs = WC()->queue()->search(
			array(
				'per_page' => -1,
				'status'   => 'pending',
				'claimed'  => false,
			)
		);

		foreach ( $jobs as $job ) {
			$job->execute();
		}
	}
}
