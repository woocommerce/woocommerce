<?php
/**
 * Helper code for wc-admin unit tests.
 *
 * @package WooCommerce\Admin\Tests\Framework\Helpers
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
		$queue_runner = new ActionScheduler_QueueRunner();

		while ( $jobs = self::get_all_pending() ) {
			foreach ( $jobs as $job_id => $job ) {
				$queue_runner->process_action( $job_id );
			}
		}
	}

	/**
	 * Cancel all pending actions.
	 *
	 * @return void
	 */
	public static function cancel_all_pending() {
		// Force immediate hard delete for Action Scheduler < 3.0.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'scheduled-action'" );

		// Delete actions for Action Scheduler >= 3.0.
		$store = ActionScheduler_Store::instance();

		if ( is_callable( array( $store, 'cancel_actions_by_group' ) ) ) {
			$store->cancel_actions_by_group( 'wc-admin-data' );
		}
	}
}
