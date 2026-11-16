<?php
/**
 * Loop Add to Cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/add-to-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     11.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$args = $args ?? array();

// Direct template callers may not supply the data prepared by woocommerce_template_loop_add_to_cart().
$button_data = $button_data ?? array(
	'url'      => $product->add_to_cart_url(),
	'text'     => $product->add_to_cart_text(),
	'quantity' => $args['quantity'] ?? 1,
);
unset( $args['button_data'] );

$aria_describedby = isset( $args['aria-describedby_text'] ) ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product->get_id() ) ) : '';

/**
 * Filters the loop add-to-cart link HTML.
 *
 * @param string     $link    The add-to-cart link HTML.
 * @param WC_Product $product The product object.
 * @param array      $args    Arguments used to build the link.
 *
 * @since 2.0.0
 */
echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The public filter intentionally allows extensions to return link markup.
	'woocommerce_loop_add_to_cart_link',
	sprintf(
		'<a href="%s" %s data-quantity="%s" class="%s" %s>%s</a>',
		esc_url( $button_data['url'] ),
		$aria_describedby,
		esc_attr( $button_data['quantity'] ),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
		isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
		esc_html( $button_data['text'] )
	),
	$product,
	$args
);
?>
<?php if ( isset( $args['aria-describedby_text'] ) ) : ?>
	<span id="woocommerce_loop_add_to_cart_link_describedby_<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text">
		<?php echo esc_html( $args['aria-describedby_text'] ); ?>
	</span>
<?php endif; ?>
