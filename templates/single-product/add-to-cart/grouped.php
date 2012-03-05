<?php
/**
 * Grouped Product Add to Cart
 */
 
global $woocommerce, $product;

// Put grouped products into an array
$grouped_products = array();
$all_products_are_downloadable = true;

foreach ($product->get_children() as $child_id) : 
	$child_product = $product->get_child( $child_id ); 
	
	if ( !$child_product->is_downloadable() || !$child_product->is_virtual() ) $all_products_are_downloadable = false;
	
	$grouped_products[] = array(
		'product' => $child_product,
		'availability' => $child_product->get_availability()
	);	
endforeach;
?>

<?php do_action('woocommerce_before_add_to_cart_form'); ?>

<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart" method="post" enctype='multipart/form-data'>
	<table cellspacing="0" class="group_table">
		<tbody>
			<?php foreach ($grouped_products as $child_product) : ?>
				<tr>
					<td>
						<?php if ($all_products_are_downloadable) : ?>
						
							<button type="submit" name="quantity[<?php echo $child_product['product']->id; ?>]" value="1" class="button alt"><?php _e('Add to cart', 'woocommerce'); ?></button>
						
						<?php else : ?>
						
							<?php woocommerce_quantity_input( array( 'input_name' => 'quantity['.$child_product['product']->id.']', 'input_value' => '0' ) ); ?>
							
						<?php endif; ?>
					</td>
					<td><label for="product-<?php echo $child_product['product']->id; ?>"><?php
						if ($child_product['product']->is_visible()) echo '<a href="'.get_permalink($child_product['product']->id).'">';
						echo $child_product['product']->get_title();
						if ($child_product['product']->is_visible()) echo '</a>';
					?></label></td>
					<td class="price"><?php echo $child_product['product']->get_price_html(); ?>
					<?php echo apply_filters( 'woocommerce_stock_html', '<small class="stock '.$child_product['availability']['class'].'">'.$child_product['availability']['availability'].'</small>', $child_product['availability']['availability'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<?php if (!$all_products_are_downloadable) : ?>
	
		<?php do_action('woocommerce_before_add_to_cart_button'); ?>
	
		<button type="submit" class="button alt"><?php echo apply_filters('single_add_to_cart_text', __('Add to cart', 'woocommerce'), $product->product_type); ?></button>
	
		<?php do_action('woocommerce_after_add_to_cart_button'); ?>
	
	<?php endif; ?>

</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>