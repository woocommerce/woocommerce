<?php
/**
 * Offer microdata for single product pages for SEO.
 *
 * Used for both Simple products, and External products.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/schema-offers/simple.php
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

global $product;

?>
<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">

	<?php do_action( 'woocommerce_simple_schema_offer_before_schema' ); ?>
	<meta itemprop="price" content="<?php echo esc_attr( $product->get_display_price() ); ?>" />
	<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
	<link itemprop="availability" href="http://schema.org/<?php echo $product->is_in_stock() ? 'InStock' : 'OutOfStock'; ?>" />
	<?php do_action( 'woocommerce_simple_schema_offer_after_schema' ); ?>

</div>
