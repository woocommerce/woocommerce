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
	 * Get all pending queued actions.
	 *
	 * @return array Pending jobs.
	 */
	public static function get_all_pending() {
		$jobs = WC()->queue()->search(
			array(
				'per_page' => -1,
				'status'   => 'pending',
				'claimed'  => false,
			)
		);

		return $jobs;
	}

	/**
	 * Run all pending queued actions.
	 *
	 * @return void
	 */
	public static function run_all_pending() {
		$jobs = self::get_all_pending();

		foreach ( $jobs as $job ) {
			$job->execute();
		}
	}
}
