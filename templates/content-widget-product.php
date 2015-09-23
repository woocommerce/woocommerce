<?php
/**
 * The template for displaying product widget entries.
 *
 * Override this template by copying it to yourtheme/woocommerce/content-widget-product.php
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 1.0.0
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product; ?>

<li>
	<a href="<?php echo esc_url( get_permalink( $product->id ) ); ?>" title="<?php echo esc_attr( $product->get_title() ); ?>">
		<?php echo $product->get_image(); ?>
		<span class="product-title"><?php echo $product->get_title(); ?></span>
	</a>
	<?php if ( ! empty( $show_rating ) ) echo $product->get_rating_html(); ?>
	<?php echo $product->get_price_html(); ?>
</li>
