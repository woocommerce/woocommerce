# Add new action hook to allow custom content after cart item meta
<?php
/**
 * Review order template.
 *
 * @package WooCommerce\Templates
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_review_order_after_cart_contents', $cart_item['data'], $cart_item, $cart_item_key );
?>