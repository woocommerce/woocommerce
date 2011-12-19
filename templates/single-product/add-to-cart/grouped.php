<?php
/**
 * Grouped Product Add to Cart
 */
 
global $woocommerce, $product;
?>

<?php do_action('woocommerce_before_add_to_cart_form'); ?>

<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart" method="post" enctype='multipart/form-data'>
	<table cellspacing="0" class="group_table">
		<tbody>
			<?php foreach ($product->get_children() as $child_id) : $child_product = $product->get_child( $child_id ); $cavailability = $child_product->get_availability(); ?>
				<tr>
					<td><?php woocommerce_quantity_input( array( 'input_name' => 'quantity['.$child_product->id.']', 'input_value' => '0' ) ); ?></td>
					<td><label for="product-<?php echo $child_product->id; ?>"><?php
						if ($child_product->is_visible()) echo '<a href="'.get_permalink($child_product->id).'">';
						echo $child_product->get_title();
						if ($child_product->is_visible()) echo '</a>';
					?></label></td>
					<td class="price"><?php echo $child_product->get_price_html(); ?>
					<?php echo apply_filters( 'woocommerce_stock_html', '<small class="stock '.$cavailability['class'].'">'.$cavailability['availability'].'</small>', $cavailability['availability'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php do_action('woocommerce_before_add_to_cart_button'); ?>

	<button type="submit" class="button alt"><?php _e('Add to cart', 'woothemes'); ?></button>

	<?php do_action('woocommerce_after_add_to_cart_button'); ?>

</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>