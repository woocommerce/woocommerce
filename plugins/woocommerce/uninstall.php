<?php
/**
 * WooCommerce Uninstall
 *
 * Uninstalling WooCommerce deletes user roles, pages, tables, and options.
 *
 * @package WooCommerce\Uninstaller
 * @version 2.3.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb, $wp_version, $wc_uninstalling_plugin;

$wc_uninstalling_plugin = true;

// Clear WordPress cron events.
wp_clear_scheduled_hook( 'woocommerce_scheduled_sales' );
wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );
wp_clear_scheduled_hook( 'woocommerce_cleanup_sessions' );
wp_clear_scheduled_hook( 'woocommerce_cleanup_personal_data' );
wp_clear_scheduled_hook( 'woocommerce_cleanup_logs' );
wp_clear_scheduled_hook( 'woocommerce_geoip_updater' );
wp_clear_scheduled_hook( 'woocommerce_tracker_send_event' );
wp_clear_scheduled_hook( 'woocommerce_cleanup_rate_limits' );
wp_clear_scheduled_hook( 'wc_admin_daily' );
wp_clear_scheduled_hook( 'generate_category_lookup_table' );
wp_clear_scheduled_hook( 'wc_admin_unsnooze_admin_notes' );

if ( class_exists( ActionScheduler::class ) && ActionScheduler::is_initialized() && function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'woocommerce_scheduled_sales' );
	as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
	as_unschedule_all_actions( 'woocommerce_cleanup_sessions' );
	as_unschedule_all_actions( 'woocommerce_cleanup_personal_data' );
	as_unschedule_all_actions( 'woocommerce_cleanup_logs' );
	as_unschedule_all_actions( 'woocommerce_geoip_updater' );
	as_unschedule_all_actions( 'woocommerce_tracker_send_event' );
	as_unschedule_all_actions( 'woocommerce_cleanup_rate_limits' );
	as_unschedule_all_actions( 'wc_admin_daily' );
	as_unschedule_all_actions( 'generate_category_lookup_table' );
	as_unschedule_all_actions( 'wc_admin_unsnooze_admin_notes' );
}

/*
 * Only remove ALL product and page data if WC_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WC_REMOVE_ALL_DATA' ) && true === WC_REMOVE_ALL_DATA ) {
	// Load WooCommerce so we can access the container, install routines, etc, during uninstall.
	require_once __DIR__ . '/includes/class-wc-install.php';

	// Drop custom WordPress tables indexes. See \WC_Install::create_tables() for details.
	$index_exists = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->comments} WHERE key_name = 'woo_idx_comment_type';" );
	if ( null !== $index_exists ) {
		$wpdb->query( "ALTER TABLE {$wpdb->comments} DROP INDEX woo_idx_comment_type;" );
	}
	$date_type_index_exists = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->comments} WHERE key_name = 'woo_idx_comment_date_type';" );
	if ( null !== $date_type_index_exists ) {
		$wpdb->query( "ALTER TABLE {$wpdb->comments} DROP INDEX woo_idx_comment_date_type;" );
	}
	$comment_approved_type_index_exists = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->comments} WHERE key_name = 'woo_idx_comment_approved_type';" );
	if ( null !== $comment_approved_type_index_exists ) {
		$wpdb->query( "ALTER TABLE {$wpdb->comments} DROP INDEX woo_idx_comment_approved_type;" );
	}

	// Roles + caps.
	WC_Install::remove_roles();

	// Pages.
	wp_trash_post( get_option( 'woocommerce_shop_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_cart_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_checkout_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_myaccount_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_edit_address_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_view_order_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_change_password_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_logout_page_id' ) );

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_attribute_taxonomies';" ) ) {
		$wc_attributes = array_filter( (array) $wpdb->get_col( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies;" ) );
	} else {
		$wc_attributes = array();
	}

	// Tables.
	WC_Install::drop_tables();

	/*
	 * Action Scheduler is a shared library that other active plugins may also use, so its tables are
	 * kept by default. They are only dropped when the site owner additionally sets the
	 * WC_REMOVE_ACTION_SCHEDULER constant to true in wp-config.php, confirming that no other plugin
	 * relies on Action Scheduler (otherwise that plugin would lose its scheduled actions).
	 */
	if ( defined( 'WC_REMOVE_ACTION_SCHEDULER' ) && true === WC_REMOVE_ACTION_SCHEDULER ) {
		foreach ( WC_Install::get_action_scheduler_tables() as $as_table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$as_table}" );
		}
	}

	// Placeholder image: delete the attachment post, its meta and the file.
	WC_Install::delete_placeholder_image();

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce\_%';" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'widget\_woocommerce\_%';" );

	/*
	 * Delete user meta created by WooCommerce.
	 *
	 * The woocommerce_ and _woocommerce_ prefixes are uniquely namespaced, so a LIKE wildcard is safe.
	 * The wc_ / _wc_ namespace is only two characters: a blanket wc_% / _wc_% wildcard would also delete
	 * other plugins' user meta and, critically, WordPress core's own role/capability meta (the
	 * {prefix}capabilities and {prefix}user_level keys) on any site whose database table prefix is "wc_",
	 * which would strip every user's roles and could lock the site out. We therefore match only
	 * WooCommerce's own known wc_ / _wc_ user meta keys (the wc_admin_ legacy prefix; the _wc_egg_
	 * easter-egg meta; the per-site customer lookup, push notification preferences, shopper-list, and
	 * email-verification meta, whose keys are suffixed with the site's table prefix; and the exact
	 * wc_last_active and wc_marketplace_suggestions_dismissed_suggestions keys) rather than a blanket
	 * wildcard.
	 *
	 * The push-notification-preferences, shopper-list, and email-verification prefixes below mirror,
	 * respectively, NotificationPreferencesDataStore::META_KEY, ShopperList::META_KEY_PREFIX, and
	 * EmailVerificationService::VERIFIED_META / ::KEY_META. This script runs before PSR-4 autoloading is
	 * available, so those constants cannot be referenced directly here; keep the literals below in sync if
	 * the constants ever change.
	 *
	 * Note: wp_usermeta is shared across a multisite network while this uninstall runs per site, so the
	 * matching meta is removed network-wide, consistent with the woocommerce_ option/meta cleanup above.
	 */
	$wpdb->query(
		"DELETE FROM $wpdb->usermeta WHERE
			meta_key LIKE 'woocommerce\_%'
			OR meta_key LIKE '\_woocommerce\_%'
			OR meta_key LIKE 'wc\_admin\_%'
			OR meta_key LIKE '\_wc\_egg\_%'
			OR meta_key LIKE '\_wc\_shopper\_list\_%'
			OR meta_key LIKE '\_wc\_email\_verified\_%'
			OR meta_key LIKE '\_wc\_email\_verification\_%'
			OR meta_key LIKE 'wc\_last\_order\_%'
			OR meta_key LIKE 'wc\_order\_count\_%'
			OR meta_key LIKE 'wc\_money\_spent\_%'
			OR meta_key LIKE 'wc\_push\_notification\_preferences\_%'
			OR meta_key IN ( 'wc_last_active', 'wc_marketplace_suggestions_dismissed_suggestions' );"
	);

	/*
	 * Remove direct POS capabilities (woocommerce_pos_*) granted per user via WP_User::add_cap().
	 *
	 * Unlike the woocommerce_pos_preset meta removed above, these caps live inside the serialized
	 * {prefix}capabilities meta row rather than a woocommerce_ meta key, so the sweep above can't reach
	 * them. Left behind, a reinstall would silently restore POS access because has_pos_access() keys off
	 * these caps. The row also stores the user's role, so strip only the woocommerce_pos_ caps per user
	 * via remove_cap() rather than deleting the row.
	 *
	 * Users are matched by the woocommerce_pos_ cap prefix — the same {prefix}capabilities LIKE that
	 * WP_User_Query's capability__in (used by Capabilities::pos_staff_user_query_args()) is built on —
	 * so no fixed cap list is duplicated here; the PSR-4 Capabilities class is not autoloadable during
	 * uninstall. The per-user strip is prefix-based for the same reason.
	 */
	$pos_staff_ids = get_users(
		array(
			'fields'     => 'ID',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one-off uninstall cleanup, not a runtime query.
				array(
					'key'     => $wpdb->prefix . 'capabilities',
					'value'   => 'woocommerce_pos_',
					'compare' => 'LIKE',
				),
			),
		)
	);
	foreach ( $pos_staff_ids as $pos_staff_id ) {
		$pos_staff_user = new WP_User( (int) $pos_staff_id );
		foreach ( array_keys( $pos_staff_user->caps ) as $pos_capability ) {
			if ( 0 === strpos( (string) $pos_capability, 'woocommerce_pos_' ) ) {
				$pos_staff_user->remove_cap( (string) $pos_capability );
			}
		}
	}

	// Delete our data from the post and post meta tables, and remove any additional tables we created.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'product', 'product_variation', 'shop_coupon', 'shop_order', 'shop_order_refund' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

	$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_type IN ( 'order_note' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->commentmeta} meta LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id WHERE comments.comment_ID IS NULL;" );

	// Delete terms if > WP 4.2 (term splitting was added in 4.2).
	if ( version_compare( $wp_version, '4.2', '>=' ) ) {
		// Delete term taxonomies.
		foreach ( array( 'product_cat', 'product_tag', 'product_shipping_class', 'product_type', 'product_visibility' ) as $_taxonomy ) {
			$wpdb->delete(
				$wpdb->term_taxonomy,
				array(
					'taxonomy' => $_taxonomy,
				)
			);
		}

		// Delete term attributes.
		foreach ( $wc_attributes as $_taxonomy ) {
			$wpdb->delete(
				$wpdb->term_taxonomy,
				array(
					'taxonomy' => 'pa_' . $_taxonomy,
				)
			);
		}

		// Delete orphan relationships.
		$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;" );

		// Delete orphan terms.
		$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;" );

		// Delete orphan term meta.
		if ( ! empty( $wpdb->termmeta ) ) {
			$wpdb->query( "DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;" );
		}
	}

	// Clear any cached data that has been removed.
	wp_cache_flush();
}

unset( $wc_uninstalling_plugin );
