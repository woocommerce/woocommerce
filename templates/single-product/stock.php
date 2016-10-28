<?php
/**
 * Single Product stock.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/stock.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $product->is_in_stock() ) : ?>

	<p class="stock out-of-stock"><?php _e( 'Out of stock', 'woocommerce' ); ?></p>

<?php elseif ( $product->managing_stock() && $product->is_on_backorder( 1 ) ) : ?>

	<p class="stock available-on-backorder"><?php _e( 'Available on backorder', 'woocommerce' ); ?></p>

<?php elseif ( $product->managing_stock() ) : ?>

	<p class="stock in-stock"><?php echo wp_kses_post( wc_format_stock_for_display( $product->get_stock_amount(), $product->backorders_allowed() && $product->backorders_require_notification() ) ); ?></p>

<?php endif; ?>
