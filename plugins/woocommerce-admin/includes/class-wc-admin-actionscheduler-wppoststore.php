<?php
/**
 * WC Admin Action Scheduler Store.
 *
 * @package WooCommerce Admin/Classes
 */

/**
 * Class WC Admin Action Scheduler Store.
 */
class WC_Admin_ActionScheduler_WPPostStore extends ActionScheduler_wpPostStore {
	/**
	 * Action scheduler job priority (lower numbers are claimed first).
	 */
	const JOB_PRIORITY = 30;

	/**
	 * Create the post array for storing actions as WP posts.
	 *
	 * For WC Admin actions, force a lower action claim
	 * priority by setting a high value for `menu_order`.
	 *
	 * @param ActionScheduler_Action $action Action.
	 * @param DateTime               $scheduled_date Action schedule.
	 * @return array Post data array for usage in wp_insert_post().
	 */
	protected function create_post_array( ActionScheduler_Action $action, DateTime $scheduled_date = null ) {
		$postdata = parent::create_post_array( $action, $scheduled_date );

		if ( 0 === strpos( $postdata['post_title'], 'wc-admin_' ) ) {
			$postdata['menu_order'] = self::JOB_PRIORITY;
		}

		return $postdata;
	}

	/**
	 * Forcefully delete all pending WC Admin scheduled actions.
	 *
	 * Directly trashes items from in database for performance.
	 */
	public function clear_pending_wcadmin_actions() {
		global $wpdb;

		// Cancel all pending actions by trashing the posts.
		// Action Scheduler will handle the cleanup.
		$action_types = array(
			WC_Admin_Reports_Sync::QUEUE_BATCH_ACTION,
			WC_Admin_Reports_Sync::QUEUE_DEPEDENT_ACTION,
			WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION,
			WC_Admin_Reports_Sync::ORDERS_IMPORT_BATCH_ACTION,
			WC_Admin_Reports_Sync::ORDERS_IMPORT_BATCH_INIT,
			WC_Admin_Reports_Sync::SINGLE_ORDER_IMPORT_ACTION,
		);

		foreach ( $action_types as $action_type ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_status' => 'trash',
				),
				array(
					'post_type'   => 'scheduled-action',
					'post_status' => 'pending',
					'post_title'  => $action_type,
				)
			);
		}
	}
}
