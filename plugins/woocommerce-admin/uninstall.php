<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once dirname( __FILE__ ) . '/woocommerce-admin.php';

if ( WC_Admin_Feature_Plugin::instance()->check_dependencies() ) {
	WC_Admin_Feature_Plugin::instance()->includes();
} else {
	require_once dirname( __FILE__ ) . '/includes/class-wc-admin-install.php';
}

WC_Admin_Install::delete_table_data();
