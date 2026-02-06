<?php
/**
 * Single variation cart button
 *
 * Variables available to callers of wc_get_template() for this template:
 *
 * @var bool $add_to_cart_button_disabled Optional. When provided, overrides whether the add to cart button
 *                                        is initially disabled. If not set, defaults to true when the
 *                                        wc-add-to-cart-variation script is enqueued (so the button can be
 *                                        enabled once a variation is selected).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.1
 */

defined( 'ABSPATH' ) || exit;

global $product;

$is_add_to_cart_button_disabled = isset( $add_to_cart_button_disabled )
	? (bool) $add_to_cart_button_disabled
	: wp_script_is( 'wc-add-to-cart-variation', 'enqueued' );
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input(
		array(
			'min_value'   => $product->get_min_purchase_quantity(),
			'max_value'   => $product->get_max_purchase_quantity(),
			'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
		)
	);

	do_action( 'woocommerce_after_add_to_cart_quantity' );
	?>

	<button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"<?php echo $is_add_to_cart_button_disabled ? ' disabled' : ''; ?>><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
