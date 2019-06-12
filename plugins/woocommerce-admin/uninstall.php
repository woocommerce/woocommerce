<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once dirname( __FILE__ ) . '/woocommerce-admin.php';

WC_Admin_Feature_Plugin::instance()->includes();
WC_Admin_Install::delete_table_data();
