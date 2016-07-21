<?php
/**
 * Schema.org aggregate offer microdata for a variable product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/schema-offers/variation-aggregate.php
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
<div itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer">

	<?php do_action( 'woocommerce_variation_schema_offer_before_aggregate_schema', $variable_product ); ?>
	<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
	<meta itemprop="lowPrice" content="<?php echo esc_attr( $low_price ); ?>" />
	<meta itemprop="highPrice" content="<?php echo esc_attr( $high_price ); ?>" />
	<meta itemprop="offerCount" content="<?php echo esc_attr( $offer_count ); ?>" />
	<?php do_action( 'woocommerce_variation_schema_offer_after_aggregate_schema', $variable_product ); ?>

</div>
