<?php
/**
 * Single variation display
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
?>
<div class="woocommerce-variation single_variation">
    <script type="text/template" id="tmpl-variation-template">
        <div class="woocommerce-variation-description">
            {{{ data.description }}}
        </div>

        <div class="woocommerce-variation-price">
            {{{ data.price }}}
        </div>

        <div class="woocommerce-variation-availability">
            {{{ data.availability }}}
        </div>
    </script>

    <script type="text/template" id="tmpl-unavailable-variation-template">
        <p><?php _e( 'Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce' ); ?></p>
    </script>
</div>
