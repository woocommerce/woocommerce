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
	 * Directly deletes items from the database for performance.
	 */
	public function clear_pending_wcadmin_actions() {
		global $wpdb;

		// Remove all scheduled action posts and their metadata.
		$delete_pending_sql =
			"DELETE p.*, pm.* FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE post_type = 'scheduled-action'
			AND post_status = 'pending'
			AND post_title LIKE 'wc-admin_%'";

		// phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( $delete_pending_sql );

		// Delete all taxonomy data related to the WC Admin scheduled action group.
		$group_term = get_term_by( 'slug', WC_Admin_Reports_Sync::QUEUE_GROUP, parent::GROUP_TAXONOMY );

		if ( $group_term ) {
			$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $group_term->term_taxonomy_id ), array( '%d' ) );
			$wpdb->delete( $wpdb->term_taxonomy, array( 'term_id' => $group_term->term_id ), array( '%d' ) );
			$wpdb->delete( $wpdb->terms, array( 'term_id' => $group_term->term_id ), array( '%d' ) );

			clean_taxonomy_cache( parent::GROUP_TAXONOMY );
		}
	}
}
