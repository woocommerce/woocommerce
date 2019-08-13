<?php
/**
 * WC Admin Action Scheduler Store.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\Overrides;

/**
 * Class WC Admin Action Scheduler Store.
 */
class WPPostStore extends \ActionScheduler_wpPostStore {
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
	protected function create_post_array( \ActionScheduler_Action $action, \DateTime $scheduled_date = null ) {
		$postdata = parent::create_post_array( $action, $scheduled_date );

		if ( 0 === strpos( $postdata['post_title'], 'wc-admin_' ) ) {
			$postdata['menu_order'] = self::JOB_PRIORITY;
		}

		return $postdata;
	}

	/**
	 * Forcefully delete all pending WC Admin scheduled actions.
	 * Directly trashes items from in database for performance.
	 *
	 * @param array $action_types Array of actions to delete.
	 */
	public function clear_pending_wcadmin_actions( $action_types ) {
		global $wpdb;

		// Cancel all pending actions by trashing the posts.
		// Action Scheduler will handle the cleanup.
		foreach ( (array) $action_types as $action_type ) {
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
