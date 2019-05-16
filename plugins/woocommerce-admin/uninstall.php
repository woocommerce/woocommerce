<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

WC_Admin::instance()->includes();
WC_Admin_Reports_Sync::clear_queued_actions();
WC_Admin_Notes::clear_queued_actions();
wp_clear_scheduled_hook( 'wc_admin_daily' );
