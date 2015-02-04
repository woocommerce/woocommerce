<?php
/**
 * Update WC to 2.3.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// _money_spent and _order_count may be out of sync - clear them
delete_metadata( 'user', 0, '_money_spent', '', true );
delete_metadata( 'user', 0, '_order_count', '', true );