<?php
/**
 * WC Admin Uninstall
 *
 * @package WC_Admin\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

use \Automattic\WooCommerce\Admin\Install;

require_once dirname( __FILE__ ) . '/woocommerce-admin.php';

Install::delete_table_data();
