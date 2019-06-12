<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once dirname( __FILE__ ) . '/woocommerce-admin.php';

WC_Admin_Feature_Plugin::instance()->includes();
WC_Admin_Reports_Sync::clear_queued_actions();
WC_Admin_Notes::clear_queued_actions();
WC_Admin_Install::delete_table_data();
wp_clear_scheduled_hook( 'wc_admin_daily' );
