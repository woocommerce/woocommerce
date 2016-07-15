<?php
/**
 * Schema.org offer microdata for a single variation on a variable product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/schema-offers/variation.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">

	<?php do_action( 'woocommerce_variation_schema_offer_before_schema', $variation_product ); ?>
	<meta itemprop="price" content="<?php echo esc_attr( $variation_product->get_price() ); ?>" />
	<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
	<link itemprop="availability" href="http://schema.org/<?php echo $variation_product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>" />
	<meta itemprop="name" content="<?php echo esc_attr( $variation_product->get_title() ) . ' (' . esc_attr( $variation_product->get_formatted_variation_attributes( true ) ) . ')'; ?>" />
	<?php do_action( 'woocommerce_variation_schema_offer_after_schema', $variation_product ); ?>

</div>
