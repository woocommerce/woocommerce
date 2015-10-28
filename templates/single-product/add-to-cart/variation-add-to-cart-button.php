<?php
/**
 * Single variation cart button
 *
 * This is a javascript-based template for single variations (see https://codex.wordpress.org/Javascript_Reference/wp.template).
 * The values will be dynamically replaced after selecting attributes.
 *
 * @see 	http://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.5.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
    <script type="text/template" id="tmpl-variation-add-to-cart-template">
    	<?php woocommerce_quantity_input( array(
            'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : 1,
            'min_value'   => '{{{ data.min_qty }}}',
            'max_value'   => '{{{ data.max_qty }}}'
        ) ); ?>
    	<button type="submit" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
        <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->id ); ?>" />
    	<input type="hidden" name="product_id" value="<?php echo absint( $product->id ); ?>" />
    	<input type="hidden" name="variation_id" class="variation_id" value="{{ data.variation_id }}" />
    </script>
</div>
