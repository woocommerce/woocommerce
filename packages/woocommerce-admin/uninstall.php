<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Don't delete table data if core WooCommerce contains WooCommerce Admin.
// This could alternatively check for a specific WooCommerce version rather than a file.
if (
	defined( 'WC_ABSPATH' ) &&
	file_exists( WC_ABSPATH . 'packages/woocommerce-admin/woocommerce-admin.php' )
) {
	return;
}

use \Automattic\WooCommerce\Admin\Install;

require_once dirname( __FILE__ ) . '/woocommerce-admin.php';

Install::drop_tables();
