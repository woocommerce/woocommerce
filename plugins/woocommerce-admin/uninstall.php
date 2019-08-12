<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

use \Automattic\WooCommerce\Admin\WC_Admin_Install;

require_once dirname( __FILE__ ) . '/woocommerce-admin.php';
require_once dirname( __FILE__ ) . '/includes/class-wc-admin-install.php';
WC_Admin_Install::delete_table_data();
